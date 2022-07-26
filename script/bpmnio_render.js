function extractXml(data) {
    return decodeURIComponent(escape(window.atob(data)));
}

async function renderDiagram(xml, container, viewer, computeSizeFn) {
    try {
        const result = await viewer.importXML(xml);
        const { warnings } = result;
        if (warnings?.length > 0) console.log(warnings);

        if (!computeSizeFn) return;

        const size = computeSizeFn(viewer);
        if (!size) return;

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
    return undefined;
}

async function renderBpmnDiagram(xml, container) {
    const BpmnViewer = window.BpmnJS.Viewer;
    const viewer = new BpmnViewer({ container });

    renderDiagram(xml, container, viewer, computeBpmnDiagramSize);
}

async function renderDmnDiagram(xml, container) {
    const DmnViewer = window.DmnJS;
    const viewer = new DmnViewer({ container });

    renderDiagram(xml, container, viewer, computeDmnDiagramSize);
}

async function transferXmlToForm(editor) {
    try {
        const options = { format: true };
        const result = await editor.saveXML(options);
        const { xml } = result;
        jQuery('input[name="plugin_bpmnio_data"]').val(window.btoa(xml));
    } catch (err) {
        console.log(err);
    }
}

async function renderEditor(xml, container) {
    const BpmnEditor = window.BpmnJS;
    const editor = new BpmnEditor({ container });

    editor.on("commandStack.changed", () => transferXmlToForm(editor));

    renderDiagram(xml, container, editor, null);
    transferXmlToForm(editor);
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
    jQuery("div[id=plugin_bpmnio__editor]").each((_, tag) =>
        safeRender(tag, renderEditor)
    );
});
