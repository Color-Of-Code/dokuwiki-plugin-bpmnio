<?php
require_once dirname(__FILE__).'/../syntax/bpmnio.php';

/**
 * @group plugin_bpmnio
 * @group plugins
 */
class syntax_plugin_bpmnio_bpmnio_test extends DokuWikiTest {

    public function syntax_bpmn() {
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

    public function syntax_dmn() {
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
