<?php
/*
 * DokuWiki Plugin bpmnio (Action Component: Viewer)
 *
 * @license MIT, see LICENSE
 * @author  Jaap de Haan <jaap.dehaan@color-of-code.de>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

class action_plugin_bpmnio_viewer extends DokuWiki_Action_Plugin {

    public function register(Doku_Event_Handler $controller) {
       $controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'handle_tpl_metaheader_output');
    }

    /**
     * Add <script> blocks to the meta headers
     */
    public function handle_tpl_metaheader_output(Doku_Event &$event, $param) {

		$event->data['link'][] = $this->create_css("assets/diagram-js.css");
        $event->data['link'][] = $this->create_css("assets/bpmn-font/css/bpmn-embedded.css");

        // Load bpmn.io
        $event->data['script'][] = $this->create_js("bpmn-viewer.min.js");
        
		// If activated we can edit but we cannot save
        // $event->data['script'][] = $this->create_js("bpmn-modeler.min.js");
		
        $event->data['script'][] = $this->create_js("script.js");
    }
    
    private function create_css($rel) {
		return array(
			'type'    => 'text/css',
            'rel'     => 'stylesheet',
			'href'    => $this->to_abs_url($rel),
		);
    }

    private function create_js($rel) {
		return array(
			'type'    => 'text/javascript',
			'charset' => 'utf-8',
			'src'     => $this->to_abs_url($rel),
			'_data'   => '',
		);
    }

    private function to_abs_url($rel) {
        return DOKU_BASE."lib/plugins/bpmnio/".$rel;
    }
}
