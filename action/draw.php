<?php
/*
 * DokuWiki Plugin bpmnio (Action Component)
 *
 * @license MIT, see LICENSE
 * @author  Jaap de Haan <jaap.dehaan@color-of-code.de>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

class action_plugin_bpmnio_draw extends DokuWiki_Action_Plugin {

    public function register(Doku_Event_Handler $controller) {
       $controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'handle_tpl_metaheader_output');
    }



    /**
     * Add <script> blocks to the headers
     */
    public function handle_tpl_metaheader_output(Doku_Event &$event, $param) {
        $event->data["link"][] = array (
            "type" => "text/css",
            "rel" => "stylesheet",
            "href" => $this->to_abs_url("assets/diagram-js.css"),
        );
        $event->data["link"][] = array (
            "type" => "text/css",
            "rel" => "stylesheet",
            "href" => $this->to_abs_url("assets/bpmn-font/css/bpmn-embedded.css"),
        );

        // Load bpmn.io
        $event->data['script'][] = array(
			'type'    => 'text/javascript',
			'charset' => 'utf-8',
			'src'     => $this->to_abs_url("bpmn-viewer.min.js"),
			'_data'   => '',
		);
        /* If activated we can edit but we cannot save
        $event->data['script'][] = array(
			'type'    => 'text/javascript',
			'charset' => 'utf-8',
			'src'     => $this->to_abs_url("bpmn-modeler.min.js"),
			'_data'   => '',
		);
        */
        $event->data['script'][] = array(
			'type'    => 'text/javascript',
			'charset' => 'utf-8',
			'src'     => $this->to_abs_url("script.js"),
			'_data'   => '',
		);
    }
    
    private function to_abs_url($rel) {
        return DOKU_BASE."lib/plugins/bpmnio/".$rel;
    }
}
