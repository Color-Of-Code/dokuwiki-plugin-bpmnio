function extractXml(data) {
    return decodeURIComponent(escape(window.atob(data)));
}

async function renderBpmnTag(data, container) {
    const xml = extractXml(data);

    // bundle exposes the viewer / modeler via the BpmnJS variable
    const BpmnViewer = window.BpmnJS;
    const viewer = new BpmnViewer({ container });

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

function safeRenderBpmnTag(tag) {
    try {
        const container = jQuery(tag).find(".bpmn_js_container")[0];
        // avoid double rendering
        if (container.children.length > 0) return;

        const data = jQuery(tag).find(".bpmn_js_data")[0];
        const encodedData = data.textContent;
        renderBpmnTag(encodedData, container);
    } catch (err) {
        console.warn(err.message);
    }
}

jQuery(document).ready(function () {
    jQuery("div[id^=__bpmn_js_]").each((_, tag) => safeRenderBpmnTag(tag));
});
