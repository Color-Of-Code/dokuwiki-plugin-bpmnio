<?php

/**
 * @license    See LICENSE file
 * @author     Jaap de Haan <jaap.dehaan@color-of-code.de>
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


class syntax_plugin_bpmnio_bpmnio extends DokuWiki_Syntax_Plugin
{
    private $type = ''; // 'bpmn' or 'dmn'

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
            case DOKU_LEXER_ENTER :
                $matched = '';
                preg_match('/<bpmnio type="(\w+)">/', $match, $matched);
                $this->$type = $matched[1] ?? 'bpmn';
                return array($state, $this->$type, '', '', '');

            case DOKU_LEXER_UNMATCHED:
                $posStart = $pos;
                $posEnd = $pos + strlen($match);
                $match = base64_encode($match);
                return array($state, $this->$type, $match, $posStart, $posEnd);

            case DOKU_LEXER_EXIT:
                $this->$type = '';
                return array($state, '', '', '', '');
        }
        return array();
    }

    public function render($mode, Doku_Renderer $renderer, $data)
    {
        list($state, $type, $match, $posStart, $posEnd) = $data;

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
                    $bpmnid = uniqid("__{$type}_js_");
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

                    $class = $this->_startSectionEdit($renderer, $posStart, $type);
                    $renderer->doc .= <<<HTML
                        <div class="{$type}_js_canvas {$class}">
                            <div class="{$type}_js_container"></div>
                        </div>
                        HTML;
                    $this->_finishSectionEdit($renderer, $posEnd);
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

    private function _startSectionEdit(Doku_Renderer $renderer, $pos, $type)
    {
        $target = "plugin_bpmnio_{$type}";
        $sectionEditData = ['target' => $target];
        if (!defined('SEC_EDIT_PATTERN')) {
            // backwards-compatibility for Frusterick Manners (2017-02-19)
            $sectionEditData = $target;
        }
        return $renderer->startSectionEdit($pos, $sectionEditData);
    }

    private function _finishSectionEdit(Doku_Renderer $renderer, $pos)
    {
        $renderer->finishSectionEdit($pos);
    }
}
