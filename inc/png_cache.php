<?php

class plugin_bpmnio_png_cache
{
    public static function buildKey(string $type, string $xml, string $variant = ''): string
    {
        return sha1($type . "\0" . $variant . "\0" . $xml);
    }

    public static function isValidKey(string $key): bool
    {
        return (bool) preg_match('/^[a-f0-9]{40}$/', $key);
    }

    public static function loadDataUri(string $key): ?string
    {
        if (!self::isValidKey($key)) {
            return null;
        }

        $path = self::getPath($key);
        if (!is_readable($path)) {
            return null;
        }

        $png = file_get_contents($path);
        if (!is_string($png) || !self::isValidPng($png)) {
            return null;
        }

        return 'data:image/png;base64,' . base64_encode($png);
    }

    public static function getDimensions(string $key): ?array
    {
        if (!self::isValidKey($key)) {
            return null;
        }

        $size = @getimagesize(self::getPath($key));
        if ($size === false || ($size['mime'] ?? '') !== 'image/png') {
            return null;
        }

        return ['width' => (int) $size[0], 'height' => (int) $size[1]];
    }

    public static function save(string $key, string $png): bool
    {
        if (!self::isValidKey($key)) {
            return false;
        }

        $png = self::decodePng($png);
        if ($png === null) {
            return false;
        }

        $directory = self::getDirectory();
        if (!io_mkdir_p($directory)) {
            return false;
        }

        return file_put_contents(self::getPath($key), $png) !== false;
    }

    public static function getPath(string $key): string
    {
        return self::getDirectory() . '/' . $key . '.png';
    }

    private static function getDirectory(): string
    {
        return DOKU_INC . 'data/cache/bpmnio';
    }

    private static function decodePng(string $png): ?string
    {
        $png = trim($png);
        if (preg_match('/^data:image\/png;base64,(.+)$/s', $png, $matches)) {
            $decoded = base64_decode($matches[1], true);
            if ($decoded === false) {
                return null;
            }
            $png = $decoded;
        }

        if (strlen($png) > 5 * 1024 * 1024 || !self::isValidPng($png)) {
            return null;
        }

        return $png;
    }

    private static function isValidPng(string $png): bool
    {
        if (substr($png, 0, 8) !== "\x89PNG\r\n\x1a\n") {
            return false;
        }

        $size = @getimagesizefromstring($png);
        return $size !== false
            && ($size['mime'] ?? '') === 'image/png'
            && (int) $size[0] > 0
            && (int) $size[1] > 0;
    }
}
