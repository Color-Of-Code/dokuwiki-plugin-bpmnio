<?php
require_once dirname(__FILE__).'/../syntax/bpmnio.php';

/**
 * @group plugin_bpmnio
 * @group plugins
 */
class syntax_plugin_bpmnio_bpmnio_test extends DokuWikiTest {

    public function test_superscript() {
        $info = array();
        $expected = "\n<p>\nThis is <sup>superscripted</sup> text.<br />\n</p>\n";
 
        $instructions = p_get_instructions('This is ^^superscripted^^ text.');
        $xhtml = p_render('xhtml', $instructions, $info);
 
        $this->assertEquals($expected, $xhtml);
    }
}
