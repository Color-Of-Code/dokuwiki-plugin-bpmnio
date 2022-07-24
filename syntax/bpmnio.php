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
        switch ($state) {
            case DOKU_LEXER_ENTER:
                return array($state, $match, $pos);
            case DOKU_LEXER_UNMATCHED:
                $data = base64_encode($match);
                return array($state, $data, $pos);
            case DOKU_LEXER_EXIT:
                return array($state, $match, $pos);
        }
        return array();
    }

    public function render($mode, Doku_Renderer $renderer, $data)
    {
        // $data is returned by handle()
        if ($mode == 'xhtml' || $mode == 'odt') {
            list($state, $match, $pos) = $data;
            // $renderer->doc .= '<textarea class="bpmn_js_data">' . $match . '</textarea>';
            switch ($state) {
                case DOKU_LEXER_ENTER:
                    preg_match('/<bpmnio type="(\w+)">/', $match, $type);
                    $type = $type[1] ?? 'bpmn';
                    $bpmnid = uniqid('__' . $type . '_js_');
                    $sectionEditData = ['target' => 'plugin_bpmnio_bpmnio'];
                    if (!defined('SEC_EDIT_PATTERN')) {
                        // backwards-compatibility for Frusterick Manners (2017-02-19)
                        $sectionEditData = 'plugin_bpmnio_bpmnio';
                    }
                    $class = $renderer->startSectionEdit($data[$pos], $sectionEditData);

                    $renderer->doc .= '<div class="' . $class . '">';
                    $renderer->doc .= '<textarea class="bpmn_js_data" id="' . $bpmnid . '" style="visibility:hidden;">';
                    break;

                case DOKU_LEXER_UNMATCHED:
                    $renderer->doc .= trim($match);
                    break;
                case DOKU_LEXER_EXIT:
                    $renderer->doc .= '</textarea>';
                    $renderer->doc .= '</div>';
                    $renderer->finishSectionEdit($pos);
                    break;
            }
            return true;
        }
        return false;
    }
}
