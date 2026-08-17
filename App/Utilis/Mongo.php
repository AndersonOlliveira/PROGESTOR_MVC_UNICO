<?php

namespace App\Utilis;

use PDO;
use DateTime;

use Core\Model;
use App\Models\instance;
use Core\Logs;
use PDOException;
use Core\Functions;
use Core\AppManipularError;
use App\Utilis\Funcoes;


class Mongo
{

    protected $utils;
    protected $utils_functions;
    protected $tratamento;

    protected $MontaJsonConfigEHeadersDaConsultas;
    protected $GravaTransacao;
    protected $GravaRespostaPlugin;
    protected $teste;
    public function __construct()
    {

        $this->utils = new instance();

        // require_once __DIR__ . '/Funcoes.php';
        $this->utils_functions = new Funcoes();
    }
    public function get_dada_all()
    {

        //DELETO ARQUIVOS COM MAS DE 40 DIAS 
        $dados = $this->utils->data_all();
        $dados_deletar = [];

        foreach ($dados as $key => $valores) {

            $object = $valores->_id;

            $timestamp = $object->getTimestamp();
            $date = new DateTime(date('d-m-Y', $timestamp));

            $data_atual = new DateTime(date('d-m-Y'));
            $intervalo = $date->diff($data_atual);

            if ($intervalo->days >= 365) {

                $dados_deletar[] = $valores->_id;
                // $this->utils->delete($valores->_id);

            } else {

                $msg = "ID Não deletado: " . $valores->_id . " - Data de Criação: " . $date->format('d-m-Y') . " - Dias: " . $intervalo->days . "\n";
                error_log($msg, 3, __DIR__ . '/log_mongo.txt');
            }

            // $dados[$key]->data_cadastro = $date;
            // echo "A data de inserção do documento é: " . $date . ' tem o id ' . $object . "\n";
        }

        if (!empty($dados_deletar)) {
            echo "meus Ids para ser deletados \n";
            $this->utils->delete_all($dados_deletar);
        }

        $tam_banco = $this->utils->get_size_database();
        $tam_banco  = $this->utils_functions->formatarTamanho($tam_banco);




        $msg = "tamanho do banco: " . $tam_banco . " - Data de Criação: " . $date->format('d-m-Y') . " - Dias: " . $intervalo->days . "\n";
        error_log($msg, 3, __DIR__ . '/../../errosTxt/tamBanco.txt');
    }
}
