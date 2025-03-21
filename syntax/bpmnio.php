<?php

use dokuwiki\Extension\SyntaxPlugin;

/**
 * @license    See LICENSE file
 */
// See help: https://www.dokuwiki.org/devel:syntax_plugins
// The HTML structure generated by this syntax plugin is:
//
// <div class="plugin-bpmnio" id="__(bpmn|dmn)_js_<hash>">
//   <div class="bpmn_js_data">
//      ... base64 encoded xml
//   </div>
//   <div class="bpmn_js_canvas {$class}">
//     <div class="bpmn_js_container">... rendered herein</div>
//   </div>
// </div>
class syntax_plugin_bpmnio_bpmnio extends SyntaxPlugin
{
    protected string $type = ''; // 'bpmn' or 'dmn'
    protected string $src = ''; // media file

    public function getPType()
    {
        return 'block';
    }

    public function getType()
    {
        return 'protected';
    }

    public function getSort()
    {
        return 0;
    }

    public function connectTo($mode)
    {
        $this->Lexer->addEntryPattern('<bpmnio.*?>(?=.*?</bpmnio>)', $mode, 'plugin_bpmnio_bpmnio');
    }

    public function postConnect()
    {
        $this->Lexer->addExitPattern('</bpmnio>', 'plugin_bpmnio_bpmnio');
    }

    public function handle($match, $state, $pos, Doku_Handler $handler)
    {
        switch ($state) {
            case DOKU_LEXER_ENTER:
                $matched = [];
                preg_match('/<bpmnio\s+([^>]+)>/', $match, $matched);

                $attrs = [];
                if (!empty($matched[1])) {
                    $attrs = $this->buildAttributes($matched[1]);
                }

                $this->type = $attrs['type'] ?? 'bpmn';
                $this->src = $attrs['src'] ?? '';

                return [$state, $this->type, '', $pos, '', false];

            case DOKU_LEXER_UNMATCHED:
                $posStart = $pos;
                $posEnd = $pos + strlen($match);

                $inline = empty($this->src);
                if (!$inline) {
                    $match = $this->getMedia($this->src);
                }
                return [$state, $this->type, base64_encode($match), $posStart, $posEnd, $inline];

            case DOKU_LEXER_EXIT:
                $this->type = '';
                $this->src = '';
                return [$state, '', '', '', '', '', false];
        }
        return [];
    }

    private function buildAttributes($string)
    {
        $attrs = [];
        preg_match_all('/(\w+)=["\'](.*?)["\']/', $string, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $attrs[$match[1]] = $match[2];
        }
        return $attrs;
    }

    private function getMedia($src)
    {
        $file = mediaFN($src);

        if (!file_exists($file) || !is_readable($file)) {
            return "Error: Cannot load file $src";
        }

        return file_get_contents($file);
    }

    public function render($mode, Doku_Renderer $renderer, $data)
    {
        [$state, $type, $match, $posStart, $posEnd, $inline] = $data;

        if (is_a($renderer, 'renderer_plugin_dw2pdf')) {
            if ($state == DOKU_LEXER_EXIT) {
                $renderer->doc .= <<<HTML
                    <div class="plugin-bpmnio">
                        <a href="https://github.com/Color-Of-Code/dokuwiki-plugin-bpmnio/issues/4">
                            DW2PDF support missing: Help wanted
                        </a>
                    </div>
                    HTML;
            }
            return true;
        }

        if ($mode == 'xhtml' || $mode == 'odt') {
            switch ($state) {
                case DOKU_LEXER_ENTER:
                    $bpmnid = "__{$type}_js_{$posStart}";
                    $renderer->doc .= <<<HTML
                        <div class="plugin-bpmnio" id="{$bpmnid}">
                        HTML;
                    break;

                case DOKU_LEXER_UNMATCHED:
                    $renderer->doc .= <<<HTML
                        <div class="{$type}_js_data">
                            {$match}
                        </div>
                        HTML;
                    if ($inline) {
                        $target = "plugin_bpmnio_{$type}";
                        $sectionEditData = ['target' => $target];
                        $class = $renderer->startSectionEdit($posStart, $sectionEditData);
                    } else {
                        $class = '';
                    }
                    $renderer->doc .= <<<HTML
                        <div class="{$type}_js_canvas {$class}">
                            <div class="{$type}_js_container"></div>
                        </div>
                        HTML;
                    if ($inline) {
                        $renderer->finishSectionEdit($posEnd);
                    }
                    break;

                case DOKU_LEXER_EXIT:
                    $renderer->doc .= <<<HTML
                        </div>
                        HTML;
                    break;
            }
            return true;
        }
        return false;
    }
}
