<?php

class plugin_bpmnio_link_processor
{
    /**
     * @return array{xml: string, links: array<string, array{href: string, target: string}>}
     */
    public static function buildPayload(string $xml): array
    {
        if (trim($xml) === '') {
            return ['xml' => $xml, 'links' => []];
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $document->preserveWhiteSpace = true;
        $document->formatOutput = false;

        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadXML($xml);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (!$loaded) {
            return ['xml' => $xml, 'links' => []];
        }

        $links = [];
        foreach ($document->getElementsByTagName('*') as $element) {
            if (!$element->hasAttribute('id') || !$element->hasAttribute('name')) {
                continue;
            }

            $parsedLink = self::parseLinkMarkup($element->getAttribute('name'));
            if ($parsedLink === null) {
                continue;
            }

            $target = self::resolveTarget($parsedLink['target']);
            if ($target === null || auth_quickaclcheck($target) < AUTH_READ) {
                continue;
            }

            $elementId = trim($element->getAttribute('id'));
            if ($elementId === '') {
                continue;
            }

            $label = $parsedLink['label'] !== '' ? $parsedLink['label'] : $target;

            $element->setAttribute('name', $label);
            $links[$elementId] = [
                'href' => self::buildHref($target),
                'target' => $target,
            ];
        }

        $renderXml = $document->saveXML();
        if ($renderXml === false) {
            $renderXml = $xml;
        }

        return ['xml' => $renderXml, 'links' => $links];
    }

    /**
     * @return array{target: string, label: string}|null
     */
    private static function parseLinkMarkup(string $value): ?array
    {
        $value = trim($value);
        if (!preg_match('/^\[\[([^\]|]+)(?:\|([^\]]*))?\]\]$/', $value, $matches)) {
            return null;
        }

        $target = trim($matches[1]);
        if ($target === '') {
            return null;
        }

        return [
            'target' => $target,
            'label' => isset($matches[2]) ? trim($matches[2]) : '',
        ];
    }

    private static function resolveTarget(string $target): ?string
    {
        global $ID;

        $target = trim($target);
        if ($target === '' || preg_match('#^[a-z][a-z0-9+.-]*://#i', $target)) {
            return null;
        }

        if (str_starts_with($target, ':')) {
            return self::normalizeId(trim($target, ':'));
        }

        $baseNamespace = self::getNamespace((string) $ID);
        if (str_starts_with($target, '.')) {
            $segments = $baseNamespace === '' ? [] : explode(':', $baseNamespace);
            foreach (explode(':', $target) as $segment) {
                $segment = trim($segment);
                if ($segment === '' || $segment === '.') {
                    continue;
                }
                if ($segment === '..') {
                    array_pop($segments);
                    continue;
                }
                $segments[] = $segment;
            }

            return self::normalizeId(implode(':', $segments));
        }

        if (str_contains($target, ':')) {
            return self::normalizeId($target);
        }

        $pageId = $baseNamespace === '' ? $target : $baseNamespace . ':' . $target;
        return self::normalizeId($pageId);
    }

    private static function buildHref(string $target): string
    {
        return DOKU_BASE . 'doku.php?' . http_build_query(['id' => $target], '', '&', PHP_QUERY_RFC3986);
    }

    private static function getNamespace(string $pageId): string
    {
        $pos = strrpos($pageId, ':');
        if ($pos === false) {
            return '';
        }

        return substr($pageId, 0, $pos);
    }

    private static function normalizeId(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (function_exists('cleanID')) {
            $value = cleanID($value);
        }

        $segments = [];
        foreach (explode(':', $value) as $segment) {
            $segment = trim($segment);
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                array_pop($segments);
                continue;
            }
            $segments[] = $segment;
        }

        if ($segments === []) {
            return null;
        }

        return implode(':', $segments);
    }
}
