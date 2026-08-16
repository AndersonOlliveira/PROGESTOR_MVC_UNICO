<?php

class Env{

    public static function load($path){

        if(!file_exists($path)){

            throw new Exception("Arquivo .env não encontrador {$path}");

        }
            $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue; // ignora comentários
            [$name, $value] = array_map('trim', explode('=', $line, 2));
            $_ENV[$name] = $value;
            putenv("$name=$value");
        }
    }
}