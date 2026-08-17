<?php

namespace App\Utilis;


class Configs
{

    public static function env($param)
    {

        // $filePath = __DIR__ . DIRECTORY_SEPARATOR . 'env.json';
        $filePath = file_get_contents('https://site2.proscore.com.br/progestor/env.json');
        $obj = json_decode($filePath, true);

        return $obj[$param];
    }
}
