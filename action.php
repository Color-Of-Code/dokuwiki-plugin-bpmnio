<?php

/**
 * @license    See LICENSE file
 * @author     Jaap de Haan <jaap.dehaan@color-of-code.de>
 */

// must be run within DokuWiki
if (!defined('DOKU_INC')) {
    die();
}

if (!defined('DOKU_PLUGIN')) {
    define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');
}

require_once DOKU_PLUGIN . 'action.php';

// See help: https://www.dokuwiki.org/devel:toolbar
// See help: https://www.dokuwiki.org/devel:section_editor

class action_plugin_bpmnio extends DokuWiki_Action_Plugin
{

    public function register(Doku_Event_Handler $controller)
    {
        $controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'handle_tpl_metaheader_output');
        $controller->register_hook('TOOLBAR_DEFINE', 'AFTER', $this, 'handle_toolbar', array());
        $controller->register_hook('HTML_SECEDIT_BUTTON', 'BEFORE', $this, 'handle_section_edit_button');
    }

    /**
     * Add <script> blocks to the meta headers
     */
    public function handle_tpl_metaheader_output(Doku_Event &$event, $param)
    {

        $event->data['link'][] = $this->create_css("assets/diagram-js.css");
        $event->data['link'][] = $this->create_css("assets/bpmn-font/css/bpmn.css");
        $event->data['link'][] = $this->create_css("assets/bpmn-font/css/bpmn-codes.css");
        $event->data['link'][] = $this->create_css("assets/bpmn-font/css/bpmn-embedded.css");

        // Load bpmn.io
        $event->data['script'][] = $this->create_js("bpmn-viewer.production.min.js");

        // If activated we can edit but we cannot save
        // $event->data['script'][] = $this->create_js("bpmn-modeler.production.min.js");
        $event->data['script'][] = $this->create_js("script.js");
    }

    private function create_css($rel)
    {
        return array(
            'type' => 'text/css',
            'rel' => 'stylesheet',
            'href' => $this->to_abs_url($rel),
        );
    }

    private function create_js($rel)
    {
        return array(
            'type' => 'text/javascript',
            'charset' => 'utf-8',
            'src' => $this->to_abs_url($rel),
            '_data' => '',
        );
    }

    private function to_abs_url($rel)
    {
        return DOKU_BASE . "lib/plugins/bpmnio/" . $rel;
    }

    public function handle_toolbar(Doku_Event $event, $param)
    {
        $event->data[] = array(
            'type' => 'picker',
            'title' => $this->getLang('picker'),
            'icon' => '../../plugins/bpmnio/images/toolbar/picker.png',
            'list' => array(
                array(
                    'type' => 'format',
                    'title' => $this->getLang('add'),
                    'icon' => '../../plugins/bpmnio/images/toolbar/bpmn_add.png',
                    'open' => '<bpmnio zoom=1.0>\n' . $this->_get_open_text(),
                    'close' => $this->_get_close_text() . '\n</bpmnio>\n',
                ),
            ),
        );
    }

    public function handle_section_edit_button(Doku_Event $event, $param)
    {
        if ($event->data['target'] !== 'plugin_bpmnio') {
            return;
        }
        $event->data['name'] = $this->getLang('section_name');
    }

    private function _get_open_text()
    {
        return '<?xml version="1.0" encoding="UTF-8"?>
<definitions xmlns="http://www.omg.org/spec/BPMN/20100524/MODEL" xmlns:bpmndi="http://www.omg.org/spec/BPMN/20100524/DI" xmlns:omgdi="http://www.omg.org/spec/DD/20100524/DI" xmlns:omgdc="http://www.omg.org/spec/DD/20100524/DC" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" id="sid-38422fae-e03e-43a3-bef4-bd33b32041b2" targetNamespace="http://bpmn.io/bpmn" exporter="http://bpmn.io" exporterVersion="0.10.1">
  <collaboration id="Collaboration_1oh70al">
    <participant id="Participant_1r8g02m" name="';
    }

    private function _get_close_text()
    {

        return '" processRef="Process_1" />
  </collaboration>
  <process id="Process_1" isExecutable="false">
    <startEvent id="StartEvent_1" name="Start">
      <outgoing>SequenceFlow_1</outgoing>
    </startEvent>
    <task id="Task_1" name="Do Something">
      <incoming>SequenceFlow_1</incoming>
      <incoming>SequenceFlow_121ul2c</incoming>
      <incoming>SequenceFlow_0nuwads</incoming>
      <outgoing>SequenceFlow_2</outgoing>
    </task>
    <exclusiveGateway id="ExclusiveGateway_1" name="Result OK" gatewayDirection="Diverging">
      <incoming>SequenceFlow_2</incoming>
      <outgoing>SequenceFlow_0snv4kp</outgoing>
      <outgoing>SequenceFlow_0nuwads</outgoing>
    </exclusiveGateway>
    <task id="Task_17knw8l" name="Monitor">
      <outgoing>SequenceFlow_121ul2c</outgoing>
    </task>
    <endEvent id="EndEvent_0oj7l6x" name="End">
      <incoming>SequenceFlow_0snv4kp</incoming>
    </endEvent>
    <sequenceFlow id="SequenceFlow_1" name="" sourceRef="StartEvent_1" targetRef="Task_1" />
    <sequenceFlow id="SequenceFlow_121ul2c" sourceRef="Task_17knw8l" targetRef="Task_1" />
    <sequenceFlow id="SequenceFlow_0nuwads" name="No" sourceRef="ExclusiveGateway_1" targetRef="Task_1" />
    <sequenceFlow id="SequenceFlow_2" sourceRef="Task_1" targetRef="ExclusiveGateway_1" />
    <sequenceFlow id="SequenceFlow_0snv4kp" name="Yes" sourceRef="ExclusiveGateway_1" targetRef="EndEvent_0oj7l6x" />
  </process>
  <bpmndi:BPMNDiagram id="BpmnDiagram_1">
    <bpmndi:BPMNPlane id="BpmnPlane_1" bpmnElement="Collaboration_1oh70al">
      <bpmndi:BPMNShape id="Participant_1r8g02m_di" bpmnElement="Participant_1r8g02m">
        <omgdc:Bounds x="104" y="78" width="668" height="297" />
      </bpmndi:BPMNShape>
      <bpmndi:BPMNShape id="StartEvent_1_gui" bpmnElement="StartEvent_1">
        <omgdc:Bounds x="242" y="187" width="30" height="30" />
        <bpmndi:BPMNLabel>
          <omgdc:Bounds x="212" y="219" width="90" height="22" />
        </bpmndi:BPMNLabel>
      </bpmndi:BPMNShape>
      <bpmndi:BPMNShape id="Task_1_gui" bpmnElement="Task_1">
        <omgdc:Bounds x="340" y="162" width="100" height="80" />
        <bpmndi:BPMNLabel>
          <omgdc:Bounds x="118.85714721679688" y="47" width="82.28570556640625" height="12" />
        </bpmndi:BPMNLabel>
      </bpmndi:BPMNShape>
      <bpmndi:BPMNShape id="ExclusiveGateway_1_gui" bpmnElement="ExclusiveGateway_1" isMarkerVisible="true">
        <omgdc:Bounds x="508" y="182" width="40" height="40" />
        <bpmndi:BPMNLabel>
          <omgdc:Bounds x="483" y="234" width="90" height="24" />
        </bpmndi:BPMNLabel>
      </bpmndi:BPMNShape>
      <bpmndi:BPMNShape id="Task_17knw8l_di" bpmnElement="Task_17knw8l">
        <omgdc:Bounds x="340" y="275" width="100" height="80" />
      </bpmndi:BPMNShape>
      <bpmndi:BPMNShape id="EndEvent_0oj7l6x_di" bpmnElement="EndEvent_0oj7l6x">
        <omgdc:Bounds x="648" y="184" width="36" height="36" />
        <bpmndi:BPMNLabel>
          <omgdc:Bounds x="621" y="220" width="90" height="20" />
        </bpmndi:BPMNLabel>
      </bpmndi:BPMNShape>
      <bpmndi:BPMNEdge id="SequenceFlow_1_gui" bpmnElement="SequenceFlow_1">
        <omgdi:waypoint xsi:type="omgdc:Point" x="272" y="202" />
        <omgdi:waypoint xsi:type="omgdc:Point" x="340" y="202" />
        <bpmndi:BPMNLabel>
          <omgdc:Bounds x="225" y="140" width="90" height="20" />
        </bpmndi:BPMNLabel>
      </bpmndi:BPMNEdge>
      <bpmndi:BPMNEdge id="SequenceFlow_121ul2c_di" bpmnElement="SequenceFlow_121ul2c">
        <omgdi:waypoint xsi:type="omgdc:Point" x="390" y="275" />
        <omgdi:waypoint xsi:type="omgdc:Point" x="390" y="242" />
        <bpmndi:BPMNLabel>
          <omgdc:Bounds x="358" y="273" width="90" height="20" />
        </bpmndi:BPMNLabel>
      </bpmndi:BPMNEdge>
      <bpmndi:BPMNEdge id="SequenceFlow_0nuwads_di" bpmnElement="SequenceFlow_0nuwads">
        <omgdi:waypoint xsi:type="omgdc:Point" x="528" y="182" />
        <omgdi:waypoint xsi:type="omgdc:Point" x="528" y="110" />
        <omgdi:waypoint xsi:type="omgdc:Point" x="390" y="110" />
        <omgdi:waypoint xsi:type="omgdc:Point" x="390" y="162" />
        <bpmndi:BPMNLabel>
          <omgdc:Bounds x="495" y="140" width="90" height="20" />
        </bpmndi:BPMNLabel>
      </bpmndi:BPMNEdge>
      <bpmndi:BPMNEdge id="SequenceFlow_2_di" bpmnElement="SequenceFlow_2">
        <omgdi:waypoint xsi:type="omgdc:Point" x="440" y="202" />
        <omgdi:waypoint xsi:type="omgdc:Point" x="508" y="202" />
        <bpmndi:BPMNLabel>
          <omgdc:Bounds x="433" y="192" width="90" height="20" />
        </bpmndi:BPMNLabel>
      </bpmndi:BPMNEdge>
      <bpmndi:BPMNEdge id="SequenceFlow_0snv4kp_di" bpmnElement="SequenceFlow_0snv4kp">
        <omgdi:waypoint xsi:type="omgdc:Point" x="548" y="202" />
        <omgdi:waypoint xsi:type="omgdc:Point" x="648" y="202" />
        <bpmndi:BPMNLabel>
          <omgdc:Bounds x="550" y="183" width="90" height="20" />
        </bpmndi:BPMNLabel>
      </bpmndi:BPMNEdge>
    </bpmndi:BPMNPlane>
  </bpmndi:BPMNDiagram>
</definitions>';
    }
}
