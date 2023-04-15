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
        <p>

        &lt;bpmnio type=“bpmn”&gt;
        </p>
        <pre class="code">XML...</pre>
        <p>
        &lt;/bpmnio&gt;
        </p>
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
        <p>

        &lt;bpmnio type=“dmn”&gt;
        </p>
        <pre class="code">XML...</pre>
        <p>
        &lt;/bpmnio&gt;
        </p>
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
