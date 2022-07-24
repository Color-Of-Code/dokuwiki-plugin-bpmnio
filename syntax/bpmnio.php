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
        if ($state == DOKU_LEXER_UNMATCHED) {
            $match = base64_encode($match);
        }
        return array($match, $state);
    }

    // $data is returned by handle()
    public function render($mode, Doku_Renderer $renderer, $data)
    {
        if ($mode == 'xhtml' || $mode == 'odt') {
            list($match, $state) = $data;
            switch ($state) {
                case DOKU_LEXER_ENTER:
                    preg_match('/<bpmnio type="(\w+)">/', $match, $type);
                    $type = $type[1] ?? 'bpmn';
                    $bpmnid = uniqid('__' . $type . '_js_');
                    $renderer->doc .= <<<HTML
                        <div class="plugin-bpmnio" id="{$bpmnid}">
                            <textarea class="bpmn_js_data" style="visibility:hidden;">
                        HTML;
                    break;

                case DOKU_LEXER_UNMATCHED:
                    $renderer->doc .= trim($match);
                    break;
                case DOKU_LEXER_EXIT:
                    $renderer->doc .= <<<HTML
                            </textarea>
                            <div class="bpmn_js_container"></div>
                        </div>
                        HTML;
                    break;
            }
            return true;
        }
        return false;
    }
}
