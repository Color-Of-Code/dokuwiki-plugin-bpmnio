<?php

/**
 * @group plugin_bpmnio
 * @group plugins
 */
class syntax_plugin_bpmnio_test extends DokuWikiTest
{
    protected $pluginsEnabled = array('bpmnio');

    public function test_syntax_bpmn()
    {
        $info = array();
        $expected = <<<OUT
        <div class="plugin-bpmnio" id="__bpmn_js_1"><div class="bpmn_js_data">
            ClhNTC4uLgo=
        </div><div class="bpmn_js_canvas sectionedit1">
            <div class="bpmn_js_container"></div>
        </div><!-- EDIT{&quot;target&quot;:&quot;plugin_bpmnio_bpmn&quot;,&quot;secid&quot;:1,&quot;range&quot;:&quot;21-29&quot;} --></div>
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

    public function test_syntax_dmn()
    {
        $info = array();
        $expected = <<<OUT
        <div class="plugin-bpmnio" id="__dmn_js_1"><div class="dmn_js_data">
            ClhNTC4uLgo=
        </div><div class="dmn_js_canvas sectionedit1">
            <div class="dmn_js_container"></div>
        </div><!-- EDIT{&quot;target&quot;:&quot;plugin_bpmnio_dmn&quot;,&quot;secid&quot;:1,&quot;range&quot;:&quot;20-28&quot;} --></div>
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

    /**
     * Test that type defaults to bpmn when not specified
     */
    public function test_syntax_default_type()
    {
        $info = array();

        $input = <<<IN
        <bpmnio>
        XML...
        </bpmnio>
        IN;

        $instructions = p_get_instructions($input);
        $xhtml = p_render('xhtml', $instructions, $info);

        $this->assertStringContainsString('bpmn_js_data', $xhtml);
        $this->assertStringContainsString('bpmn_js_canvas', $xhtml);
        $this->assertStringContainsString('bpmn_js_container', $xhtml);
    }

    /**
     * Test empty content between tags
     */
    public function test_syntax_empty_content()
    {
        $info = array();

        $input = <<<IN
        <bpmnio type="bpmn">
        </bpmnio>
        IN;

        $instructions = p_get_instructions($input);
        $xhtml = p_render('xhtml', $instructions, $info);

        // Should still produce the structure, with base64 of whitespace/empty
        $this->assertStringContainsString('plugin-bpmnio', $xhtml);
        $this->assertStringContainsString('bpmn_js_data', $xhtml);
    }

    /**
     * Test that multiline XML content is properly base64-encoded
     */
    public function test_syntax_multiline_content()
    {
        $info = array();

        $input = <<<IN
        <bpmnio type="bpmn">
        <?xml version="1.0"?>
        <definitions xmlns="http://www.omg.org/spec/BPMN/20100524/MODEL">
        </definitions>
        </bpmnio>
        IN;

        $instructions = p_get_instructions($input);
        $xhtml = p_render('xhtml', $instructions, $info);

        $this->assertStringContainsString('bpmn_js_data', $xhtml);
        // Verify the data section contains valid base64
        preg_match('/<div class="bpmn_js_data">\s*(.*?)\s*<\/div>/s', $xhtml, $matches);
        $this->assertNotEmpty($matches[1]);
        $decoded = base64_decode(trim($matches[1]), true);
        $this->assertNotFalse($decoded, 'Content should be valid base64');
        $this->assertStringContainsString('definitions', $decoded);
    }

    /**
     * Test that the plugin produces section edit markers for inline content
     */
    public function test_syntax_section_edit_bpmn()
    {
        $info = array();

        $input = <<<IN
        <bpmnio type="bpmn">
        Content
        </bpmnio>
        IN;

        $instructions = p_get_instructions($input);
        $xhtml = p_render('xhtml', $instructions, $info);

        $this->assertStringContainsString('sectionedit', $xhtml);
        $this->assertStringContainsString('plugin_bpmnio_bpmn', $xhtml);
    }

    /**
     * Test that the plugin produces section edit markers for DMN inline content
     */
    public function test_syntax_section_edit_dmn()
    {
        $info = array();

        $input = <<<IN
        <bpmnio type="dmn">
        Content
        </bpmnio>
        IN;

        $instructions = p_get_instructions($input);
        $xhtml = p_render('xhtml', $instructions, $info);

        $this->assertStringContainsString('sectionedit', $xhtml);
        $this->assertStringContainsString('plugin_bpmnio_dmn', $xhtml);
    }

    /**
     * Test that unrecognized text outside <bpmnio> is not affected
     */
    public function test_syntax_no_interference()
    {
        $info = array();

        $input = <<<IN
        Hello World
        <bpmnio type="bpmn">
        XML...
        </bpmnio>
        Goodbye World
        IN;

        $instructions = p_get_instructions($input);
        $xhtml = p_render('xhtml', $instructions, $info);

        $this->assertStringContainsString('Hello World', $xhtml);
        $this->assertStringContainsString('Goodbye World', $xhtml);
        $this->assertStringContainsString('plugin-bpmnio', $xhtml);
    }

    public function test_syntax_zoom_attribute()
    {
        $info = array();

        $input = <<<IN
        <bpmnio type="bpmn" zoom="0.5">
        XML...
        </bpmnio>
        IN;

        $instructions = p_get_instructions($input);
        $xhtml = p_render('xhtml', $instructions, $info);

        $this->assertStringContainsString('data-zoom="0.5"', $xhtml);
    }

    public function test_syntax_ignores_invalid_zoom_attribute()
    {
        $info = array();

        $input = <<<IN
        <bpmnio type="bpmn" zoom="0">
        XML...
        </bpmnio>
        IN;

        $instructions = p_get_instructions($input);
        $xhtml = p_render('xhtml', $instructions, $info);

        $this->assertStringNotContainsString('data-zoom=', $xhtml);
    }

    /**
     * Test the handle method directly for ENTER state
     */
    public function test_handle_enter_state()
    {
        $plugin = plugin_load('syntax', 'bpmnio_bpmnio');
        $this->assertNotNull($plugin, 'Plugin should be loadable');

        $handler = new Doku_Handler();
        $result = $plugin->handle('<bpmnio type="bpmn">', DOKU_LEXER_ENTER, 0, $handler);

        $this->assertEquals(DOKU_LEXER_ENTER, $result[0]);
        $this->assertEquals('bpmn', $result[1]);
    }

    /**
     * Test the handle method directly for EXIT state
     */
    public function test_handle_exit_state()
    {
        $plugin = plugin_load('syntax', 'bpmnio_bpmnio');
        $handler = new Doku_Handler();
        $result = $plugin->handle('</bpmnio>', DOKU_LEXER_EXIT, 0, $handler);

        $this->assertEquals(DOKU_LEXER_EXIT, $result[0]);
    }

    /**
     * Test that the plugin is correctly registered
     */
    public function test_plugin_registration()
    {
        $plugin = plugin_load('syntax', 'bpmnio_bpmnio');
        $this->assertNotNull($plugin, 'Plugin should be loadable');
        $this->assertEquals('block', $plugin->getPType());
        $this->assertEquals('protected', $plugin->getType());
        $this->assertEquals(0, $plugin->getSort());
    }
}
