<?php

/**
 * @group plugin_bpmnio
 * @group plugins
 */
class action_plugin_bpmnio_toolbar_test extends DokuWikiTest
{
    protected $pluginsEnabled = array('bpmnio');

    /**
     * Test that the toolbar plugin can be loaded
     */
    public function test_plugin_load()
    {
        $plugin = plugin_load('action', 'bpmnio_toolbar');
        $this->assertNotNull($plugin, 'Toolbar action plugin should be loadable');
    }

    /**
     * Test that the toolbar handler adds the picker to event data
     */
    public function test_toolbar_handler_adds_picker()
    {
        $plugin = plugin_load('action', 'bpmnio_toolbar');

        $data = [];
        $event = new \dokuwiki\Extension\Event('TOOLBAR_DEFINE', $data);

        $plugin->handleToolbar($event);

        $this->assertCount(1, $data, 'Should add one toolbar entry');
        $picker = $data[0];
        $this->assertEquals('picker', $picker['type']);
        $this->assertArrayHasKey('list', $picker);
        $this->assertCount(2, $picker['list'], 'Picker should have BPMN and DMN entries');

        // Check BPMN entry
        $bpmn = $picker['list'][0];
        $this->assertEquals('format', $bpmn['type']);
        $this->assertNotEmpty($bpmn['open'], 'BPMN open template should not be empty');
        $this->assertNotEmpty($bpmn['close'], 'BPMN close template should not be empty');
        $this->assertStringContainsString('bpmnio', $bpmn['open']);

        // Check DMN entry
        $dmn = $picker['list'][1];
        $this->assertEquals('format', $dmn['type']);
        $this->assertNotEmpty($dmn['open'], 'DMN open template should not be empty');
        $this->assertNotEmpty($dmn['close'], 'DMN close template should not be empty');
        $this->assertStringContainsString('bpmnio', $dmn['open']);
    }

    /**
     * Test that toolbar icons reference the correct directory
     */
    public function test_toolbar_icon_paths()
    {
        $plugin = plugin_load('action', 'bpmnio_toolbar');

        $data = [];
        $event = new \dokuwiki\Extension\Event('TOOLBAR_DEFINE', $data);
        $plugin->handleToolbar($event);

        $picker = $data[0];
        $this->assertStringContainsString('bpmnio/images/toolbar/', $picker['icon']);
        $this->assertStringContainsString('bpmnio/images/toolbar/', $picker['list'][0]['icon']);
        $this->assertStringContainsString('bpmnio/images/toolbar/', $picker['list'][1]['icon']);
    }

    /**
     * Test that BPMN template contains valid XML structure
     */
    public function test_bpmn_template_content()
    {
        $plugin = plugin_load('action', 'bpmnio_toolbar');

        $data = [];
        $event = new \dokuwiki\Extension\Event('TOOLBAR_DEFINE', $data);
        $plugin->handleToolbar($event);

        $bpmn_open = $data[0]['list'][0]['open'];
        $bpmn_close = $data[0]['list'][0]['close'];

        $this->assertStringContainsString('<bpmnio type="bpmn">', $bpmn_open);
        $this->assertStringContainsString('</bpmnio>', $bpmn_close);
    }

    /**
     * Test that DMN template contains valid XML structure
     */
    public function test_dmn_template_content()
    {
        $plugin = plugin_load('action', 'bpmnio_toolbar');

        $data = [];
        $event = new \dokuwiki\Extension\Event('TOOLBAR_DEFINE', $data);
        $plugin->handleToolbar($event);

        $dmn_open = $data[0]['list'][1]['open'];
        $dmn_close = $data[0]['list'][1]['close'];

        $this->assertStringContainsString('<bpmnio type="dmn">', $dmn_open);
        $this->assertStringContainsString('</bpmnio>', $dmn_close);
    }
}
