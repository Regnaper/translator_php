<?php

class Autoloader
{
    public static function register()
    {
        spl_autoload_register(function ($class) {
            $file = str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
            $file = $_SERVER['DOCUMENT_ROOT'] . "/classes/" . $file;

            if (file_exists("$file")) {
                require $file;
                return true;
            }
            return false;
        });
    }
}

Autoloader::register();