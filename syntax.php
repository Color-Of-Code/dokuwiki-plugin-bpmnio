<?php
/**
 * @license    See LICENSE file
 * @author     Jaap de Haan <jaap.dehaan@color-of-code.de>
*/
     
// must be run within DokuWiki
if(!defined('DOKU_INC')) die();
     
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once DOKU_PLUGIN.'syntax.php';

// See help: https://www.dokuwiki.org/devel:syntax_plugins

class syntax_plugin_bpmnio extends DokuWiki_Syntax_Plugin {

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

    public function getPType() {
        return 'block';
    }

    public function getType() {
        return 'protected';
    }

    public function getSort() {
        return 0;
    }
     
    public function connectTo($mode) {
        $this->Lexer->addEntryPattern('<bpmnio.*?>(?=.*?</bpmnio>)', $mode, 'plugin_bpmnio');
    }
    
    public function postConnect() {
        $this->Lexer->addExitPattern('</bpmnio>', 'plugin_bpmnio');
    }
    
    public function handle($match, $state, $pos, Doku_Handler $handler) {
        
        return array($match, $state, $pos);
    }
     
    public function render($mode, Doku_Renderer $renderer, $data) {
        // $data is returned by handle()
        if ($mode == 'xhtml'  || $mode == 'odt') {
            list($match, $state, $pos) = $data;
            
            switch ($state) {
                case DOKU_LEXER_ENTER :      
                    break;
 
                case DOKU_LEXER_UNMATCHED :
                    $classes = 'bpmnio_container';
    	            $bpmnid = uniqid('__bpmnio_');
                    if(method_exists($renderer, 'startSectionEdit'))
                        $classes.= ' '.$renderer->startSectionEdit($pos, 'plugin_bpmnio');  
    	            $renderer->doc .= '<div class="'.$classes.'">';
    	            $renderer->doc .= '<textarea class="bpmnio_data" id="'.$bpmnid.'" style="visibility:hidden;">';
                    if(trim($match) === '')
                    {
                        $xml = trim(base64_encode($this->emptyDiagramXML));
                    }
                    else
                    {
                        $xml = trim(base64_encode($match));
                    }
            	    $renderer->doc .= $xml;
    	            $renderer->doc .= '</textarea>';
    	            $renderer->doc .= '</div>';
                    if(method_exists($renderer, 'finishSectionEdit'))
                        $renderer->finishSectionEdit(strlen($match) + $pos);
                    break;
                case DOKU_LEXER_EXIT :       
                    break;
            }
            return true;
        }
        return false;
    }
}
