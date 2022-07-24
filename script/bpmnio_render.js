function extractXml(data) {
    return decodeURIComponent(escape(window.atob(data)));
}

async function renderDiagram(xml, container, viewer) {
    try {
        const result = await viewer.importXML(xml);
        const { warnings } = result;
        console.log(warnings);
        const canvas = viewer.get("canvas");
        const bboxViewport = canvas.getActiveLayer().getBBox();
        const bboxSvg = canvas.getSize();
        canvas.viewbox({
            x: bboxViewport.x,
            y: bboxViewport.y,
            width: bboxSvg.width,
            height: bboxSvg.height,
        });
        container.style.height = `${bboxViewport.height}px`;
        container.style.width = `max(100%,${bboxViewport.width}px)`;
    } catch (err) {
        container.text = err;
        console.log(err.message, err.warnings);
    }
}

async function renderBpmnDiagram(xml, container) {
    const BpmnViewer = window.BpmnJS;
    const viewer = new BpmnViewer({ container });

    renderDiagram(xml, container, viewer);
}

async function renderDmnDiagram(xml, container) {
    const DmnViewer = window.DmnJS;
    const viewer = new DmnViewer({ container });

    renderDiagram(xml, container, viewer);
}

function safeRender(tag, fn) {
    try {
        const container = jQuery(tag).find(".bpmn_js_container")[0];
        // avoid double rendering
        if (container.children.length > 0) return;

        const data = jQuery(tag).find(".bpmn_js_data")[0];
        const xml = extractXml(data.textContent);

        fn(xml, container);
    } catch (err) {
        console.warn(err.message);
    }
}

jQuery(document).ready(function () {
    jQuery("div[id^=__bpmn_js_]").each((_, tag) => safeRender(tag, renderBpmnDiagram));
    jQuery("div[id^=__dmn_js_]").each((_, tag) => safeRender(tag, renderDmnDiagram));
});
