<?php

/**
 * @group plugin_bpmnio
 * @group plugins
 */
class action_plugin_bpmnio_editor_test extends DokuWikiTest
{
    protected $pluginsEnabled = array('bpmnio');

    /**
     * Test that the editor plugin can be loaded
     */
    public function test_plugin_load()
    {
        $plugin = plugin_load('action', 'bpmnio_editor');
        $this->assertNotNull($plugin, 'Editor action plugin should be loadable');
    }

    /**
     * Test section edit button for BPMN target
     */
    public function test_section_edit_button_bpmn()
    {
        $plugin = plugin_load('action', 'bpmnio_editor');

        $data = ['target' => 'plugin_bpmnio_bpmn', 'name' => ''];
        $event = new \dokuwiki\Extension\Event('HTML_SECEDIT_BUTTON', $data);

        $plugin->sectionEditButton($event);

        $this->assertNotEmpty($data['name'], 'Button name should be set for BPMN target');
    }

    /**
     * Test section edit button for DMN target
     */
    public function test_section_edit_button_dmn()
    {
        $plugin = plugin_load('action', 'bpmnio_editor');

        $data = ['target' => 'plugin_bpmnio_dmn', 'name' => ''];
        $event = new \dokuwiki\Extension\Event('HTML_SECEDIT_BUTTON', $data);

        $plugin->sectionEditButton($event);

        $this->assertNotEmpty($data['name'], 'Button name should be set for DMN target');
    }

    /**
     * Test section edit button ignores non-bpmnio targets
     */
    public function test_section_edit_button_ignores_other()
    {
        $plugin = plugin_load('action', 'bpmnio_editor');

        $data = ['target' => 'section', 'name' => ''];
        $event = new \dokuwiki\Extension\Event('HTML_SECEDIT_BUTTON', $data);

        $plugin->sectionEditButton($event);

        $this->assertEmpty($data['name'], 'Button name should not be set for non-bpmnio targets');
    }

    /**
     * Test handlePost does nothing when plugin data not in POST
     */
    public function test_handle_post_noop_without_data()
    {
        $plugin = plugin_load('action', 'bpmnio_editor');

        global $TEXT;
        $TEXT = 'original';
        global $INPUT;

        $data = 'edit';
        $event = new \dokuwiki\Extension\Event('ACTION_ACT_PREPROCESS', $data);

        $plugin->handlePost($event);

        $this->assertEquals('original', $TEXT, '$TEXT should not change when plugin data is not posted');
    }
}
