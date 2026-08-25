<?php

namespace App\Utilis;

use Throwable;
use Exception;

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
    echo "ANTES DO ENV.JSON\n";

    $url = 'https://site2.proscore.com.br/progestor/env.json';

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);

    echo "ANTES DO CURL\n";

    $confContent = curl_exec($ch);

    echo "DEPOIS DO CURL\n";

    if ($confContent === false) {
        $erro = curl_error($ch);
        curl_close($ch);

        throw new Exception("Erro CURL: {$erro}");
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    echo "HTTP: {$httpCode}\n";

    curl_close($ch);

    $obj = json_decode($confContent, true);

    if (!is_array($obj)) {
        throw new Exception('JSON inválido: ' . json_last_error_msg());
    }

    if (!array_key_exists($param, $obj)) {
        throw new Exception("Parâmetro '{$param}' não encontrado");
    }

    return $obj[$param];
}
}
