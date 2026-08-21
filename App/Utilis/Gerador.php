<?php

namespace App\Utilis;

use Core\Model;
use Exception;
use DateTime;
use App\Utilis\Config;
use App\Models\CapturaDadosJob;
use App\Models\instance;
use App\Models\process;
use App\Models\CapturaDadosTransacoesJob;
use App\Models\CapturaPluginsDaConsulta;
use App\Models\GravaTransacao;
use App\Models\GravaRespostaPlugin;
use App\Models\GravaUpdateParalizar;
use App\Models\GravarUpdateDieProcess;
use App\Models\CapturaRedeLojaDoContrato;
use App\Models\CapturaCamposConsultas;
use App\Models\BuscaValorLotePorConsulta;
use App\Utilis\MontaJsonConfigEHeadersDaConsulta;



class Gerador
{

    protected $utils;
    protected $tratamento;

    protected $MontaJsonConfigEHeadersDaConsultas;
    protected $GravaTransacao;
    protected $GravaRespostaPlugin;
    protected $GravaUpdateParalizar;
    protected $teste;
    protected $filtros;
    protected $instance;

    protected $CapturaRedeLojaDoContrato;
    protected $CapturaCamposConsultas;
    protected $BuscaValorLotePorConsulta;
    protected $GravarUpdateDieProcess;
    protected $arquivos_json;

    protected $CapturaDadosTransacoesJob;
    protected $capturaDadosJob;


    public function __construct()
    {

        $this->utils = new instance();
        // require_once 'MontaJsonConfigEHeadersDaConsulta.php';
        $this->MontaJsonConfigEHeadersDaConsultas = new MontaJsonConfigEHeadersDaConsulta();

        // require_once __DIR__ . '/../models/GravaTransacao.php';
        $this->GravaTransacao = new GravaTransacao();


        $this->capturaDadosJob = new CapturaDadosJob();
        //  $this->GravaTransacao = $this->utils = new GravaTransacao();

        // require_once __DIR__ . '/../models/GravaRespostaPlugin.php';
        $this->GravaRespostaPlugin = new GravaRespostaPlugin();

        // require_once __DIR__ . '/../models/GravaUpdateParalizar.php';
        $this->GravaUpdateParalizar = new GravaUpdateParalizar();

        // require_once __DIR__ . '/../models/GravarUpdateDieProcess.php';
        $this->GravarUpdateDieProcess = new GravarUpdateDieProcess();

        // require_once __DIR__ . '/../models/process.php';
        $this->teste = new process();

        // require_once __DIR__ . '/../models/process.php';
        $this->filtros = new process();

        // require_once __DIR__ . '/../models/instance.php';
        $this->instance = new instance();

        // require_once __DIR__ . '/../models/CapturaRedeLojaDoContrato.php';
        $this->CapturaRedeLojaDoContrato = new CapturaRedeLojaDoContrato();
        // require_once __DIR__ . '/../models/CapturaCamposConsultas.php';
        $this->CapturaCamposConsultas = new CapturaCamposConsultas();
        // require_once __DIR__ . '/../models/BuscaValorLotePorConsulta.php';
        $this->BuscaValorLotePorConsulta = new BuscaValorLotePorConsulta();

        // require_once __DIR__ . '/../Utilis/Config.php';
        $this->arquivos_json = new Config();

        $this->CapturaDadosTransacoesJob = new CapturaDadosTransacoesJob();
    }

