function extractXml(data) {
    return new TextDecoder()
        .decode(Uint8Array.from(atob(data), (c) => c.charCodeAt(0)))
        .trim();
}

function getErrorMessage(error, fallbackMessage) {
    if (error instanceof Error && error.message) {
        return error.message;
    }

    if (typeof error === "string" && error.length > 0) {
        return error;
    }

    return fallbackMessage;
}

function getPluginRoot(target) {
    if (!(target instanceof Element)) {
        return null;
    }

    return target.closest(".plugin-bpmnio") ?? target;
}

function getStatusElement(target) {
    const root = getPluginRoot(target);
    if (!root) {
        return null;
    }

    let status = root.querySelector(".plugin_bpmnio_status");
    if (!status) {
        status = document.createElement("div");
        status.className = "plugin_bpmnio_status";
        root.append(status);
    }

    return status;
}

function clearStatusMessage(target) {
    const status = getPluginRoot(target)?.querySelector(".plugin_bpmnio_status");
    if (status) {
        status.remove();
    }
}

function showStatusMessage(target, message) {
    const status = getStatusElement(target);
    if (!status) {
        return;
    }

    status.textContent = message;
    status.style.color = "red";
}

function showContainerError(container, message) {
    clearStatusMessage(container);
    container.textContent = message;
    container.style.color = "red";
}

function clearContainerError(container) {
    container.style.color = "";
    clearStatusMessage(container);
}

function getLayerBounds(canvas) {
    const layer = canvas?.getActiveLayer?.();
    if (!layer || typeof layer.getBBox !== "function") {
        return null;
    }

    const bounds = layer.getBBox();
    if (!bounds) {
        return null;
    }

    const values = [bounds.x, bounds.y, bounds.width, bounds.height];
    if (!values.every(Number.isFinite)) {
        return null;
    }

    return bounds;
}

async function renderDiagram(xml, container, viewer, computeSizeFn) {
    try {
        clearContainerError(container);
        await viewer.importXML(xml);

        if (!computeSizeFn) return;

        const zoom = getZoomFactor(container);
        const layout = computeSizeFn(viewer, zoom);
        if (!layout) return;

        container.style.height = `${layout.scaledHeight}px`;
        container.style.width = `${layout.scaledWidth}px`;

        if (typeof layout.applyZoom === "function") {
            layout.applyZoom();
        }
    } catch (err) {
        showContainerError(
            container,
            getErrorMessage(err, "Unable to render diagram.")
        );
    }
}

function getZoomFactor(container) {
    const zoom = Number.parseFloat(container.dataset.zoom ?? "1");

    if (!Number.isFinite(zoom) || zoom <= 0) {
        return 1;
    }

    return zoom;
}

function computeBpmnDiagramSize(viewer, zoom) {
    const canvas = viewer.get("canvas");
    const bboxViewport = getLayerBounds(canvas);
    if (!bboxViewport) {
        return undefined;
    }

    const width = bboxViewport.width + 4;
    const height = bboxViewport.height + 4;

    return {
        width,
        height,
        scaledWidth: Math.max(width * zoom, 1),
        scaledHeight: Math.max(height * zoom, 1),
        applyZoom() {
            canvas.resized();
            canvas.viewbox({
                x: bboxViewport.x - 2,
                y: bboxViewport.y - 2,
                width,
                height,
            });
        },
    };
}

function computeDmnDiagramSize(viewer, zoom) {
    const activeView = viewer.getActiveView();
    if (!activeView || activeView.type !== "drd") {
        return undefined;
    }

    const activeEditor = viewer.getActiveViewer();

    const canvas = activeEditor?.get("canvas");
    const bboxViewport = getLayerBounds(canvas);
    if (!bboxViewport) {
        return undefined;
    }

    const width = bboxViewport.width + 4;
    const height = bboxViewport.height + 4;
    return {
        width,
        height,
        scaledWidth: Math.max(width * zoom, 1),
        scaledHeight: Math.max(height * zoom, 1),
        applyZoom() {
            canvas.resized();
            canvas.viewbox({
                x: bboxViewport.x - 2,
                y: bboxViewport.y - 2,
                width,
                height,
            });
        },
    };
}

async function renderBpmnDiagram(xml, container) {
    const BpmnViewer = window.BpmnJS?.Viewer;
    if (typeof BpmnViewer !== "function") {
        throw new Error("BPMN viewer library is unavailable.");
    }

    const viewer = new BpmnViewer({ container });

    return renderDiagram(xml, container, viewer, computeBpmnDiagramSize);
}

