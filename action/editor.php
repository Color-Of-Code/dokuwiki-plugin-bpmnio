<?php

/**
 * @license    See LICENSE file
 */

// See help:
// * https://www.dokuwiki.org/devel:section_editor
// * https://www.dokuwiki.org/devel:releases:refactor2021
use dokuwiki\Extension\ActionPlugin;
use dokuwiki\Extension\EventHandler;
use dokuwiki\Extension\Event;
use dokuwiki\Form\Form;
use dokuwiki\Utf8;

class action_plugin_bpmnio_editor extends ActionPlugin
{
    public function register(EventHandler $controller)
    {
        $controller->register_hook('HTML_SECEDIT_BUTTON', 'BEFORE', $this, 'sectionEditButton');
        $controller->register_hook('EDIT_FORM_ADDTEXTAREA', 'BEFORE', $this, 'handleForm');
        $controller->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE', $this, 'handlePost');
        $controller->register_hook('FORM_EDIT_OUTPUT', 'BEFORE', $this, 'handleFormEditOutput');
    }

    public function handleFormEditOutput(Event $event)
    {
        /** @var Doku_Form $form */
        $form = &$event->data;

        $previewButtonPosition = $form->findPositionByAttribute('id', 'edbtn__preview');
        if ($previewButtonPosition !== false) {
            $form->removeElement($previewButtonPosition);
        }
    }

    public function sectionEditButton(Event $event)
    {
        if ($this->shallIgnore($event)) return;

        $event->data['name'] = $this->getLang('edit_diagram');
    }

    public function handleForm(Event $event)
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

    public function handlePost(Event $event)
    {
        global $TEXT;
        global $INPUT;

        if (!$INPUT->post->has('plugin_bpmnio_data')) return;

        $TEXT = base64_decode($INPUT->post->str('plugin_bpmnio_data'));
    }

    private function shallIgnore(Event $event)
    {
        if ($event->data['target'] === 'plugin_bpmnio_bpmn')
            return false;
        if ($event->data['target'] === 'plugin_bpmnio_dmn')
            return false;
        return true;
    }
}
