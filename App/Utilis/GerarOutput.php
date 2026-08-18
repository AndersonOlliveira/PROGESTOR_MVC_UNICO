<?php

namespace App\Utilis;


use App\Utilis\Configs;
use App\Models\CapturaDadosJob;
use App\Models\CapturaDadosTransacoesJob;
use App\Models\RespostaPluginsTrasancao;

class GerarOutput
{


    protected $utils;
    protected $tratamento;

    protected $MontaJsonConfigEHeadersDaConsultas;
    protected $CapturaDadosJob;
    protected $CapturaDadosTransacoesJob;
    protected $CapturaRespostasPluginsTransacao;
    protected $filtros;
    public function __construct()
    {

        // $this->utils = new Instance();
        // require_once 'MontaJsonConfigEHeadersDaConsulta.php';
        // $this->MontaJsonConfigEHeadersDaConsultas = new MontaJsonConfigEHeadersDaConsulta();

        // require_once __DIR__ . '/../models/CapturaDadosJob.php';
        $this->CapturaDadosJob = new CapturaDadosJob();
        // //  $this->GravaTransacao = $this->utils = new GravaTransacao();

        // require_once __DIR__ . '/../models/CapturaDadosTransacoesJob.php';
        $this->CapturaDadosTransacoesJob = new CapturaDadosTransacoesJob();

        // require_once __DIR__ . '/../models/RespostaPluginsTrasancao.php';
        $this->CapturaRespostasPluginsTransacao = new RespostaPluginsTrasancao();

        // require_once __DIR__ . '/Configs.php';
        $this->filtros = new Configs();
    }


    public function generateOutputFiles($idJob)
    {


        $job = $this->CapturaDadosJob->execute($idJob);


        echo "<pre>";
        print_r($job);

        echo "acessei o resultado\n";
        $transacoes = $this->CapturaDadosTransacoesJob->execute($idJob);
        if ($transacoes) {

            $dir = $this->filtros->env('path_arquivos');

            //exec("rm -rf $dir/JOB_$idJob");
            //exec("rm $dir/JOB_$idJob.zip");

            if (!file_exists("$dir/JOB_$idJob.zip")) {

                $conteudoArquivoPrincipal = "";
                mkdir("$dir/JOB_$idJob/", 0755, true);

                $nomeArquivoPrincipal = "$dir/JOB_$idJob/SAIDA_PRINCIPAL__" . $job['nome_arquivo'];

                // $conteudoArquivoPrincipal .= "CPF/CNPJ;" . utf8_encode($job['header_arquivo']) . "\n";
                $conteudoArquivoPrincipal .= "CPF/CNPJ;" . self::garantirUtf8($job['header_arquivo']) . "\n";


                $tCount = 0;
                $plugins = array();
                $jobId = $idJob;
                foreach ($transacoes as $registro) {

                    if (trim($registro['resposta']) != "" && $registro['resposta'] != null) {
                        // $conteudoArquivoPrincipal .= utf8_encode($registro['resposta']) . "\n";
                        $conteudoArquivoPrincipal .= self::garantirUtf8($registro['resposta']) . "\n";
                    } else {
                        $conteudoArquivoPrincipal .= $registro['campo_aquisicao']  . ";\n";
                        // $conteudoArquivoPrincipal .= utf8_encode($registro['campo_aquisicao']) . ";\n";
                    }


                    // file_put_contents($nomeArquivoPrincipal, "\xEF\xBB\xBF");


                    if (($tCount % 1000) == 0 && $tCount > 0) {

                        $conteudoArquivoPrincipal = self::limpaConteudoArquivo($conteudoArquivoPrincipal);

                        file_put_contents($nomeArquivoPrincipal,  $conteudoArquivoPrincipal, FILE_APPEND);
                        unset($conteudoArquivoPrincipal);
                        $conteudoArquivoPrincipal = "";
                    }

                    $respPlugins = $this->CapturaRespostasPluginsTransacao->execute($registro['transacao_id']);
                    if ($respPlugins) {
                        foreach ($respPlugins as $resp) {

                            $nomeArquivoPlugin = "$dir/JOB_$jobId/SAIDA_PLUGIN_" . $resp['plugin'] . "__" . $job['nome_arquivo'];

                            if (!file_exists($nomeArquivoPlugin)) {
                                file_put_contents($nomeArquivoPlugin, "CPF/CNPJ;" . self::garantirUtf8($resp['header_arquivo'])  . "\n", FILE_APPEND); // grava header
                                $conteudoArquivoPlg[$resp['plugin']] = "";
                                $plugins[] = $resp['plugin'];
                            }

                            foreach ($respPlugins as $resp) {

                                $nomeArquivoPlugin = "$dir/JOB_$jobId/SAIDA_PLUGIN_" . $resp['plugin'] . "__" . $job['nome_arquivo'];

                                if (trim($resp['resposta']) != "" && $resp['resposta'] != null) {
                                    $conteudoArquivoPlg[$resp['plugin']] .= $resp['resposta'] . "\n";
                                }
                            }

                            $tCount++;
                        }

                        file_put_contents($nomeArquivoPrincipal, $conteudoArquivoPrincipal, FILE_APPEND);
                        if (count($plugins) > 0) {
                            foreach ($plugins as $plg) {

                                $nomeArquivoPlugin = "$dir/JOB_$jobId/SAIDA_PLUGIN_" . $plg . "__" . $job['nome_arquivo'];
                                file_put_contents($nomeArquivoPlugin, $conteudoArquivoPlg[$plg], FILE_APPEND);
                            }
                        }

                        // ZIP
                        // exec("cd $dir; zip JOB_$idJob.zip JOB_$idJob/* -P '" . $job['contrato'] . "';");
                    }
                }
                echo "<pre>";
                echo "MEU CONTEUDO\n";
                print_r($conteudoArquivoPrincipal);
            }

            return "Sucesso";
        }
    }


    public static function garantirUtf8($texto)
    {

        return mb_check_encoding($texto, 'UTF-8')
            ? $texto
            : mb_convert_encoding($texto, 'UTF-8', 'ISO-8859-1');
    }

    function limpaConteudoArquivo($string)
    {

        $string = preg_replace('/[^a-zA-Z0-9_ %\[\]\(\n)\.\;\(\)%&-]/s', '', $string);
        return $string;
    }
}
