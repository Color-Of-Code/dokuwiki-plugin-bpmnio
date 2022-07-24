function extractXml(tag) {
    const text = jQuery(tag).text();
    return decodeURIComponent(escape(window.atob(text)));
}

async function replaceBpmnTag(xml, container) {
    const ViewerClass = window.BpmnJS;
    const viewer = new ViewerClass({ container });

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
        container.style.height = `${bboxViewport.height}px`;
        container.style.width = `max(100%,${bboxViewport.width}px)`;
    } catch (err) {
        container.text = err;
        console.log(err.message, err.warnings);
    }
}

async function replaceDmnTag(xml, container) {
    const ViewerClass = window.DmnJS;
    const viewer = new ViewerClass({ container });

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
        container.style.height = `${bboxViewport.height}px`;
        container.style.width = `max(100%,${bboxViewport.width}px)`;
    } catch (err) {
        container.text = err;
        console.log(err.message, err.warnings);
    }
}

function safeReplace(tag, fn) {
    try {
        const xml = extractXml(tag);

        // avoid doing it twice
        jQuery(tag).removeAttr("id");

        // bundle exposes the viewer / modeler via the BpmnJS variable
        let container = document.createElement("div");
        container.className = "plugin-bpmnio";
        jQuery(tag).parent().append(container);

        fn(xml, container);

        jQuery(tag).remove();
    } catch (err) {
        console.warn(err.message);
    }
}

jQuery(document).ready(function () {
    jQuery("textarea[id^=__bpmn_js_]").each((_, tag) =>
        safeReplace(tag, replaceBpmnTag)
    );
    jQuery("textarea[id^=__dmn_js_]").each((_, tag) =>
        safeReplace(tag, replaceDmnTag)
    );
});
