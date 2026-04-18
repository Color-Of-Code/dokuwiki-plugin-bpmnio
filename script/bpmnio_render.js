function extractXml(data) {
    return new TextDecoder()
        .decode(Uint8Array.from(atob(data), (c) => c.charCodeAt(0)))
        .trim();
}

async function renderDiagram(xml, container, viewer, computeSizeFn) {
    try {
        const result = await viewer.importXML(xml);
        const { warnings } = result;
        if (warnings?.length > 0) console.warn(warnings);

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
        container.textContent = err;
        console.error(err.message, err.warnings);
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
    const bboxViewport = canvas.getActiveLayer().getBBox();
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

    if (activeView.type === "drd") {
        const activeEditor = viewer.getActiveViewer();

        // access active editor components
        const canvas = activeEditor.get("canvas");

        const bboxViewport = canvas.getActiveLayer().getBBox();
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
    return undefined;
}

async function renderBpmnDiagram(xml, container) {
    const BpmnViewer = window.BpmnJS.Viewer;
    const viewer = new BpmnViewer({ container });

    renderDiagram(xml, container, viewer, computeBpmnDiagramSize);
}

async function renderDmnDiagram(xml, container) {
    const DmnViewer = window.DmnJSViewer;
    const viewer = new DmnViewer({ container });

    renderDiagram(xml, container, viewer, computeDmnDiagramSize);
}

async function exportDataBase64(editor) {
    try {
        const options = { format: true };
        const result = await editor.saveXML(options);
        const { xml } = result;
        if (xml.length > 0) {
            const encoder = new TextEncoder();
            const data = encoder.encode(xml);
            return btoa(String.fromCharCode(...data));
        }
    } catch (err) {
        console.error(err);
        return null;
    }
}

function addFormSubmitListener(editor) {
    const form = document.getElementById("dw__editform");
    form.addEventListener("submit", async () => {
        const data = await exportDataBase64(editor);
        const field = form.querySelector('input[name="plugin_bpmnio_data"]');
        if (field && data) {
            field.value = data;
        }
    });
}

async function renderBpmnEditor(xml, container) {
    const BpmnEditor = window.BpmnJS;
    const editor = new BpmnEditor({ container });
    addFormSubmitListener(editor);
    renderDiagram(xml, container, editor, null);
}

async function renderDmnEditor(xml, container) {
    const DmnEditor = window.DmnJS;
    const editor = new DmnEditor({ container });
    addFormSubmitListener(editor);
    renderDiagram(xml, container, editor, null);
}

function safeRender(tag, type, fn) {
    try {
        const root = jQuery(tag);
        const containerId = "." + type + "_js_container";
        const container = root.find(containerId)[0];
        // avoid double rendering
        if (container.children?.length > 0) return;

        const dataId = "." + type + "_js_data";
        const data = root.find(dataId)[0];
        const xml = extractXml(data.textContent);

        if (xml.startsWith("Error:")) {
            container.textContent = xml;
            container.style.color = "red";
            return;
        }

        fn(xml, container);
    } catch (err) {
        console.warn(err.message);
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
