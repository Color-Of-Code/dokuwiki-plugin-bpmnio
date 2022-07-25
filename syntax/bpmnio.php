<?php

/**
 * @license    See LICENSE file
 * @author     Jaap de Haan <jaap.dehaan@color-of-code.de>
 */

// must be run within DokuWiki
if (!defined('DOKU_INC')) {
    die();
}

// See help: https://www.dokuwiki.org/devel:syntax_plugins

class syntax_plugin_bpmnio_bpmnio extends DokuWiki_Syntax_Plugin
{
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
        $posStart = $pos;
        $posEnd = $pos + strlen($match);

        if ($state == DOKU_LEXER_UNMATCHED) {
            $match = base64_encode(trim($match));
        }
        return array($match, $state, $posStart, $posEnd);
    }

    public function render($mode, Doku_Renderer $renderer, $data)
    {
        list($match, $state, $posStart, $posEnd) = $data;

        if (is_a($renderer, 'renderer_plugin_dw2pdf')) {
            if ($state == DOKU_LEXER_EXIT) {
                $renderer->doc .= <<<HTML
                    <div class="plugin-bpmnio">
                        <a href="https://github.com/Color-Of-Code/dokuwiki-plugin-bpmnio/issues/4">DW2PDF support missing: Help wanted</a>
                    </div>
                    HTML;
            }
            return true;
        }

        if ($mode == 'xhtml' || $mode == 'odt') {
            switch ($state) {
                case DOKU_LEXER_ENTER:
                    preg_match('/<bpmnio type="(\w+)">/', $match, $type);
                    $type = $type[1] ?? 'bpmn';
                    $bpmnid = uniqid('__' . $type . '_js_');
                    $renderer->doc .= <<<HTML
                        <div class="plugin-bpmnio" id="{$bpmnid}">
                        HTML;
                    break;

                case DOKU_LEXER_UNMATCHED:
                    $renderer->doc .= <<<HTML
                        <div class="bpmn_js_data">
                            {$match}
                        </div>
                        HTML;

                    $class = $this->_startSectionEdit($renderer, $posStart);
                    $renderer->doc .= <<<HTML
                        <div class="bpmn_js_canvas {$class}">
                            <div class="bpmn_js_container {$class}"></div>
                        </div>
                        HTML;
                    $this->_finishSectionEdit($renderer, $posEnd);
                    break;

                case DOKU_LEXER_EXIT:
                    $renderer->doc .= '</div>';
                    break;
            }
            return true;
        }
        return false;
    }

    private function _startSectionEdit(Doku_Renderer $renderer, $pos)
    {
        $sectionEditData = ['target' => 'plugin_bpmnio'];
        if (!defined('SEC_EDIT_PATTERN')) {
            // backwards-compatibility for Frusterick Manners (2017-02-19)
            $sectionEditData = 'plugin_bpmnio';
        }
        return $renderer->startSectionEdit($pos, $sectionEditData);
    }

    private function _finishSectionEdit(Doku_Renderer $renderer, $pos)
    {
        $renderer->finishSectionEdit($pos);
    }
}
