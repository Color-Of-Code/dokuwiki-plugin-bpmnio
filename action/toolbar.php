<?php

use dokuwiki\Extension\ActionPlugin;
use dokuwiki\Extension\EventHandler;
use dokuwiki\Extension\Event;

/**
 * @license    See LICENSE file
 */
// See help: https://www.dokuwiki.org/devel:toolbar
class action_plugin_bpmnio_toolbar extends ActionPlugin
{
    public function register(EventHandler $controller)
    {
        $controller->register_hook('TOOLBAR_DEFINE', 'AFTER', $this, 'handleToolbar');
    }

    public function handleToolbar(Event $event)
    {
        $basedir = DOKU_BASE . 'lib/plugins/bpmnio/images/toolbar/';
        $event->data[] = [
            'type' => 'picker',
            'title' => $this->getLang('picker'),
            'icon' => $basedir . 'picker.png',
            'list' => [
                [
                    'type' => 'format',
                    'class' => 'plugin-bpmnio icon-large',
                    'title' => $this->getLang('bpmn_add'),
                    'icon' => $basedir . 'bpmn_add.png',
                    'open' => $this->getFileContent('bpmn_open'),
                    'close' => $this->getFileContent('bpmn_close')
                ],
                [
                    'type' => 'format',
                    'class' => 'plugin-bpmnio icon-large',
                    'title' => $this->getLang('dmn_add'),
                    'icon' => $basedir . 'dmn_add.png',
                    'open' => $this->getFileContent('dmn_open'),
                    'close' => $this->getFileContent('dmn_close')
                ]
            ]
        ];
    }

    private function getFileContent($file)
    {
        return trim(file_get_contents(__DIR__ . '/../data/' . $file . '.text'));
    }
}
