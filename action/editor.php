<?php
/**
 * @license    See LICENSE file
 * @author     Jaap de Haan <jaap.dehaan@color-of-code.de>
 */

// See help:
// * https://www.dokuwiki.org/devel:section_editor
// * https://www.dokuwiki.org/devel:releases:refactor2021

class action_plugin_bpmnio_editor extends DokuWiki_Action_Plugin
{
    public function register(Doku_Event_Handler $controller)
    {
        $controller->register_hook('HTML_SECEDIT_BUTTON', 'BEFORE', $this, 'secedit_button');
    }

    function secedit_button(Doku_Event $event)
    {
        if ($this->_shall_ignore($event)) return;

        $event->data['name'] = $this->getLang('edit_diagram');
    }

    private function _shall_ignore(Doku_Event $event)
    {
        if ($event->data['target'] !== 'plugin_bpmnio')
            return true;
        return false;
    }
}
