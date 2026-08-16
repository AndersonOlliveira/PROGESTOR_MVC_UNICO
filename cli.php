<?php

date_default_timezone_set('America/Sao_Paulo');

require __DIR__ . '/vendor/autoload.php';
// require_once __DIR__ . '/App/Core/AppProcessor.php';

set_time_limit(0);

use Core\AppProcessor;

$app = new AppProcessor();

$id = null;
$quantidade = 20;

$tempoEspera = 20;

while (true) {

    try {

        echo "[" . date('H:i:s') . "] Executando processo CLI...\n";

        $app->processar(
            $id,
            $quantidade
        );

        echo "[" . date('H:i:s') . "] Aguardando {$tempoEspera} segundos...\n";

        sleep($tempoEspera);
    } catch (Throwable $e) {

        echo "[" . date('H:i:s') . "] ERRO: {$e->getMessage()}\n";

        echo "Arquivo: {$e->getFile()}\n";
        echo "Linha: {$e->getLine()}\n";

        sleep($tempoEspera);
    }
}
