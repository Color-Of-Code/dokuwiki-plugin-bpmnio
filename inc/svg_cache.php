<?php

class plugin_bpmnio_svg_cache
{
    public static function buildKey(string $type, string $xml): string
    {
        return sha1($type . "\0" . $xml);
    }

    public static function isValidKey(string $key): bool
    {
        return (bool) preg_match('/^[a-f0-9]{40}$/', $key);
    }

    public static function load(string $key): ?string
    {
        if (!self::isValidKey($key)) {
            return null;
        }

        $path = self::getPath($key);
        if (!is_readable($path)) {
            return null;
        }

        $svg = file_get_contents($path);
        return is_string($svg) ? $svg : null;
    }

    public static function save(string $key, string $svg): bool
    {
        if (!self::isValidKey($key)) {
            return false;
        }

        $svg = self::sanitize($svg);
        if ($svg === null) {
            return false;
        }

        $directory = self::getDirectory();
        if (!io_mkdir_p($directory)) {
            return false;
        }

        return io_saveFile(self::getPath($key), $svg);
    }

    public static function getPath(string $key): string
    {
        return self::getDirectory() . '/' . $key . '.svg';
    }

    private static function getDirectory(): string
    {
        return DOKU_INC . 'data/cache/bpmnio';
    }

    private static function sanitize(string $svg): ?string
    {
        $svg = trim($svg);
        if ($svg === '' || strlen($svg) > 2 * 1024 * 1024) {
            return null;
        }

        if (stripos($svg, '<!DOCTYPE') !== false) {
            return null;
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $document->preserveWhiteSpace = false;
        $document->formatOutput = false;

        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadXML($svg, LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (!$loaded || !$document->documentElement || $document->documentElement->localName !== 'svg') {
            return null;
        }

        $normalized = $document->saveXML($document->documentElement);
        return is_string($normalized) && $normalized !== '' ? $normalized : null;
    }
}
