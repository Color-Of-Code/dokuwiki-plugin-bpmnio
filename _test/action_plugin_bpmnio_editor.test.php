<?php

/**
 * @group plugin_bpmnio
 * @group plugins
 */
class action_plugin_bpmnio_editor_test extends DokuWikiTest
{
    protected $pluginsEnabled = array('bpmnio');

    public function tearDown(): void
    {
        parent::tearDown();

        global $TEXT;
        global $RANGE;
        $TEXT = null;
        $RANGE = null;
    }

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

    public function test_handle_form_uses_render_payload_but_preserves_original_text()
    {
        $plugin = plugin_load('action', 'bpmnio_editor');

        global $TEXT;
        global $RANGE;
        $TEXT = '<?xml version="1.0"?><definitions xmlns="http://www.omg.org/spec/BPMN/20100524/MODEL"><process id="Process_1"><task id="Task_1" name="[[:docs:start|Read docs]]" /></process></definitions>';
        $RANGE = '1-10';

        $form = new class {
            /** @var array<string, string> */
            public array $hiddenFields = [];

            public string $html = '';

            public function setHiddenField(string $name, string $value): void
            {
                $this->hiddenFields[$name] = $value;
            }

            public function addHTML(string $html): void
            {
                $this->html .= $html;
            }
        };
        $data = ['target' => 'plugin_bpmnio_bpmn', 'form' => $form];
        $event = new \dokuwiki\Extension\Event('EDIT_FORM_ADDTEXTAREA', $data);

        $plugin->handleForm($event);

        $this->assertArrayHasKey('plugin_bpmnio_data', $form->hiddenFields);
        $this->assertArrayHasKey('plugin_bpmnio_links', $form->hiddenFields);
        $this->assertEquals(base64_encode($TEXT), $form->hiddenFields['plugin_bpmnio_data']);
        $this->assertStringContainsString('id="plugin_bpmnio__bpmn_editor"', $form->html);
        $this->assertStringContainsString('<div class="bpmn_js_canvas">', $form->html);
        $this->assertSame(5, substr_count($form->html, '<div'));
        $this->assertSame(5, substr_count($form->html, '</div>'));

        preg_match('/<div class="bpmn_js_data">(.*?)<\/div>/s', $form->html, $xmlMatch);
        $this->assertNotEmpty($xmlMatch[1]);
        $decodedXml = base64_decode(trim($xmlMatch[1]), true);
        $this->assertNotFalse($decodedXml);
        $this->assertStringContainsString('name="Read docs"', $decodedXml);

        preg_match('/<div class="bpmn_js_links">(.*?)<\/div>/s', $form->html, $linkMatch);
        $this->assertNotEmpty($linkMatch[1]);
        $decodedLinks = base64_decode(trim($linkMatch[1]), true);
        $links = json_decode($decodedLinks, true);
        $this->assertArrayHasKey('Task_1', $links);
        $this->assertEquals('docs:start', $links['Task_1']['target']);
    }
}
