<?php
use DB\Base as DB,
    Translator\Base as Translator;

DB::init('mysql:3306', 'web_translator', 'test_user', 'test_secret');
DB::createTable('translator_phrases', ['RU' => 'TEXT', 'KZ' => 'TEXT']);

Translator::setTranslate([
    'RU' => 'высококачественный прототип будущего проекта играет важную роль в формировании модели развития',
    'KZ' => 'болашақ жобаның жоғары сапалы прототипі даму моделін қалыптастыруда маңызды рөл атқарады'
]);