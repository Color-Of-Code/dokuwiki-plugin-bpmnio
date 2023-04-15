<?php
/**
 * @group plugin_bpmnio
 * @group plugins
 */
class syntax_plugin_bpmnio_test extends DokuWikiTest {

    protected $pluginsEnabled = array('bpmnio');

    public function test_syntax_bpmn() {
        $info = array();
        $expected = <<<OUT
        <div class="plugin-bpmnio" id="__bpmn_js_643ada031891e"><div class="bpmn_js_data">
            ClhNTC4uLgo=
        </div><div class="bpmn_js_canvas sectionedit1">
            <div class="bpmn_js_container"></div>
        </div>
        OUT;

        $input = <<<IN
        <bpmnio type="bpmn">
        XML...
        </bpmnio>
        IN;
 
        $instructions = p_get_instructions($input);
        $xhtml = p_render('xhtml', $instructions, $info);
 
        $this->assertEquals($expected, $xhtml);
    }

    public function test_sytax_dmn() {
        $info = array();
        $expected = <<<OUT
        <div class="plugin-bpmnio" id="__dmn_js_643ada031891e"><div class="dmn_js_data">
            ClhNTC4uLgo=
        </div><div class="dmn_js_canvas sectionedit1">
            <div class="dmn_js_container"></div>
        </div>
        OUT;

        $input = <<<IN
        <bpmnio type="dmn">
        XML...
        </bpmnio>
        IN;
 
        $instructions = p_get_instructions($input);
        $xhtml = p_render('xhtml', $instructions, $info);
 
        $this->assertEquals($expected, $xhtml);
    }
}
