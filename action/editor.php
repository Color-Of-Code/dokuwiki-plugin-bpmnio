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
    private const SVG_CACHE_AJAX_CALL = 'plugin_bpmnio_svg_cache';

    private function loadLinkProcessor(): void
    {
        require_once __DIR__ . '/../inc/link_processor.php';
    }

    private function loadSvgCache(): void
    {
        require_once __DIR__ . '/../inc/svg_cache.php';
    }

    public function register(EventHandler $controller): void
    {
        $controller->register_hook('HTML_SECEDIT_BUTTON', 'BEFORE', $this, 'sectionEditButton');
        $controller->register_hook('EDIT_FORM_ADDTEXTAREA', 'BEFORE', $this, 'handleForm');
        $controller->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE', $this, 'handlePost');
        $controller->register_hook('FORM_EDIT_OUTPUT', 'BEFORE', $this, 'handleFormEditOutput');
        $controller->register_hook('AJAX_CALL_UNKNOWN', 'BEFORE', $this, 'handleSvgCacheAjax');
    }

    public function handleFormEditOutput(Event $event)
    {
    }

    public function sectionEditButton(Event $event)
    {
        if ($this->shallIgnore($event)) {
            return;
        }

        $event->data['name'] = $this->getLang('edit_diagram');
    }

    public function handleForm(Event $event)
    {
        if ($this->shallIgnore($event)) {
            return;
        }

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
        $this->loadLinkProcessor();
        $payload = plugin_bpmnio_link_processor::buildPayload($TEXT);
        $renderData = base64_encode($payload['xml']);
        $linkData = base64_encode(json_encode($payload['links']));

        $type = 'bpmn';
        if ($event->data['target'] === 'plugin_bpmnio_dmn') {
            $type = 'dmn';
        }

        $this->loadSvgCache();
        $cacheKey = plugin_bpmnio_svg_cache::buildKey($type, $TEXT);

        $form->setHiddenField('plugin_bpmnio_data', $data);
        $form->setHiddenField('plugin_bpmnio_links', $linkData);
        $form->addHTML(<<<HTML
            <div class="plugin-bpmnio" id="plugin_bpmnio__{$type}_editor">
                <div class="{$type}_js_data">{$renderData}</div>
                <div class="{$type}_js_links">{$linkData}</div>
                <div class="{$type}_js_canvas">
                    <div class="{$type}_js_container" data-svg-cache-key="{$cacheKey}"></div>
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

        if (!$INPUT->post->has('plugin_bpmnio_data')) {
            return;
        }

        $TEXT = base64_decode($INPUT->post->str('plugin_bpmnio_data'));
    }

    public function handleSvgCacheAjax(Event $event): void
    {
        if ($event->data !== self::SVG_CACHE_AJAX_CALL) {
            return;
        }

        global $INPUT;

        $event->stopPropagation();
        $event->preventDefault();

        $this->loadSvgCache();

        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
            $this->sendJsonResponse(405, ['ok' => false, 'error' => 'method-not-allowed']);
            return;
        }

        $key = $INPUT->post->str('key');
        $type = $INPUT->post->str('type');
        $svg = $_POST['svg'] ?? '';

        if ($type !== 'bpmn' && $type !== 'dmn') {
            $this->sendJsonResponse(400, ['ok' => false, 'error' => 'invalid-type']);
            return;
        }

        if (!is_string($svg) || !plugin_bpmnio_svg_cache::isValidKey($key)) {
            $this->sendJsonResponse(400, ['ok' => false, 'error' => 'invalid-payload']);
            return;
        }

        $ok = plugin_bpmnio_svg_cache::save($key, $svg);

        $this->sendJsonResponse($ok ? 200 : 400, [
            'ok' => $ok,
            'error' => $ok ? null : 'invalid-svg',
        ]);
    }

    private function sendJsonResponse(int $statusCode, array $payload): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($payload);
    }

    private function shallIgnore(Event $event)
    {
        if ($event->data['target'] === 'plugin_bpmnio_bpmn') {
            return false;
        }
        if ($event->data['target'] === 'plugin_bpmnio_dmn') {
            return false;
        }
        return true;
    }
}