async function renderDmnDiagram(xml, container) {
    const DmnViewer = window.DmnJSViewer;
    if (typeof DmnViewer !== "function") {
        throw new Error("DMN viewer library is unavailable.");
    }

    const viewer = new DmnViewer({ container });

    return renderDiagram(xml, container, viewer, computeDmnDiagramSize);
}

async function exportDataBase64(editor) {
    try {
        if (typeof editor?.saveXML !== "function") {
            return null;
        }

        const options = { format: true };
        const result = await editor.saveXML(options);
        const { xml } = result;
        if (typeof xml === "string" && xml.length > 0) {
            const encoder = new TextEncoder();
            const data = encoder.encode(xml);
            return btoa(String.fromCharCode(...data));
        }
    } catch {
        return null;
    }

    return null;
}

function addFormSubmitListener(editor, container) {
    const form = document.getElementById("dw__editform");
    if (!form) {
        showStatusMessage(container, "Editor form is unavailable.");
        return;
    }

    if (form.dataset.pluginBpmnioListenerBound === "true") {
        return;
    }

    form.dataset.pluginBpmnioListenerBound = "true";
    form.addEventListener("submit", async (event) => {
        if (form.dataset.pluginBpmnioSubmitting === "true") {
            delete form.dataset.pluginBpmnioSubmitting;
            return;
        }

        event.preventDefault();

        const field = form.querySelector('input[name="plugin_bpmnio_data"]');
        if (!field) {
            showStatusMessage(container, "Diagram data field is unavailable.");
            return;
        }

        clearStatusMessage(container);
        const data = await exportDataBase64(editor);
        if (!data) {
            showStatusMessage(container, "Unable to save diagram changes.");
            return;
        }

        field.value = data;
        form.dataset.pluginBpmnioSubmitting = "true";

        if (typeof form.requestSubmit === "function") {
            form.requestSubmit(event.submitter);
            return;
        }

        delete form.dataset.pluginBpmnioSubmitting;
        form.submit();
    });
}

async function renderBpmnEditor(xml, container) {
    const BpmnEditor = window.BpmnJS;
    if (typeof BpmnEditor !== "function") {
        throw new Error("BPMN editor library is unavailable.");
    }

    const editor = new BpmnEditor({ container });
    addFormSubmitListener(editor, container);
    return renderDiagram(xml, container, editor, null);
}

async function renderDmnEditor(xml, container) {
    const DmnEditor = window.DmnJS;
    if (typeof DmnEditor !== "function") {
        throw new Error("DMN editor library is unavailable.");
    }

    const editor = new DmnEditor({ container });
    addFormSubmitListener(editor, container);
    return renderDiagram(xml, container, editor, null);
}

function startRender(fn, xml, container) {
    Promise.resolve(fn(xml, container)).catch((error) => {
        showContainerError(
            container,
            getErrorMessage(error, "Unable to initialize diagram.")
        );
    });
}

function safeRender(tag, type, fn) {
    try {
        const root = jQuery(tag);
        const containerId = "." + type + "_js_container";
        const container = root.find(containerId)[0];
        if (!container) {
            showStatusMessage(tag, "Diagram container is missing.");
            return;
        }

        // avoid double rendering
        if (container.children?.length > 0) return;

        const dataId = "." + type + "_js_data";
        const data = root.find(dataId)[0];
        if (!data) {
            showContainerError(container, "Diagram data is missing.");
            return;
        }

        const xml = extractXml(data.textContent);

        if (xml.startsWith("Error:")) {
            showContainerError(container, xml);
            return;
        }

        startRender(fn, xml, container);
    } catch (err) {
        showStatusMessage(
            tag,
            getErrorMessage(err, "Unable to initialize diagram.")
        );
    }
}

jQuery(document).ready(function () {
    jQuery("div[id^=__bpmn_js_]").each((_, tag) =>
        safeRender(tag, "bpmn", renderBpmnDiagram)
    );
    jQuery("div[id^=__dmn_js_]").each((_, tag) =>
        safeRender(tag, "dmn", renderDmnDiagram)
    );
    jQuery("div[id=plugin_bpmnio__bpmn_editor]").each((_, tag) =>
        safeRender(tag, "bpmn", renderBpmnEditor)
    );
    jQuery("div[id=plugin_bpmnio__dmn_editor]").each((_, tag) =>
        safeRender(tag, "dmn", renderDmnEditor)
    );
});
