<?php

namespace Cache;

class AbstractCache
{
    public static function getCacheId(array $params): string
    {
        return sha1(serialize($params));
    }

    protected static function getCacheValue(string $cacheId)
    {

    }

    protected static function setCacheValue(string $cacheId, $valueForCache)
    {

    }
}