<?php


namespace App\Controllers;


use Core\Model;
use App\Models\process;
use App\Models\instance;
use App\Utilis\Arquivos;
use App\Utilis\Gerador;
use App\Utilis\Mongo;
use App\Utilis\Funcoes;
use App\Utilis\Config;
use App\Utilis\GerarOutput;
use Core\Controller;
use App\Core\MongoConect;


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


class ListarController extends Controller
{

    protected $utils;
    protected $utilss;
    protected $utilis_pgadmin;
    protected $utils_out;
    protected $utils_mongo;
    protected $tratamento;
    protected $utils_functions;
    protected $arquivos_json;
    protected $geradador;


    public function __construct()
    {
        // require_once __DIR__ . '/../Utilis/Arquivos.php';
        $this->tratamento = new Arquivos();
        $this->geradador = new Gerador();

        $this->utilss = new instance();

        $this->utils = new Arquivos();

        $this->utils_out = new GerarOutput();

        $this->utils_mongo = new Mongo();

        // require_once __DIR__ . '/../models/process.php';
        $this->utilis_pgadmin = new process();

        // require_once __DIR__ . '/../Utilis/Funcoes.php';
        $this->utils_functions = new Funcoes();

        // require_once __DIR__ . '/../Utilis/Config.php';
        $this->arquivos_json = new Config();
    }


    public function arquivos($idProcesso = null, $qtLimit = null)
    {

        echo "<pre>";
        print_r($idProcesso);
        print_r('estou chegando na chamada do gerador');
    }


    public function listar($idProcesso = null, $qtLimit = null)
    {

        echo "<pre>";
        print_R('ESTOU CHEGANDO AQUI NO CONTROLLER!!!');
        print_R($idProcesso);

        $result_idProcess = [];
        $return = $this->utilis_pgadmin;
        $returns = $return->list_processo($idProcesso, $qtLimit);

        $re = $this->utils->get_dados_id($returns);

        // echo "<pre>";
        // print_R('ESTOU CHEGANDO AQUI NO CONTROLLER!!!');
        // print_R($re);

        // die();

        $return_valores = $return->count_new_quantidade();

        $dados_parar = $return->busca_erros_eight();

        $pegar_dados_parados = $return->list_processo_parar($idProcesso, $qtLimit, false);

        $push_dados_process_die = $return->get_info_status_process();


        if (isset($pegar_dados_parados)) {
            $this->utils->get_dados_id($pegar_dados_parados);
        }

        if (isset($push_dados_process_die) && $push_dados_process_die) {
            $this->utils->treat_dados_die($push_dados_process_die);
        }

        ///pegos dentro da collection de paralizar
        $jobs_parados = $this->utilss->get_data_paralizar();

        if (isset($jobs_parados)) {
            $retorno_processo = $this->utils->process_paralisar($jobs_parados, $qtLimit);
        }

        $pasta = $this->arquivos_json->env_json('path_arquivos_info');

        if (isset($pasta)) {
            //envio para a pasta de arquivos para processarl
            $this->utils->open_json_dados($pasta);
        }

        echo "Minha pasta e: " . $pasta . "\n";

        if (isset($return_valores)) {
            $this->utils->contar_atualizar_valores($return_valores);
        }

        //finalizar jobs parados com o status 17 para gerarr o resultado 
        if (isset($dados_parar)) {
            echo "chamei o parar";
            $this->utils->process_finalizar_status_erros($dados_parar);
        }
        //vou percorrer para pegar o id e calcular o valorer correto;


        // # PEGO O QUE FOI FINALIZADO JÁ 
        $return_finish = $return->list_processo_modulo($idProcesso, $qtLimit);


        $returns_alert = $return->list_processo_qta_process($qtLimit);

        $result_idProcess = array_values(
            array_column(
                array_filter($returns, fn($row) => !empty($row['processo_id'])),
                'processo_id'
            )
        );


        if (empty($returns)) {
            echo "Nenhum dado encontrado!\n";
        }

        if (empty($returns_alert)) {

            echo "Nenhum dado encontrado\n";
        }


        if (empty($returns_modulos)) {

            echo "Nenhum dado encontrado\n";
        }


        $consult_modulos = [];


        if (!empty($return_finish)) {

            foreach ($return_finish as $key => $values_modulos) {


                $dados = $return->push_value_modulo(
                    $values_modulos['rede'],
                    $values_modulos['codcns'],
                    $values_modulos['data_cadastro'],
                    $values_modulos['data_finalizacao'],
                    null
                );

                if (!empty($dados)) {

                    $consult_modulos[] = [
                        'dados' => $dados,
                    ];

                    $consult_modulos['processo_id'] = $values_modulos['processo_id'];
                    $consult_modulos['valor_original'] = $values_modulos['valor_total'];
                }
            }
        }

        if (isset($consult_modulos) && !empty($consult_modulos)) {
            $this->utils->updados_modulos($consult_modulos);
        }

        $result_resposta = array_values(array_filter($returns_alert, function ($row) {
            return !empty($row['info']);
        }));

        if (isset($result_resposta)) {
            $list_dados = [];
            foreach ($result_resposta as $key => $values) {

                if ($values['qta_processar'] > 0) {

                    $list_dados = $return->list_processo_alert($values['processo_id'],  $values['qta_processar']);
                } else {


                    $return->finish_process_die($values['processo_id']);
                }
            }

            echo "<pre>";
            echo "minha variavel lista dados";

            print_r($list_dados);
            $re = $this->utils->get_dados_id($list_dados);
            echo "estou saindo aqui";
            // return $this->view('listar');
        }
    }

    public function teste()
    {


        try {
            // Instancia o Singleton da sua classe
            $mongo = MongoConect::getInstance();

            echo "Tentando pingar o MongoDB...<br>";

            if ($mongo->testarConexao()) {
                echo "✅ **Sucesso!** Sua classe conseguiu se conectar e autenticar no MongoDB.";
            } else {
                echo "❌ **Erro!** A classe instanciou, mas o comando de Ping falhou.";
            }
        } catch (\Exception $e) {
            echo "❌ **Erro crítico na inicialização:** " . $e->getMessage();
        }
    }
}
