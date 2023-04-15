<?php
require_once dirname(__FILE__).'/../action/editor.php';

/**
 * @group plugin_bpmnio
 * @group plugins
 */
class action_plugin_bpmnio_editor_test extends DokuWikiTest {


    function test_handle_form() {
        $event = array(
            'data' => array(
                'target' => 'plugin_bpmnio_bpmn'
            )
        );
        $expect = <<<EOF
        TEST
        EOF;

        $action = new action_plugin_bpmnio_editor();
        $output = $action->handle_form($event);

        $this->assertEquals($expect, $output);
    }

}
