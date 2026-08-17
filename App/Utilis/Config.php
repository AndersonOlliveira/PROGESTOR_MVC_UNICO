<?php

namespace App\Utilis;



class Config
{

    public static function env_old($param)
    {

        // $filePath = __DIR__ . DIRECTORY_SEPARATOR . 'env.json';
        $filePath = file_get_contents('https://site2.proscore.com.br/progestor/env.json');
        $obj = json_decode($filePath, true);

        return $obj[$param];
    }

    public static function env($param)
    {

        $confContent = file_get_contents('/usr/chp/pub/prod/pag/progestor/env.json');
        $obj = json_decode($confContent, true);

        return $obj[$param];
    }

    public static function env_json($param)
    {

        $confContent = file_get_contents('https://site2.proscore.com.br/progestor/env.json');


        $obj = json_decode($confContent, true);

        return $obj[$param];
    }
}
