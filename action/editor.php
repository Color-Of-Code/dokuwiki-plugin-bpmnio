<?php
/*
 * DokuWiki Plugin bpmnio (Action Component: Editor)
 *
 * @license MIT, see LICENSE
 * @author  Andreas Boehler <dev (AT) aboehler.at>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

class action_plugin_bpmnio_editor extends DokuWiki_Action_Plugin {

    private $emptyDiagramXML = '<?xml version="1.0" encoding="UTF-8"?>
<bpmn2:definitions xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:bpmn2="http://www.omg.org/spec/BPMN/20100524/MODEL" xmlns:bpmndi="http://www.omg.org/spec/BPMN/20100524/DI" xmlns:dc="http://www.omg.org/spec/DD/20100524/DC" xmlns:di="http://www.omg.org/spec/DD/20100524/DI" xsi:schemaLocation="http://www.omg.org/spec/BPMN/20100524/MODEL BPMN20.xsd" id="sample-diagram" targetNamespace="http://bpmn.io/schema/bpmn">
  <bpmn2:process id="Process_1" isExecutable="false">
    <bpmn2:startEvent id="StartEvent_1"/>
  </bpmn2:process>
  <bpmndi:BPMNDiagram id="BPMNDiagram_1">
    <bpmndi:BPMNPlane id="BPMNPlane_1" bpmnElement="Process_1">
      <bpmndi:BPMNShape id="_BPMNShape_StartEvent_2" bpmnElement="StartEvent_1">
        <dc:Bounds height="36.0" width="36.0" x="412.0" y="240.0"/>
      </bpmndi:BPMNShape>
    </bpmndi:BPMNPlane>
  </bpmndi:BPMNDiagram>
</bpmn2:definitions>';

    public function register(Doku_Event_Handler $controller) {
       $controller->register_hook('HTML_SECEDIT_BUTTON', 'BEFORE', $this, 'secedit_button');
       // register our editor
       $controller->register_hook('HTML_EDIT_FORMSELECTION', 'BEFORE', $this, 'editform');
       $controller->register_hook('PLUGIN_EDITTABLE_PREPROCESS_EDITOR', 'BEFORE', $this, 'handle_bpmn_post');
    }
    
    function secedit_button(Doku_Event $event)
    {
        if($event->data['target'] !== 'plugin_bpmnio')
            return;

        $event->data['name'] = $this->getLang('secedit_name');
    }
    
    function editform(Doku_Event $event)
    {
        global $TEXT;
        if($event->data['target'] !== 'plugin_bpmnio')
            return;
        $event->stopPropagation();
        $event->preventDefault();
        
        $form =& $event->data['form'];
        $xml = $TEXT;
        if(trim($xml) === '')
        {
            $xml = $this->emptyDiagramXML;
        }
        $form->addElement('<div class="plugin_bpmnio_editor_outer"><div id="plugin_bpmnio_editor" style="display:none">'.base64_encode($xml).'</div></div>');
        $form->addHidden('plugin_bpmnio_data', base64_encode($xml));
    }
    
    function handle_bpmn_post(Doku_Event $event)
    {
        global $TEXT;
        if(!isset($_POST['plugin_bpmnio_data']))
            return;
            
        $TEXT = base64_decode($_POST['plugin_bpmnio_data']);
    }
    
}