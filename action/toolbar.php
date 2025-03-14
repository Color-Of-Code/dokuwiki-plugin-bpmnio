<?php

/**
 * @license    See LICENSE file
 */

// See help: https://www.dokuwiki.org/devel:toolbar

class action_plugin_bpmnio_toolbar extends DokuWiki_Action_Plugin
{
    public function register(Doku_Event_Handler $controller)
    {
        $controller->register_hook('TOOLBAR_DEFINE', 'AFTER', $this, 'handleToolbar');
    }

    public function handleToolbar(Doku_Event $event)
    {
        $basedir = DOKU_BASE . 'lib/plugins/bpmnio/images/toolbar/';
        $event->data[] = array(
            'type' => 'picker',
            'title' => $this->getLang('picker'),
            'icon' => $basedir . 'picker.png',
            'list' => array(
                array(
                    'type' => 'format',
                    'class' => 'plugin-bpmnio icon-large',
                    'title' => $this->getLang('bpmn_add'),
                    'icon' => $basedir . 'bpmn_add.png',
                    'open' => file_get_contents(__DIR__ . '/../data/bpmn_open.text'),
                    'close' => file_get_contents(__DIR__ . '/../data/bpmn_close.text')
                ),
                array(
                    'type' => 'format',
                    'class' => 'plugin-bpmnio icon-large',
                    'title' => $this->getLang('dmn_add'),
                    'icon' => $basedir . 'dmn_add.png',
                    'open' => file_get_contents(__DIR__ . '/../data/dmn_open.text'),
                    'close' => file_get_contents(__DIR__ . '/../data/dmn_close.text')
                )
            ),
        );
    }
}
