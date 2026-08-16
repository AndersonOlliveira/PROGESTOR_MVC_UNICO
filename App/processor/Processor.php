<?php

namespace app\processor;


use app\Controllers\ListarController;



class processor
{
    private int $maxWorkers;
    private int $batchSize;
    private  $idProcesso;
    private int $qtLimit;

    public function __construct(int $maxWorkers = 10, int $batchSize = 10,  $idProcesso = null, int $qtLimit = 1000)
    {
        $this->maxWorkers = $maxWorkers;
        $this->batchSize = $batchSize;
        $this->idProcesso = $idProcesso;
        $this->qtLimit = $qtLimit;
    }


    public function executar_ciclo()
    {


        echo "[" . date('H:i:s') . "] Executando ciclo do Processor (ID: {$this->idProcesso})\n";
        echo "[" . date('H:i:s') . "] Executando ciclo total de linhas (ID: {$this->qtLimit})\n";

        // Aqui você coloca o que realmente precisa processar
        require_once 'App/controllers/ListarController.php';
        require_once 'App/controllers/ProcessController.php';

        $listarController = new ListarController();
        $listarController->listar($this->idProcesso, $this->qtLimit);

        echo "[" . date('H:i:s') . "] Executa deletar Dados Mongo\n";
        echo "-----\n";

        //criado rota para deletar os json depois de 40 dias
        //comentado por segurança
        //$listarController->mongo();
        // $listarController->teste_teste();

        sleep(2);
    }
}
