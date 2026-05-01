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

function getSvgCacheKey(container) {
    return container?.dataset?.svgCacheKey ?? "";
}

async function saveSvgFallback(viewer, container, type) {
    const cacheKey = getSvgCacheKey(container);
    if (
        !cacheKey
        || container.dataset.svgCacheUploaded === "true"
        || container.dataset.svgCacheUnsupported === "true"
    ) {
        return;
    }

    let svgViewer = viewer;

    if (type === "dmn") {
        const activeView = viewer.getActiveView?.();
        if (!activeView || activeView.type !== "drd") {
            container.dataset.svgCacheUnsupported = "true";
            return;
        }
        svgViewer = viewer.getActiveViewer?.() ?? viewer;
    }

    const saveSvg = svgViewer?.saveSVG;
    if (typeof saveSvg !== "function") {
        return;
    }

    let svg = "";
    try {
        const result = await saveSvg.call(svgViewer);
        svg = result?.svg ?? "";
    } catch {
        return;
    }

    if (!svg) {
        return;
    }

    const base = window.DOKU_BASE ?? "/";
    const body = new URLSearchParams({
        call: "plugin_bpmnio_svg_cache",
        key: cacheKey,
        type,
        svg,
    });

    try {
        const response = await fetch(`${base}lib/exe/ajax.php`, {
            method: "POST",
            body,
            credentials: "same-origin",
            headers: {
                Accept: "application/json",
            },
        });

        if (response.ok) {
            const payload = await response.json().catch(() => null);
            if (payload?.ok === true) {
                container.dataset.svgCacheUploaded = "true";
            }
        }
    } catch {
        // Keep the on-page render working even if the PDF fallback upload fails.
    }
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

function extractPayload(data) {
    if (!data) {
        return "";
    }

    return new TextDecoder().decode(
        Uint8Array.from(atob(data), (c) => c.charCodeAt(0))
    );
}

function parseLinkMap(root, type) {
    const dataId = "." + type + "_js_links";
    const payload = root.find(dataId)[0];

    if (!payload?.textContent?.trim()) {
        return {};
    }

    try {
        return JSON.parse(extractPayload(payload.textContent.trim()));
    } catch {
        return {};
    }
}

function getElementRegistry(viewer, type) {
    if (type === "dmn") {
        const activeView = viewer.getActiveView();
        if (!activeView || activeView.type !== "drd") {
            return null;
        }

        return viewer.getActiveViewer()?.get("elementRegistry") ?? null;
    }

    return viewer.get("elementRegistry");
}

function openDiagramLink(event, href) {
    if (event.button !== undefined && event.button !== 0) {
        return;
    }

    if (event.metaKey || event.ctrlKey) {
        window.open(href, "_blank", "noopener");
        return;
    }

    window.location.assign(href);
}

function setGraphicsTooltip(graphics, href) {
    let tooltip = graphics.querySelector(":scope > title");
    if (!tooltip) {
        tooltip = document.createElementNS("http://www.w3.org/2000/svg", "title");
        graphics.insertBefore(tooltip, graphics.firstChild);
    }

    tooltip.textContent = href;
}

function wireGraphicsLink(graphics, href, linkClass = "wikilink1") {
    if (!graphics) {
        return;
    }

    if (graphics.dataset.bpmnioLinked !== "true") {
        graphics.addEventListener("click", (event) => openDiagramLink(event, href));
        graphics.addEventListener("keydown", (event) => {
            if (event.key !== "Enter" && event.key !== " ") {
                return;
            }

            event.preventDefault();
            openDiagramLink(event, href);
        });
    }

    graphics.setAttribute("tabindex", "0");
    graphics.setAttribute("role", "link");
    graphics.setAttribute("aria-label", href);
    setGraphicsTooltip(graphics, href);
    graphics.dataset.bpmnioLinked = "true";
    graphics.classList.add("bpmnio-linked", linkClass);
}

function applyDiagramLinks(viewer, type, links) {
    const elementRegistry = getElementRegistry(viewer, type);
    if (!elementRegistry) {
        return;
    }

    for (const [elementId, link] of Object.entries(links)) {
        if (!link?.href) {
            continue;
        }

        const element = elementRegistry.get(elementId);
        if (!element) {
            continue;
        }

        wireGraphicsLink(elementRegistry.getGraphics(element), link.href);

        const labelElement = elementRegistry.get(`${elementId}_label`);
        if (labelElement) {
            wireGraphicsLink(elementRegistry.getGraphics(labelElement), link.href);
        }
    }
}

function restoreWikiLinks(xml, links) {
    if (!links || Object.keys(links).length === 0) {
        return xml;
    }

    const parser = new DOMParser();
    const document = parser.parseFromString(xml, "application/xml");

    if (document.querySelector("parsererror")) {
        return xml;
    }

    const elements = document.getElementsByTagName("*");
    for (const element of elements) {
        const elementId = element.getAttribute("id");
        if (!elementId || !Object.hasOwn(links, elementId) || !element.hasAttribute("name")) {
            continue;
        }

        const currentName = element.getAttribute("name").trim();
        const target = links[elementId]?.target;
        if (!target) {
            continue;
        }

        const linkMarkup = currentName === "" || currentName === target
            ? `[[${target}]]`
            : `[[${target}|${currentName}]]`;

        element.setAttribute("name", linkMarkup);
    }

    return new XMLSerializer().serializeToString(document);
}

async function renderDiagram(xml, container, viewer, computeSizeFn, linkMap = {}, type) {
    try {
        clearContainerError(container);
        await viewer.importXML(xml);

        applyDiagramLinks(viewer, type, linkMap);

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
    const root = jQuery(container).closest(".plugin-bpmnio");
    const linkMap = parseLinkMap(root, "bpmn");

    await renderDiagram(xml, container, viewer, computeBpmnDiagramSize, linkMap, "bpmn");
    await saveSvgFallback(viewer, container, "bpmn");
}

async function renderDmnDiagram(xml, container) {
    const DmnViewer = window.DmnJSViewer;
    if (typeof DmnViewer !== "function") {
        throw new Error("DMN viewer library is unavailable.");
    }

    const viewer = new DmnViewer({ container });
    const root = jQuery(container).closest(".plugin-bpmnio");
    const linkMap = parseLinkMap(root, "dmn");

    await renderDiagram(xml, container, viewer, computeDmnDiagramSize, linkMap, "dmn");
    await saveSvgFallback(viewer, container, "dmn");
}

async function exportDataBase64(editor, linkMap = {}) {
    try {
        if (typeof editor?.saveXML !== "function") {
            return null;
        }

        const options = { format: true };
        const result = await editor.saveXML(options);
        const { xml } = result;
        if (typeof xml === "string" && xml.length > 0) {
            const restoredXml = restoreWikiLinks(xml, linkMap);
            const encoder = new TextEncoder();
            const data = encoder.encode(restoredXml);
            return btoa(String.fromCharCode(...data));
        }
    } catch {
        return null;
    }

    return null;
}

function addFormSubmitListener(editor, container, type) {
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
        const root = jQuery(container).closest(".plugin-bpmnio");
        const linkMap = parseLinkMap(root, type);
        const data = await exportDataBase64(editor, linkMap);
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
    addFormSubmitListener(editor, container, "bpmn");
    return renderDiagram(xml, container, editor, null, {}, "bpmn");
}

async function renderDmnEditor(xml, container) {
    const DmnEditor = window.DmnJS;
    if (typeof DmnEditor !== "function") {
        throw new Error("DMN editor library is unavailable.");
    }

    const editor = new DmnEditor({ container });
    addFormSubmitListener(editor, container, "dmn");
    return renderDiagram(xml, container, editor, null, {}, "dmn");
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
