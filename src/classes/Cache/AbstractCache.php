<?php

namespace Cache;

class AbstractCache
{
    /** Получение уникального ID для кеша
     * @param array $params массив данных для создания уникального ID
     * @return string
     */
    public static function getCacheId(array $params): string
    {
        return sha1(serialize($params));
    }

    /** Запись значения кеша
     * @param string $cacheId
     * @return string|null
     */
    protected static function getCacheValue(string $cacheId)
    {

    }

    /** Получение значения кеша
     * @param string $cacheId
     * @param string $valueForCache кешируемое значение
     * @return void
     */
    protected static function setCacheValue(string $cacheId, string $valueForCache): void
    {

    }

    /** Удаление значения кеша
     * @param string $cacheId
     * @return void
     */
    public static function deleteCacheValue(string $cacheId): void
    {

    }
}