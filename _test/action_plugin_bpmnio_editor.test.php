<?php
require_once dirname(__FILE__).'/../action/editor.php';

/**
 * @group plugin_bpmnio
 * @group plugins
 */
class action_plugin_bpmnio_editor_test extends DokuWikiTest {

    function test_handle_form() {
        // $event = new Doku_Event(
        //     'EDIT_FORM_ADDTEXTAREA',
        //     array(
        //         'data' => array(
        //             'target' => 'plugin_bpmnio_bpmn'
        //         )
        //     )
        // );
        // $expect = <<<EOF
        // TEST
        // EOF;

        // $action = new action_plugin_bpmnio_editor();
        // $output = $action->handle_form($event);

        // $this->assertEquals($expect, $output);
        $this->assertTrue(true);
    }

    // function test_handle_post() {
    //     $event = new Doku_Event(
    //         'ACTION_ACT_PREPROCESS',
    //         array(
    //             'data' => array(
    //                 'target' => 'plugin_bpmnio_bpmn'
    //             )
    //         )
    //     );
    //     $expect = <<<EOF
    //     TEST
    //     EOF;

    //     $action = new action_plugin_bpmnio_editor();
    //     $output = $action->handle_post($event);

    //     $this->assertEquals($expect, $output);
    // }
}
