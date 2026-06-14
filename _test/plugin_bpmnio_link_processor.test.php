<?php

/**
 * @group plugin_bpmnio
 * @group plugins
 */
class plugin_bpmnio_link_processor_test extends DokuWikiTest
{
    protected $pluginsEnabled = array('bpmnio');

    private function loadLinkProcessor(): void
    {
        require_once __DIR__ . '/../inc/link_processor.php';
    }

    /**
     * XML with a declaration at position 0 (clean input as provided after source trim)
     * must be successfully parsed so that wikilinks are resolved.
     */
    public function test_build_payload_xml_declaration_at_start(): void
    {
        $this->loadLinkProcessor();

        io_mkdir_p(dirname(wikiFN('test:target')));
        io_saveFile(wikiFN('test:target'), 'Target page');

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<definitions xmlns="http://www.omg.org/spec/BPMN/20100524/MODEL">'
            . '<process id="Process_1">'
            . '<task id="Task_1" name="[[test:target|Go there]]" />'
            . '</process>'
            . '</definitions>';

        $result = plugin_bpmnio_link_processor::buildPayload($xml);

        // XML was parsed successfully: wikilink markup is stripped from name
        $this->assertStringNotContainsString('[[', $result['xml']);
        $this->assertStringContainsString('name="Go there"', $result['xml']);
        // XML declaration is preserved in output
        $this->assertStringContainsString('<?xml', $result['xml']);
        // Link map contains the resolved entry
        $this->assertArrayHasKey('Task_1', $result['links']);
        $this->assertEquals('test:target', $result['links']['Task_1']['target']);
    }

    /**
     * Empty input returns empty xml and no links without crashing.
     */
    public function test_build_payload_empty_input(): void
    {
        $this->loadLinkProcessor();

        $result = plugin_bpmnio_link_processor::buildPayload('');

        $this->assertSame('', $result['xml']);
        $this->assertSame([], $result['links']);
    }

    /**
     * XML without wikilinks returns the processed XML and an empty link map.
     */
    public function test_build_payload_no_links(): void
    {
        $this->loadLinkProcessor();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<definitions xmlns="http://www.omg.org/spec/BPMN/20100524/MODEL">'
            . '<process id="Process_1">'
            . '<task id="Task_1" name="Plain Task" />'
            . '</process>'
            . '</definitions>';

        $result = plugin_bpmnio_link_processor::buildPayload($xml);

        $this->assertStringContainsString('Plain Task', $result['xml']);
        $this->assertSame([], $result['links']);
    }

    /**
     * Malformed XML returns the original string unchanged and no links.
     */
    public function test_build_payload_malformed_xml(): void
    {
        $this->loadLinkProcessor();

        $xml = '<unclosed>';

        $result = plugin_bpmnio_link_processor::buildPayload($xml);

        $this->assertSame($xml, $result['xml']);
        $this->assertSame([], $result['links']);
    }
}
