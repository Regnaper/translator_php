<?php

namespace Translator;

use Cache\FileCache as Cache,
    DB\Base as DB;

class Base
{
    protected const DB_TABLE_NAME = "translator_phrases";
    protected string $fromLanguage;
    protected string $toLanguage;
    protected static self $instance;

    protected function __construct(string $fromLanguage, string $toLanguage)
    {
        $this->fromLanguage = $fromLanguage;
        $this->toLanguage = $toLanguage;
    }

    /**
     * Получение значения перевода из кеша
     * @param string $originalString
     * @return string|null
     */
    protected function getCachedValue(string $originalString)
    {
        $params = [
            'toLanguage' => $this->toLanguage,
            'string' => $originalString
        ];

        $cacheId = Cache::getCacheId($params);

        return Cache::getCacheValue($cacheId);
    }

    /**
     * Сохранение кеша для перевода
     * @param string $valueForCache
     * @return void
     */
    protected function setCachedValue(string $valueForCache): void
    {
        $params = [
            'toLanguage' => $this->toLanguage,
            'string' => $valueForCache
        ];

        $cacheId = Cache::getCacheId($params);
        Cache::setCacheValue($cacheId, $valueForCache);
    }

    /**
     * Получение перевода из базы данных
     * @param string $string
     * @return string|null
     *
     * @throws \PDOException|\Exception
     */
    protected function getFromDB(string $string)
    {
        $dbResult = DB::select(self::DB_TABLE_NAME, [$this->fromLanguage, $this->toLanguage], [$this->fromLanguage => $string]);

        if ($arResult = $dbResult->Fetch()) {
            if (!empty($arResult[$this->toLanguage])) {
                // в случае наличия перевода сохранить кеш перевода и вернуть значение перевода
                $this->setCachedValue($arResult[$this->toLanguage]);

                return $arResult[$this->toLanguage];
            }

            return null;
        }
        // в случае отсутствия перевода добавить в БД строку с оригинальным текстом без перевода
        DB::add(self::DB_TABLE_NAME, [$this->fromLanguage => $string]);
        return null;
    }

    /**
     * Получение перевода для строки текста
     * @param string $originalString
     * @return string
     *
     * @throws \Exception
     */
    public static function getTranslate(string $originalString): string
    {
        if (!isset(self::$instance))
            throw new \Exception("Не заданы языки для перевода текста.");

        $instance = self::$instance;

        $translatedString = $instance->getCachedValue($originalString);

        if (empty($translatedString))
            // при отсутствии перевода в кеше получить из БД или вернуть оригинальную строку
            $translatedString = $instance->getFromDB($originalString) ?? $originalString;

        return $translatedString;
    }

    /**
     * Задание языков для перевода
     * @param string $fromLanguage код языка оригинальной строки
     * @param string $toLanguage код языка перевода
     * @return void
     */
    public static function setLocales(string $fromLanguage, string $toLanguage): void
    {
        $fromLanguage = strtoupper($fromLanguage);
        $toLanguage = strtoupper($toLanguage);

        // создание инстанса при его отсутствии
        if (!isset(self::$instance)) {
            self::$instance = new self($fromLanguage, $toLanguage);
        }
        else {
            self::$instance->fromLanguage = $fromLanguage;
            self::$instance->toLanguage = $toLanguage;
        }
    }

    /**
     * Задание перевода для фразы
     * @param array $values ассоциативный массив языковых фраз одного текста ['RU' => 'текст', 'KZ' => 'текст']
     * @return void
     * @throws \PDOException|\Exception
     */
    public static function setTranslate(array $values): void
    {
        $dbResult = DB::select(self::DB_TABLE_NAME, ['*'], $values, true);

        if ($arResult = $dbResult->Fetch()) {
            // при наличии в БД строки с любой из указанных языковых версий текста обновить эту строку
            DB::update(self::DB_TABLE_NAME, $values, ['id' => $arResult['id']]);

            // сбросить кеш для всех языковых версий данной строки
            foreach ($arResult as $key => $value) {
                if (array_key_exists($key, $values)) {
                    $params = [
                        'toLanguage' => $key,
                        'string' => $value
                    ];

                    $cacheId = Cache::getCacheId($params);
                    Cache::deleteCacheValue($cacheId);
                }
            }
        } else
            DB::add(self::DB_TABLE_NAME, $values);
    }

    /**
     * Получение переводов из базы данных
     * @param array $filter ассоциативный массив фильтра для передачи в запрос ['поле' => 'значение']
     * @return array
     * @throws \PDOException|\Exception
     */
    public static function getTranslates(array $filter = []): array
    {
        $result = [];

        $dbResult = DB::select(self::DB_TABLE_NAME, ['*'], $filter);
        while ($arResult = $dbResult->Fetch()) {
            $result[$arResult['id']] = $arResult;
        }

        return $result;
    }
}