function extractXm(tag) {
    const text = jQuery(tag).text();
    return decodeURIComponent(escape(window.atob(text)));
}

async function replaceBpmnTag(tag) {
    const xml = extractXm(tag);

    // avoid doing it twice
    jQuery(tag).removeAttr("id");

    // bundle exposes the viewer / modeler via the BpmnJS variable
    const BpmnViewer = window.BpmnJS;
    let containerdiv = document.createElement("div");
    containerdiv.className = "plugin-bpmnio";
    jQuery(tag).parent().append(containerdiv);
    const viewer = new BpmnViewer({ container: containerdiv });

    try {
        const result = await viewer.importXML(xml);
        const { warnings } = result;
        console.log(warnings);
        let canvas = viewer.get("canvas");
        const bboxViewport = canvas.getActiveLayer().getBBox();
        const bboxSvg = canvas.getSize();
        canvas.viewbox({
            x: bboxViewport.x,
            y: bboxViewport.y,
            width: bboxSvg.width,
            height: bboxSvg.height,
        });
        containerdiv.style.height = `${bboxViewport.height}px`;
        containerdiv.style.width = `max(100%,${bboxViewport.width}px)`;
    } catch (err) {
        containerdiv.text = err;
        console.log(err.message, err.warnings);
    }

    jQuery(tag).remove();
}

function safeReplaceBpmnTag(tag) {
    try {
        replaceBpmnTag(tag);
    } catch (err) {
        console.warn(err.message);
    }
}

jQuery(document).ready(function () {
    jQuery("textarea[id^=__bpmn_js_]").each((_, tag) =>
        safeReplaceBpmnTag(tag)
    );
});