    public function generateOutputFiles($idJob)
    {

        $job = $this->capturaDadosJob->execute($idJob);

        // echo "<pre>";
        // print_r("lista com os JOBS");
        // print_R($job);

        $colunasEsperadas = $this->CapturaCamposConsultas->Consultation_description($job['campos_aquisicao']);


        // echo "<pre>";
        // print_r("lista com os colunasEsperadas\n");
        // print_R($colunasEsperadas);



        $transacoes = $this->CapturaDadosTransacoesJob->execute($idJob);


        if ($transacoes) {

            $dir = Config::env('path_arquivos');

            // exec("rm -rf $dir/JOB_$idJob");
            // exec("rm $dir/JOB_$idJob.zip");
            // print_R("pasta?");
            // print_R("$dir/JOB_$idJob.zip");
            // $arquivoZip = $dir . "/JOB_" . $idJob . ".zip";
            // $arquivoZips = $dir . "/JOB_" . $idJob . ".csv";

            // if (file_exists("$dir/JOB_$idJob.zip")) {
            // 	unlink("$dir/JOB_$idJob.zip");
            // }
            // if (file_exists("$dir/JOB_$idJob.csv")) {
            // 	unlink("$dir/JOB_$idJob.csv");
            // }
            // if (is_dir("$dir/JOB_$idJob")) {
            // 	// remove todos os arquivos dentro
            // 	array_map('unlink', glob("$dir/JOB_$idJob/*"));
            // 	rmdir("$dir/JOB_$idJob");
            // }
            // die();

            if (!file_exists("$dir/JOB_$idJob.zip")) {

                echo "<pre>";
                print_R("vou criar a pasta");

                $conteudoArquivoPrincipal = "";
                mkdir("$dir/JOB_$idJob/", 0755, true);

                $nomeArquivoPrincipal = "$dir/JOB_$idJob/ARQUIVO_CONSOLIDADO__" . $job['nome_arquivo'];

                // echo "<pre>";
                // print_R("conteudo\n");
                // print_R($nomeArquivoPrincipal);

                // $conteudoArquivoPrincipal .= "CPF/CNPJ;" . utf8_encode($job['header_arquivo']) . "\n";
                $conteudoArquivoPrincipal .= implode(';', $colunasEsperadas) . ";";

                $conteudoArquivoPrincipal .= $this->utils->garantirUtf8($job['header_arquivo']) . "\n";



                #ajuste para limpar as strings do header do arquivo
                $conteudoArquivoPrincipal = preg_replace('/[\d]+/', '', $conteudoArquivoPrincipal);

                //pra gerar um utf8
                file_put_contents($nomeArquivoPrincipal, "\xEF\xBB\xBF");

                $tCount = 0;
                $plugins = array();
                foreach ($transacoes as $registro) {

                    if (trim($registro['resposta']) != "" && $registro['resposta'] != null) {
                        // $conteudoArquivoPrincipal .= utf8_encode($registro['resposta']) . "\n";
                        $conteudoArquivoPrincipal .= $this->utils->garantirUtf8($registro['resposta']) . "\n";
                    } else {
                        $conteudoArquivoPrincipal .= $this->utils->garantirUtf8($registro['campo_aquisicao'])  . ";\n";
                        // $conteudoArquivoPrincipal .= utf8_encode($registro['campo_aquisicao']) . ";\n";
                    }

                    if (($tCount % 2000) == 0 && $tCount > 0) {

                        $conteudoArquivoPrincipal = $this->utils->limpaConteudoArquivo($conteudoArquivoPrincipal);

                        file_put_contents($nomeArquivoPrincipal, $conteudoArquivoPrincipal, FILE_APPEND);
                        unset($conteudoArquivoPrincipal);
                        $conteudoArquivoPrincipal = "";
                    }

                    $respPlugins = $this->CapturaRespostasPluginsTransacao->execute($registro['transacao_id']);
                    // echo "<pre>";
                    // print_R("respPlugins\n");
                    // print_R($respPlugins);


                    if ($respPlugins) {
                        foreach ($respPlugins as $resp) {
                            $nomeArquivoPlugin = "$dir/JOB_$idJob/SAIDA_PLUGIN_" . $resp['plugin'] . "__" . $job['nome_arquivo'];

                            if (!file_exists($nomeArquivoPlugin)) {
                                if ($resp['header_arquivo'] == '-' || trim($resp['header_arquivo']) == '') {
                                    $numCols = count(explode(';', $resp['resposta']));
                                    $resp['header_arquivo'] = implode(';', array_map(
                                        function ($i) {
                                            return "COLUNA{$i}";
                                        },
                                        range(1, $numCols)
                                    ));
                                }


                                $headerCols = array_map('trim', explode(';', trim($resp['header_arquivo'], ';')));
                                $cabecalhoFinal = array_merge($colunasEsperadas, $headerCols);
                                $linhaCabecalho = implode(';', $cabecalhoFinal) . "\n";
                                $linhaCabecalho = mb_convert_encoding($linhaCabecalho, 'UTF-8', 'Windows-1252');


                                if (!file_exists($nomeArquivoPlugin)) {
                                    file_put_contents($nomeArquivoPlugin, "\xEF\xBB\xBF"); // grava BOM
                                }

                                // Grava no arquivo
                                file_put_contents($this->utils->garantirUtf8($nomeArquivoPlugin), $linhaCabecalho, FILE_APPEND);
                                $conteudoArquivoPlg[$resp['plugin']] = "";
                                $plugins[] = $resp['plugin'];
                            }
                        }



                        foreach ($respPlugins as $resp) {
                            $nomeArquivoPlugin = "$dir/JOB_$idJob/SAIDA_PLUGIN_" . $resp['plugin'] . "__" . $job['nome_arquivo'];
                            if (trim($resp['resposta']) != "" && $resp['resposta'] != null) {
                                if (!isset($conteudoArquivoPlg[$resp['plugin']])) {
                                    $conteudoArquivoPlg[$resp['plugin']] = "";
                                }
                                $conteudoArquivoPlg[$resp['plugin']] .= $this->utils->garantirUtf8($resp['resposta']) . "\n";
                            }
                        }
                    }


                    $tCount++;
                }

                file_put_contents($nomeArquivoPrincipal, $conteudoArquivoPrincipal, FILE_APPEND);

                if (count($plugins) > 0) {
                    foreach ($plugins as $plg) {
                        $nomeArquivoPlugin = "$dir/JOB_$idJob/SAIDA_PLUGIN_" . $plg . "__" . $job['nome_arquivo'];
                        if (isset($conteudoArquivoPlg[$plg])) {
                            file_put_contents($nomeArquivoPlugin, $conteudoArquivoPlg[$plg], FILE_APPEND);
                        }
                    }
                }
            }

            // ZIP
            // exec("cd $dir; zip JOB_$idJob.zip JOB_$idJob/* -P '" . $job['contrato'] . "';");
        }

        // return "Sucesso";
    }
}
