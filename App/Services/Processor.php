<?php

namespace Services;

use App\Controllers\ListarController;

class Processor
{
    public function executar_ciclo($idProcesso, $qtLimit)
    {
        echo "[" . date('H:i:s') . "] Iniciando processamento...\n";

        $listarController = new ListarController();

        $listarController->listar(
            $idProcesso,
            $qtLimit
        );

        echo "[" . date('H:i:s') . "] Processamento finalizado.\n";
    }
}
