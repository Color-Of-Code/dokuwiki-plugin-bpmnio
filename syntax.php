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
        $match = base64_encode($match);
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
	            $bpmnid = uniqid('__bpmnio_');
	            $renderer->doc .= '<div>';
	            $renderer->doc .= '<textarea class="bpmnio_data" id="'.$bpmnid.'" style="visibility:hidden;">';
        	    $renderer->doc .= trim($match);
	            $renderer->doc .= '</textarea>';
        	    //$renderer->doc .= '<div class="bpmnio_canvas" id="'.$bpmnid.'"></div>';
	            $renderer->doc .= '</div>';
                    break;
                case DOKU_LEXER_EXIT :       
                    break;
            }
            return true;
        }
        return false;
    }
}
