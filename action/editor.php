<?php

/**
 * @license    See LICENSE file
 * @author     Jaap de Haan <jaap.dehaan@color-of-code.de>
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
        $controller->register_hook('HTML_EDIT_FORMSELECTION', 'BEFORE', $this, 'handle_form');

        $controller->register_hook('PLUGIN_EDITTABLE_PREPROCESS_EDITOR', 'BEFORE', $this, 'handle_post');
    }

    function secedit_button(Doku_Event $event)
    {
        if ($this->_shall_ignore($event)) return;

        $event->data['name'] = $this->getLang('edit_diagram');
    }

    function handle_form(Doku_Event $event)
    {
        if ($this->_shall_ignore($event)) return;

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

        $form = &$event->data['form'];
        $data = base64_encode($TEXT);

        $this->_addHidden($form, 'plugin_bpmnio_data', $data);
        $this->_addHTML($form, <<<HTML
            <div class="plugin-bpmnio" id="plugin_bpmnio__editor">
                <div class="bpmn_js_data">{$data}</div>
                <div class="bpmn_js_canvas">
                    <div class="bpmn_js_container"></div>
                </div>
                </div>
            </div>
            HTML);

        // used during previews
        $this->_addHidden($form, 'target', 'plugin_bpmnio');
        $this->_addHidden($form, 'range', $RANGE);
    }

    function handle_post(Doku_Event $event)
    {
        global $TEXT;
        global $INPUT;

        if (!$INPUT->post->has('plugin_bpmnio_data')) return;

        $TEXT = base64_decode($INPUT->post->str('plugin_bpmnio_data'));
    }

    private function _addHidden($form, $field, $data)
    {
        if (is_a($form, Form::class)) { // $event->name is EDIT_FORM_ADDTEXTAREA
            $form->setHiddenField($field, $data);
        } else { // $event->name is HTML_EDIT_FORMSELECTION
            $form->addHidden($field, $data);
        }
    }

    private function _addHTML($form, $data)
    {
        $form->addHTML($data);
    }

    private function _shall_ignore(Doku_Event $event)
    {
        if ($event->data['target'] !== 'plugin_bpmnio')
            return true;
        return false;
    }
}
