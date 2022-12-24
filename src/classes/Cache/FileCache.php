<?php

namespace Cache;

class FileCache extends AbstractCache
{
    private const CACHE_BASE_DIR = "translate_cache";

    public static function getCacheValue(string $cacheId)
    {
        $filePath = $_SERVER['DOCUMENT_ROOT'] . "/" . self::CACHE_BASE_DIR . "/" . $cacheId . ".cache";

        if (file_exists($filePath))
            return file_get_contents($filePath);

        return false;
    }

    public static function setCacheValue(string $cacheId, $valueForCache): void
    {
        $filePath = $_SERVER['DOCUMENT_ROOT'] . "/" . self::CACHE_BASE_DIR . "/" . $cacheId . ".cache";

        if (!is_dir($_SERVER['DOCUMENT_ROOT'] . "/" . self::CACHE_BASE_DIR))
            mkdir($_SERVER['DOCUMENT_ROOT'] . "/" . self::CACHE_BASE_DIR, 0755);

        file_put_contents($filePath, $valueForCache);
    }
}