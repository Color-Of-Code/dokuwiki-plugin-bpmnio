function extractXml(data) {
    return decodeURIComponent(escape(window.atob(data)));
}

async function renderDiagram(xml, container, viewer, computeSizeFn) {
    try {
        const result = await viewer.importXML(xml);
        const { warnings } = result;
        console.log(warnings);

        const size = computeSizeFn(viewer);
        container.style.height = `${size.height}px`;
        container.style.width = `max(100%,${size.width}px)`;
    } catch (err) {
        container.textContent = err;
        console.log(err.message, err.warnings);
    }
}

function computeBpmnDiagramSize(viewer) {
    const canvas = viewer.get("canvas");
    const bboxViewport = canvas.getActiveLayer().getBBox();
    const bboxSvg = canvas.getSize();
    canvas.viewbox({
        x: bboxViewport.x,
        y: bboxViewport.y,
        width: bboxSvg.width,
        height: bboxSvg.height,
    });
    return {
        width: bboxViewport.width,
        height: bboxViewport.height,
    };
}

function computeDmnDiagramSize(viewer) {
    const activeView = viewer.getActiveView();

    if (activeView.type === "drd") {
        const activeEditor = viewer.getActiveViewer();

        // access active editor components
        const canvas = activeEditor.get("canvas");

        const bboxViewport = canvas.getActiveLayer().getBBox();
        const bboxSvg = canvas.getSize();
        canvas.viewbox({
            x: bboxViewport.x,
            y: bboxViewport.y,
            width: bboxSvg.width,
            height: bboxSvg.height,
        });
        return {
            width: bboxViewport.width,
            height: bboxViewport.height,
        };
    }
    return {
        width: 0,
        height: 0,
    };
}

async function renderBpmnDiagram(xml, container) {
    const BpmnViewer = window.BpmnJS;
    const viewer = new BpmnViewer({ container });

    renderDiagram(xml, container, viewer, computeBpmnDiagramSize);
}

async function renderDmnDiagram(xml, container) {
    const DmnViewer = window.DmnJS;
    const viewer = new DmnViewer({ container });

    renderDiagram(xml, container, viewer, computeDmnDiagramSize);
}

function safeRender(tag, fn) {
    try {
        const root = jQuery(tag);
        const container = root.find(".bpmn_js_container")[0];
        // avoid double rendering
        if (container.children?.length > 0) return;

        const data = root.find(".bpmn_js_data")[0];
        const xml = extractXml(data.textContent);

        fn(xml, container);
    } catch (err) {
        console.warn(err.message);
    }
}

jQuery(document).ready(function () {
    jQuery("div[id^=__bpmn_js_]").each((_, tag) =>
        safeRender(tag, renderBpmnDiagram)
    );
    jQuery("div[id^=__dmn_js_]").each((_, tag) =>
        safeRender(tag, renderDmnDiagram)
    );
});
