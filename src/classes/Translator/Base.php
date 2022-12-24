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

    protected function getCachedValue(string $originalString)
    {
        $params = [
            'from' => $this->fromLanguage,
            'to' => $this->toLanguage,
            'string' => $originalString
        ];

        $cacheId = Cache::getCacheId($params);

        return Cache::getCacheValue($cacheId);
    }

    protected function setCachedValue(string $valueForCache): void
    {
        $params = [
            'from' => $this->fromLanguage,
            'to' => $this->toLanguage,
            'string' => $valueForCache
        ];

        $cacheId = Cache::getCacheId($params);
        Cache::setCacheValue($cacheId, $valueForCache);
    }

    protected function getFromDB($string)
    {
        $dbResult = DB::select(self::DB_TABLE_NAME, [$this->fromLanguage, $this->toLanguage], [$this->fromLanguage => $string]);

        if ($arResult = $dbResult->Fetch()) {
            if (!empty($arResult[$this->toLanguage])) {
                $this->setCachedValue($arResult[$this->toLanguage]);

                return $arResult[$this->toLanguage];
            }

            return null;
        }

        DB::add(self::DB_TABLE_NAME, [$this->fromLanguage => $string]);
        return null;
    }

    public static function getTranslate(string $originalString): string
    {
        if (!isset(self::$instance))
            throw new \Exception("Не заданы языки для перевода текста.");

        $instance = self::$instance;

        $translatedString = $instance->getCachedValue($originalString);

        if (empty($translatedString))
            $translatedString = $instance->getFromDB($originalString) ?? $originalString;

        return $translatedString;
    }

    public static function setLocales(string $fromLanguage, string $toLanguage): void
    {
        $fromLanguage = strtoupper($fromLanguage);
        $toLanguage = strtoupper($toLanguage);

        if (!isset(self::$instance)) {
            self::$instance = new self($fromLanguage, $toLanguage);
        }
    }

    public static function setTranslate(array $values): void
    {
        $dbResult = DB::select(self::DB_TABLE_NAME, ['*'], $values, true);

        if ($arResult = $dbResult->Fetch())
            DB::update(self::DB_TABLE_NAME, $values, ['id' => $arResult['id']]);
        else
            DB::add(self::DB_TABLE_NAME, $values);
    }
}