<?php

/**
 * @license    See LICENSE file
 */

// See help:
// * https://www.dokuwiki.org/devel:section_editor
// * https://www.dokuwiki.org/devel:releases:refactor2021

use dokuwiki\Form\Form;
use dokuwiki\Utf8;

class action_plugin_bpmnio_editor extends DokuWiki_Action_Plugin
{
    public function register(Doku_Event_Handler $controller)
    {
        $controller->register_hook('HTML_SECEDIT_BUTTON', 'BEFORE', $this, 'secedit_button');
        $controller->register_hook('EDIT_FORM_ADDTEXTAREA', 'BEFORE', $this, 'handle_form');
        $controller->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE', $this, 'handle_post');
    }

    function secedit_button(Doku_Event $event)
    {
        if ($this->shallIgnore($event)) return;

        $event->data['name'] = $this->getLang('edit_diagram');
    }

    function handle_form(Doku_Event $event)
    {
        if ($this->shallIgnore($event)) return;

        global $TEXT;
        global $RANGE;
        global $INPUT;

        if (!$RANGE) {
            // section editing failed, use default editor instead
            $event->data['target'] = 'section';
            return;
        }

        $event->stopPropagation();
        $event->preventDefault();

        /** @var Doku_Form $form */
        $form = &$event->data['form'];
        $data = base64_encode($TEXT);

        $type = 'bpmn';
        if ($event->data['target'] === 'plugin_bpmnio_dmn')
            $type = 'dmn';

        $form->setHiddenField('plugin_bpmnio_data', $data);
        $form->addHTML(<<<HTML
            <div class="plugin-bpmnio" id="plugin_bpmnio__{$type}_editor">
                <div class="{$type}_js_data">{$data}</div>
                <div class="{$type}_js_canvas">
                    <div class="{$type}_js_container"></div>
                </div>
                </div>
            </div>
            HTML);

        // used during previews
        $form->setHiddenField('target', "plugin_bpmnio_{$type}");
        $form->setHiddenField('range', $RANGE);
    }

    function handle_post(Doku_Event $event)
    {
        global $TEXT;
        global $INPUT;

        if (!$INPUT->post->has('plugin_bpmnio_data')) return;

        $TEXT = base64_decode($INPUT->post->str('plugin_bpmnio_data'));
    }

    private function shallIgnore(Doku_Event $event)
    {
        if ($event->data['target'] === 'plugin_bpmnio_bpmn')
            return false;
        if ($event->data['target'] === 'plugin_bpmnio_dmn')
            return false;
        return true;
    }
}
