<?php

use App\Models\process;
use App\Models\GravaTransacao;
use App\Models\GravaRespostaPlugin;
use App\Models\GravaUpdateParalizar;
use App\Models\GravarUpdateDieProcess;
use App\Utilis\MontaJsonConfigEHeadersDaConsulta;


class Tratamentos
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


    public function __construct()
    {

        // $this->utils = new Instance();
        // require_once 'MontaJsonConfigEHeadersDaConsulta.php';
        $this->MontaJsonConfigEHeadersDaConsultas = new MontaJsonConfigEHeadersDaConsulta();

        // require_once __DIR__ . '/../models/GravaTransacao.php';
        $this->GravaTransacao = new GravaTransacao();
        //  $this->GravaTransacao = $this->utils = new GravaTransacao();

        // require_once __DIR__ . '/../models/GravaRespostaPlugin.php';
        $this->GravaRespostaPlugin = new GravaRespostaPlugin();

        // require_once __DIR__ . '/../models/GravaUpdateParalizar.php';
        $this->GravaUpdateParalizar = new GravaUpdateParalizar();

        // require_once __DIR__ . '/../models/GravarUpdateDieProcess.php';
        $this->GravarUpdateDieProcess = new GravarUpdateDieProcess(); // MEXER

        // require_once __DIR__ . '/../models/process.php';
        $this->teste = new process();

        // require_once __DIR__ . '/../models/process.php';
        $this->filtros = new process();

        // require_once __DIR__ . '/../models/instance.php';
        $this->instance = new instance();

        require_once __DIR__ . '/../models/CapturaRedeLojaDoContrato.php';
        $this->CapturaRedeLojaDoContrato = new CapturaRedeLojaDoContrato();
        require_once __DIR__ . '/../models/CapturaCamposConsultas.php';
        $this->CapturaCamposConsultas = new CapturaCamposConsultas();
        require_once __DIR__ . '/../models/BuscaValorLotePorConsulta.php';
        $this->BuscaValorLotePorConsulta = new BuscaValorLotePorConsulta();

        require_once __DIR__ . '/../Utilis/Config.php';
        $this->arquivos_json = new Config();
    }


    public function updados_modulos($dados)
    {


        echo '<pre>';


        echo  "meus dados enviados\n";
        print_r($dados);
        die();


        $valor = 0;        ## somo os valores e gero um valos
        foreach ($dados as $values) {
            #verifico se e um array 
            if (!is_array($values) || !isset($values['dados'])) {
                continue;
            }
            foreach ($values['dados'] as $dados_modulo) {
                $valor += $dados_modulo['valor_total'];
            }
        }

        $valor = number_format($valor, 2, '.', '');
        $dados['valor_geral'] = $valor;
        $dados['data_atualizacao'] = date('Y-m-d H:i:s');

        #achado o valor, realizo o up  dentro da tabela

        // $this->instance->up_valor_modulos($dados['processo_id'], $dados['valor_original'], $dados['valor_geral']);
        #insiro pra o mongo
        $info_mongo = $this->instance->up_valor_modulos($dados);

        echo "<pre>";

        print_R($info_mongo);

        if ($info_mongo) {
            echo "estou chamando para atualizar no postgres\n";
            print_R($dados);

            echo "estou chamando para atualizar no postgres\n";
            #vou atualizar o postgres
            $this->filtros->up_valor_modules($dados);
        }
    }

    public function get_dados_id($dados)
    {

        //pesquiso para primeiro para saber o resposta esta preenchido se tive faco o up para o mongo
        $result_resposta = array_values(array_filter($dados, function ($row) {
            return !empty($row['resposta_json']);
        }));


        //vou procurar os dados 
        // foreach ($dados as $key => $values) {

        // 	$dados[$key]['resultado'] = $this->utils->findById($values['processo_id'], $values['transacao_id']);
        // }


        $start = hrtime(true);

        $resultadosMongo = $this->utils->findByMultiple($dados);

        echo "<pre>";
        echo "meu resultado\n";
        print_r($resultadosMongo);


        echo "<pre>";
        echo "ESTOU VINDO NA MINHA GET DADOS ID\n";

        print_r($dados);

        $indexado = [];

        foreach ($resultadosMongo as $doc) {

            if (isset($doc->transacao_id)) {
                $chave = $doc->id_processo . '_' . $doc->transacao_id;
            } else {
                $chave = (string) $doc->id_processo;
            }

            $indexado[$chave] = $doc;
        }

        foreach ($dados as $key => $values) {

            if (preg_match('/^[a-f0-9]{24}$/i', $values['processo_id'])) {
                $chave = $values['processo_id'];
            } else {
                $chave = $values['processo_id'] . '_' . $values['transacao_id'];
            }

            $dados[$key]['resultado'] = $indexado[$chave] ?? null;
        }

        $end = hrtime(true);
        $executionTime = ($end - $start) / 1e9; // Converte para segundos
        echo "Tempo: " . $executionTime . " segundos";


        // die();
        $result_empty = array_values(array_filter($dados, function ($row) {
            return !empty($row['resultado']);
        }));

        if (isset($result_empty) || isset($result_resposta)) {

            self::up_resultado($result_empty, $result_resposta);
        } else {

            echo 'Campos Json Resposta esta vazio';
        }

        $retorno = self::tratamento_dados($dados);
    }

    public function up_resultado($dados = null, $resposa_json = null)
    {

        if (isset($dados)) {
            foreach ($dados as $result) {

                $this->filtros->filtros_data($result['transacao_id']);
            }
        }

        if (isset($resposa_json)) {
            $lista_up = [];
            foreach ($resposa_json as $result) {
                $lista_up[] = $this->filtros->filtros_data($result['transacao_id']);
            }
            $this->utils->insert($lista_up);
        }
    }

    public function tratamento_dados($row_data)
    {
        echo "<pre>";

        print_r($row_data);

        echo "tenho o resultado do tratamento dos dados\n";

        // die();



        $dados_filtrados = array_values(array_filter($row_data, function ($row) {

            return !empty($row['resultado']);
        }));



        echo "<pre>";
        echo "dados_processos\n";
        echo "dados_processos com a mensagem\n";
        print_r($dados_filtrados);

        $cacheCns = [];
        $transacoes = [];
        $GrespostaPlugin = [];
        $GtransacaoSuceso = [];
        $sucessTruegravaTransacao = [];
        $inicio = microtime(true);

        foreach ($dados_filtrados as $r) {

            if (!empty($r['resultado'])) {

                $values = $r['resultado']; //  pega o objeto inteiro

                $camposAquisicao = $values->campo_aquisicao ?? null;
                $jsonResposta    = $values->resposta_json ?? null;

                $transacoes[] = [
                    'processo_id' => $r['processo_id'],
                    'camposAquisicao' => $camposAquisicao,
                    'status' => 5,
                    'sucesso' => null,
                    'resposta' => null,
                    'json_resposta' => $jsonResposta
                ];

                $plgsConfigurados = self::getPluginsConfigDB($r['configuracao_json']);

                $jsonObjProscore = self::getObjectJson($jsonResposta);

                $linhaSaidaHorizontal = "";
                $success = false;

                if ($jsonObjProscore && !empty($jsonObjProscore->registro)) {

                    $success = true;

                    $registros = self::getRegistrosPlugins($jsonObjProscore, $plgsConfigurados);

                    foreach ($registros as $plugin => $arrayValues) {

                        $configPlugin = self::getConfObjectByPluginDB($r['configuracao_json'], $plugin);

                        if (!$configPlugin) {
                            continue;
                        }

                        if ($configPlugin->separar) {

                            foreach (self::montaLinhaRegistroVertical($arrayValues, $configPlugin) as $linhaSaidaVertical) {

                                if ($linhaSaidaVertical != "") {

                                    $GrespostaPlugin[] = [
                                        'plugin' => $plugin,
                                        'resposta' => $camposAquisicao . ";" . $linhaSaidaVertical,
                                        'transacaoId' => $values->transacao_id,
                                        'header' => '-'
                                    ];
                                }
                            }
                        } else {

                            if ($linhaSaidaHorizontal == "") {
                                $linhaSaidaHorizontal .= $camposAquisicao;
                            }

                            $linhaSaidaHorizontal .= ";" . self::montaLinhaRegistroHorizontal($arrayValues, $configPlugin);
                        }
                    }
                }

                if (!$success) {

                    $GtransacaoSuceso[] = [
                        'processo_id' => $r['processo_id'],
                        'camposAquisicao' => $camposAquisicao,
                        'status' => 3,
                        'sucesso' => 0,
                        'resposta' => null,
                        'respostaJson' => null
                    ];
                } else {

                    if ($linhaSaidaHorizontal != "") {

                        $sucessTruegravaTransacao[] = [
                            'processo_id' => $r['processo_id'],
                            'camposAquisicao' => $camposAquisicao,
                            'status' => 3,
                            'sucesso' => 1,
                            'resposta' => $linhaSaidaHorizontal,
                            'respostaJson' => null
                        ];
                    }
                }
            }
        }

        echo "<pre>";

        echo "<br>transacoes<br>";
        print_r($transacoes);
        echo "<pre>";

        echo "<br>transacoes<br>";
        print_r($GrespostaPlugin);
        echo "<pre>";

        echo "<br>GtransacaoSuceso<br>";
        print_r($GtransacaoSuceso);
        echo "<br>sucessTruegravaTransacao<br>";
        print_r($sucessTruegravaTransacao);




        if (!empty($transacoes)) {
            $this->GravaTransacao->insertBatch($transacoes);
        }

        if (!empty($GrespostaPlugin)) {
            $this->GravaRespostaPlugin->insert_all_Respost_pluglin($GrespostaPlugin);
        }

        if (!empty($GtransacaoSuceso)) {
            $this->GravaTransacao->insertBatch($GtransacaoSuceso);
        }

        if (!empty($sucessTruegravaTransacao)) {
            $this->GravaTransacao->insertBatch($sucessTruegravaTransacao);
        }
    }
    public function tratamento_dados_old($row_data)
    {
        $dados_filtrados = array_values(array_filter($row_data, function ($row) {

            return !empty($row['resultado']);
        }));



        echo "<pre>";
        echo "dados_processos\n";
        echo "dados_processos com a mensagem\n";
        print_r($dados_filtrados);





        $cacheCns = [];
        $transacoes = [];
        $GrespostaPlugin = [];
        $GtransacaoSuceso = [];
        $sucessTruegravaTransacao = [];
        $inicio = microtime(true);

        foreach ($dados_filtrados as $r) {

            $cod = $r['codcns'];

            if (!empty($r['resultado'])) {

                foreach ($r['resultado'] as $values) {

                    list($camposAquisicao, $jsonRespostas) = [$values->campo_aquisicao, null];

                    $transacoes[] = [
                        'processo_id' => $r['processo_id'],
                        'camposAquisicao' => $camposAquisicao,
                        'status' => 5,
                        'sucesso' => null,
                        'resposta' => null,
                        'json_resposta' => $jsonRespostas ?? null,
                    ];
                    // $this->GravaTransacao->execute($r['processo_id'], $camposAquisicao, 5, 0, null, $jsonRespostas);


                    $plgsConfigurados = self::getPluginsConfigDB($r['configuracao_json']); // array com codigos dos plugins da configuracao 

                    list($camposAquisicao, $jsonResposta) = [$values->campo_aquisicao,  $values->resposta_json];

                    $jsonObjProscore = self::getObjectJson($jsonResposta); // object da resposta premium json

                    $linhaSaidaHorizontal = ""; // linha arquivo principal

                    $success = false;
                    if ($jsonObjProscore) {
                        if ($jsonObjProscore->registro) {

                            $success = true;

                            // PARA CADA REGISTRO/PLUGIN do JSON PREMIUM
                            $registros = self::getRegistrosPlugins($jsonObjProscore, $plgsConfigurados);


                            foreach ($registros as $plugin => $arrayValues) {

                                $configPlugin = self::getConfObjectByPluginDB($r['configuracao_json'], $plugin);

                                if (!$configPlugin) { // config nao encontrada para o plugin
                                    continue;
                                }

                                if ($configPlugin->separar) { // SAIDA ARQUIVO A PARTE - GRAVA CADA LINHA

                                    if (!isset($cacheCns[$cod])) {

                                        $conf = $this->MontaJsonConfigEHeadersDaConsultas->execute($cod);
                                        $cacheCns[$cod] = is_array($conf) ? $conf : [];
                                    }

                                    $confCns = $cacheCns[$cod];

                                    $confCns = is_array($confCns) ? $confCns : [];
                                    $header = isset($confCns['header_' . $plugin]) ? $confCns['header_' . $plugin] : '-';


                                    $linhasSaidaVertical = self::montaLinhaRegistroVertical($arrayValues, $configPlugin); // array com linhas de saida

                                    foreach ($linhasSaidaVertical as $linhaSaidaVertical) { // PARA CADA OCORRENCIA do PLUGIN

                                        if ($linhaSaidaVertical != "") {


                                            // GRAVA RESPOSTA PLUGIN TRANSACAO
                                            $GrespostaPlugin[] = [
                                                'plugin' => $plugin,
                                                'resposta' => $camposAquisicao . ";" . $linhaSaidaVertical,
                                                'transacaoId' => $values->transacao_id,
                                                'header' => $header
                                            ];


                                            // $this->GravaRespostaPlugin->execute($plugin, $camposAquisicao . ";" . $linhaSaidaVertical, $values->transacao_id, $header);
                                        }
                                    }
                                } else { // concatena na linha do arquivo principal

                                    // MONTA LINHA MAIN
                                    if ($linhaSaidaHorizontal == "") {
                                        $linhaSaidaHorizontal .= "$camposAquisicao"; // inicializa linha com campos de aquisicao
                                    }

                                    $linhaSaidaHorizontal .= ";" . self::montaLinhaRegistroHorizontal($arrayValues, $configPlugin);
                                }
                            }
                        }
                    }

                    if (!$success) { // saida de ERRO

                        // GRAVA RESPOSTA TRANSACAO SUCESSO

                        $GtransacaoSuceso[] = [
                            'processo_id' => $r['processo_id'],
                            'camposAquisicao' => $camposAquisicao,
                            'status' => 3,
                            'sucesso' => 0,
                            'resposta' => null,
                            'respostaJson' => null
                        ];

                        // $this->GravaTransacao->execute($r['processo_id'], $camposAquisicao, 3, 0, null, null);

                    } else {

                        // SAIDA PRINCIPAL

                        // linha arquivo main preenchida
                        if ($linhaSaidaHorizontal != "") {

                            // GRAVA RESPOSTA TRANSACAO SUCESSO
                            $sucessTruegravaTransacao[] = [
                                'processo_id' => $r['processo_id'],
                                'camposAquisicao' => $camposAquisicao,
                                'status' => 3,
                                'sucesso' => 1,
                                'resposta' => $linhaSaidaHorizontal,
                                'respostaJson' => null

                            ];

                            // $this->GravaTransacao->execute($r['processo_id'], $camposAquisicao, 3, 1, $linhaSaidaHorizontal, $jsonResposta);
                        }
                    }

                    $linhaSaidaHorizontal = ""; // zera linha
                }
            } else {
                // echo "<br>***<br>";
            }
        }



        if (!empty($transacoes)) {
            $this->GravaTransacao->insertBatch($transacoes);
        }

        if (!empty($GrespostaPlugin)) {
            $this->GravaRespostaPlugin->insert_all_Respost_pluglin($GrespostaPlugin);
        }

        if (!empty($GtransacaoSuceso)) {
            $this->GravaTransacao->insertBatch($GtransacaoSuceso);
        }

        if (!empty($sucessTruegravaTransacao)) {
            $this->GravaTransacao->insertBatch($sucessTruegravaTransacao);
        }
    }

    function getConfObjectByPluginDB($configuracaoJson, $cod)
    {

        $confObj = false;
        try {
            $confObj = json_decode($configuracaoJson);
        } catch (Exception $e) {
            echo " *********** ERR01: ERRO AO ABRIR ARQUIVO DE CONFIGURACAO *********** ";
            die();
        }

        if ($confObj) {
            foreach ($confObj as $plugin) {
                if (intval($cod) == intval($plugin->plugin)) {
                    return $plugin;
                }
            }
        }

        //echo " *********** ERR02: CONFIGURACAO NAO ENCONTRADA PARA PLUGIN $cod *********** ";
        //die();
        return false;
    }

    function getObjectJson($json)
    {
        if ($json) {
            if (trim($json) != "" and trim($json) != "{{{### ###}}}") {
                // padrao json
                if ((preg_match("/^(\{|\[)/", $json)) and (preg_match("/(\}|\])$/", $json))) {
                    $object = json_decode($json);
                    return $object;
                }
            }
        }
        return false;
    }

    function getPluginsConfigDB($configuracaoJson)
    {
        $result = array();

        $confObj = false;
        try {
            $confObj = json_decode($configuracaoJson);
        } catch (Exception $e) {
            echo " *********** ERR01: ERRO AO ABRIR ARQUIVO DE CONFIGURACAO *********** ";
            die();
        }

        if ($confObj) {
            foreach ($confObj as $plugin) {
                $result[] = $plugin->plugin;
            }
        }

        return $result;
    }
    /**
     * Retorna array com registros do json premium separado pela chave do plugin  
     * 
     * @param object $obj
     * @param array $arrayPluginsConf
     * @return array
     */
    function getRegistrosPlugins($obj, $arrayPluginsConf)
    {

        $return = array();

        // inicializa array de retorno com o codigo de cada plugin da configuracao
        foreach ($arrayPluginsConf as $codPlgConf) {
            $return[$codPlgConf] = array(); // para que os registros que não estejam no retorno premium sejam incluidos com campos vazios e não quebre o layout
        }

        // para cada registro inclui no array de retorno
        foreach ($obj->registro as $registro) {

            if (!isset($return[$registro->numero_plugin])) {
                $return[$registro->numero_plugin] = array();
            }
            $return[$registro->numero_plugin][] = $registro;
        }
        return $return;
    }
    /**
     * Monta linha do registro de saida horizontal
     * 
     * @param array $arrValores
     * @param object $configuracao
     * @param int $numeroPlg
     * @return string
     */
    function montaLinhaRegistroHorizontal($arrValores, $configuracao)
    {

        $linha = "";

        $i = 0;
        foreach ($arrValores as $retPlg) { // cada ocorrencia do registro/plugin

            $retPlg = self::transformaArrayPlgEmSimples($retPlg);

            // inclui campos na linha de acordo com a configuracao
            foreach ($configuracao->campos as $indice) {

                if (isset($retPlg[$indice])) {

                    $valor = preg_replace("/\;/", " ", $retPlg[$indice]); // limpa ponto-e-virgula de valores

                } else {
                    $valor = '';
                }
                $linha .= $valor . ";";
            }
            $i++;

            if ($configuracao->ocorrencias == $i) { // limite de ocorrencias atingido
                break; // para de gravar
            }
        }
        $linha = preg_replace("/;$/", "", trim($linha)); // retira ponto-e-virgula extra do final

        // completa quantidade de campos na linha de acordo com a configuracao
        $qtCamposLinha = count(explode(";", $linha));
        $qtConfigurada = $configuracao->ocorrencias * count($configuracao->campos);
        if ($qtCamposLinha < $qtConfigurada) {
            $qtIncluir = ($qtConfigurada - $qtCamposLinha);
            $linha = self::incluiCamposVazios($linha, $qtIncluir);
        }

        return $linha;
    }
    /**
     * Monta linha do registro de saida vertical (arquivo a parte)
     * 
     * @param array $arrValores
     * @param object $configuracao
     * @param int $numeroPlg
     * @return array
     */
    function montaLinhaRegistroVertical($arrValores, $configuracao)
    {

        $linha = "";



        $i = 0;
        foreach ($arrValores as $retPlg) { // cada ocorrencia do registro/plugin

            $retPlg = self::transformaArrayPlgEmSimples($retPlg);





            // inclui campos na linha de acordo com a configuracao
            foreach ($configuracao->campos as $indice) {

                if (isset($retPlg[$indice])) {

                    $valor = preg_replace("/\;/", " ", $retPlg[$indice]); // limpa ponto-e-virgula de valores

                } else {
                    $valor = '';
                }
                $linha .= $valor . ";";
            }
            $i++;

            if ($configuracao->ocorrencias == $i) { // limite de ocorrencias atingido
                break; // para de gravar
            }

            $linha = preg_replace("/;$/", "|", trim($linha)); // delimita linhas por pipe
        }
        $linha = preg_replace("/\|$/", "", trim($linha)); // retira pipe extra do final

        return explode("|", $linha);
    }
    /**
     * Transforma o array com keys do json de retorno do registro em array simples sem keys
     * 
     * @param array $registro
     * @return array[]
     */
    function transformaArrayPlgEmSimples($registro)
    {
        $camposPlg = array();
        foreach ($registro as $campoPlg) {
            $camposPlg[] = $campoPlg;
        }
        return $camposPlg;
    }
    /**
     * Inclui campos vazios (;) na string da linha
     *  
     * @param string $linha
     * @param int $qtd
     * @return string
     */
    function incluiCamposVazios($linha, $qtd)
    {

        for ($i = 0; $i < $qtd; $i++) {
            $linha .= ";";
        }
        return $linha;
    }


    public function contar_atualizar_valores($dados)
    {
        $valorTotal = 0;

        foreach ($dados as $key => $values) {

            $redeLoja = $this->CapturaRedeLojaDoContrato->execute($values['contrato']);

            list($valorLoteConsulta, $modulo) =
                $this->BuscaValorLotePorConsulta->calcula(
                    $values['codcns'],
                    $redeLoja['rede'],
                    $values['qtd_registros']
                );
            /**VERSAO PRÉ PAGO */
            /*list($valorLoteConsulta, $modulo) =
				$this->BuscaValorLotePorConsulta->calcula(
					$values['codcns'],
					$redeLoja['rede'],
					$values['qtd_registros'],
					$values['contrato']
				);

				*/

            $dados[$key]['new_valor'] = $valorLoteConsulta;

            $valorBanco = (int) floatval($values['valor_total']);
            $novoValor  = (int) $valorLoteConsulta;


            if ($novoValor > $valorBanco) {

                // var_dump($novoValor > $valorBanco . ' MEU ID PARA SER ALTERADO ' . $values['processo_id']);

                $this->filtros->atualizarValorJobs($values['processo_id'], $values['contrato'], $novoValor);
            }
        }

        $valorTotal += $valorLoteConsulta;


        $dados['valor_total_geral'] = $valorTotal;
    }


    public function open_json_dados($pasta)
    {
        if (is_dir($pasta)) {

            $dados = scandir($pasta);
            $dados = array_diff($dados, ['.', '..']);


            $dados_a_manipular = [];
            foreach ($dados as $k =>  $arquivos) {

                $filePath = $pasta . DIRECTORY_SEPARATOR . $arquivos;
                if ($arquivos == 'meu_arquivo.json') {

                    $filePath_destino = $pasta . DIRECTORY_SEPARATOR . $arquivos;

                    echo "Tamanho: " . filesize($filePath_destino) . " bytes";
                    if (file_exists($filePath_destino)) {

                        echo "<pre>";
                        echo "tenho o resultado do caminho do arquivossss\n";

                        $conteudo = file_get_contents($filePath_destino);

                        $retorn = $this->instance->inset_json_dados($conteudo, $arquivos);

                        echo "tenho o retorno do insert json meu arquivo\n";
                        print_r($retorn);
                    }
                } else if ($arquivos == 'infoReprocess.json') {

                    $filePath_destino = $pasta . DIRECTORY_SEPARATOR . $arquivos;

                    echo "Tamanho: " . filesize($filePath_destino) . " bytes";
                    if (file_exists($filePath_destino)) {

                        echo "<pre>";
                        echo "tenho o resultado do caminho do arquivossss\n";

                        $conteudo = file_get_contents($filePath_destino);


                        $retorn = $this->instance->inset_json_dados($conteudo, $arquivos);
                        echo "tenho o retorno do insert json infoReproess\n";
                        print_r($retorn);
                    }
                }
            }
        }
    }

    public function process_paralisar($dadosP, $qtLimit)
    {
        $dados_localizado = [];
        $pegarDadosfinalizar = [];





        foreach ($dadosP as $key => $values) {

            if (isset($values->paralisado) && !isset($values->data_finalizacao)) {

                if ($values->id_processo) {
                    $prazoMaximos = $this->filtros->get_limit_day_contrato($values->contrato);
                    $prazoMaximo =     2;

                    if (empty($values->data)) {
                        continue;
                    }

                    $data_paralisacao = new DateTime($values->data);

                    // Loop para adicionar a quantidade X de dias úteis para considierar dias ulteis
                    // for ($i = 0; $i < $prazoMaximo; $i++) {
                    // 	$data_paralisacao->modify('+1 weekday');
                    // }

                    // echo "<pre>";
                    // echo "meu prazo maximo\n ";
                    // echo "meu prazo maximo" . $prazoMaximos;

                    // print_r($prazoMaximos);

                    if ($prazoMaximos === false) {
                        continue;
                    }

                    $data_paralisacao->modify('+' . $prazoMaximos .  'days');


                    echo "Início: " . $values->data . "\n";
                    echo "Prazo final (" . $prazoMaximos . " dias úteis): " . $data_paralisacao->format('d/m/Y H:i:s') .  "\n";

                    $msg = '';

                    // Comparação com a data atual para encerrar o trabalho
                    $hoje = new DateTime();
                    if ($hoje > $data_paralisacao) {
                        echo "STATUS: ENCERRAR TRABALHO (Prazo excedido) " . $values->id_processo .  "\n";


                        $dadosP[$key]->finalizar = true;

                        $dados_localizado[] = $dadosP[$key];
                    }
                }
            } //final do if inicial

            //FINALIZAR OS DADOS 

            $pegarDadosfinalizar[] = $this->filtros->count_process_finalizado_paralizado($values->id_processo);
        }


        // echo "<pre>";
        // echo "lista de dados paralizados\n";
        // print_r($pegarDadosfinalizar);

        // die();

        $valorTotal_auxilar = 0;
        //aqu vou trocar os status
        foreach ($pegarDadosfinalizar as $dados => $value) {

            //mensagem esta vindo com o valor de 0 sendo assim náo esta passando aqui! e  esta sendo salvo no banco o valor de 0
            if ($value['total'] == $value['finalizados'] && ($value['mensagem_alerta'] === null || $value['mensagem_alerta'] === '')) {
                $redeLoja = $this->CapturaRedeLojaDoContrato->execute($value['contrato']);

                list($valorLoteConsulta, $retornoCalculo) = $this->BuscaValorLotePorConsulta->calcula($value['consultas'], $redeLoja['rede'], $value['total'], $value['contrato']);
                $valorTotal_auxilar = +$valorLoteConsulta;

                $result_alter_ = $this->filtros->alter_valores_process_paralizar($value['processo_id'], $valorTotal_auxilar);

                $dados_atualizar = [
                    'id_processo' => (string)$value['processo_id'],
                    'processo_finalizado' => 'Jobs interrompidos pelo sistema pois o prazo de ' . $prazoMaximos  . ' dia foi ultrapassado na data de',
                    'data_finalizacao' =>  $hoje,
                    'valor_job' => 'valor atualizado do job  para ' . $valorTotal_auxilar
                ];


                $retorno_up_mongo =  $this->utils->insert_all_paralizar(json_encode($dados_atualizar));

                if ($retorno_up_mongo) {
                    echo "<pre>";
                    echo "SUCESSO !\n";

                    print_R($retorno_up_mongo);
                } else {
                    echo "<pre>";
                    echo "INSUCESSO !\n";
                    print_R($retorno_up_mongo);
                }


                // if (isset($result_alter_)) {
                // 	echo "<pre>";
                // 	echo "Primeiro retorno do Atualizar se tiver dados ou erro\n";
                // 	print_r($result_alter_);
                // }
            }
        }

        $retorno_dados_paralizados = [];
        $quantidade_dados_paralizados_sucessos = [];


        echo "<pre>";
        echo "Segundo retorno do dados_localizado se tiver dados ou erro\n";
        print_r($dados_localizado);

        // die();


        foreach ($dados_localizado as $chave => $valores) {
            // $qtLimit = 1;
            echo "<pre>";
            echo "Terceiro passo retorno do dados_localizado se tiver dados ou erro\n";

            $retorno_dados_paralizados = $this->filtros->list_processo($valores->id_processo, $qtLimit, true);
            echo "<pre>";
            echo "Quarto passo retorno do dados_localizado se tiver dados ou erro\n";

            var_dump($retorno_dados_paralizados);

            $lista_dados_paralizados = $this->filtros->lista_data_paralisados($valores->id_processo);
            //pego os processo que esta com o status 0 para trocar para 17

            echo "<pre>";
            echo "Quinto passo retorno do lista_dados_paralizados se tiver dados ou erro\n";

            var_dump($lista_dados_paralizados);


            if (isset($retorno_dados_paralizados) && !empty($retorno_dados_paralizados)) {

                self::get_dados_id($retorno_dados_paralizados);

                $quantidade_dados_paralizados_sucessos[] = $this->filtros->count_process_finalizado_paralizado($valores->id_processo);

                echo "<pre>";
                echo "Sexto passo retorno do quantidade_dados_paralizados_sucessos se tiver dados ou erro\n";

                var_dump($quantidade_dados_paralizados_sucessos);
            }
        }



        if (!empty($lista_dados_paralizados) && isset($lista_dados_paralizados)) {

            $retorno_update = $this->GravaUpdateParalizar->insertBatch($lista_dados_paralizados);
        }

        if (isset($quantidade_dados_paralizados_sucessos) && !empty($quantidade_dados_paralizados_sucessos)) {
            $valorTotal = 0;

            echo "<pre>";

            print_r('SAINDO DENTRO DO FOREACH');

            var_dump($quantidade_dados_paralizados_sucessos);





            foreach ($quantidade_dados_paralizados_sucessos as $valores_busca => $val) {

                echo "<pre>";
                echo "o que esta saindo aquui no vaal!!!\n";

                print_r($val);

                if ($val['total'] == $val['finalizados'] && ($val['mensagem_alerta'] === null || $val['mensagem_alerta'] === '')) {

                    $redeLoja = $this->CapturaRedeLojaDoContrato->execute($val['contrato']);
                    list($valorLoteConsulta, $retornoCalculo) = $this->BuscaValorLotePorConsulta->calcula($val['consultas'], $redeLoja['rede'], $val['total'], $val['contrato']);
                    $valorTotal = +$valorLoteConsulta;
                    if ($val['mensagem_alerta'] != 1) {
                        $result_alter = $this->filtros->alter_valores_process_paralizar($val['processo_id'], $valorTotal);
                    } else if ($val['mensagem_alerta'] == "" || $val['mensagem_alerta'] == null) {
                        $result_alter = $this->filtros->alter_valores_process_paralizar($val['processo_id'], $valorTotal);
                    }


                    // if (isset($result_alter)) {

                    $dados_atualizar = [
                        'id_processo' => (string)$val['processo_id'],
                        'processo_finalizado' => 'Jobs interrompidos pelo sistema pois o prazo de ' . $prazoMaximos  . ' dia foi ultrapassado na data de',
                        'data_finalizacao' =>  $hoje,
                        'valor_job' => 'valor atualizado do job  para ' . $valorTotal
                    ];


                    $retorno_up_mongo =  $this->utils->insert_all_paralizar(json_encode($dados_atualizar));

                    if ($retorno_up_mongo) {
                        echo "<pre>";
                        echo "SUCESSO !\n";

                        print_R($retorno_up_mongo);
                    } else {
                        echo "<pre>";
                        echo "INSUCESSOSUCESSO !\n";
                        print_R($retorno_up_mongo);
                    }
                }
                // }
            }
        }

        //vou atualizar o valor correto com o que tiver de 3 ou 12 que teve sucesso na consulta
    }

    public function process_finalizar_status_erros($dados)
    {

        echo "<pre>";

        print_R('estou chegando aqui');


        $somatar = 0;

        foreach ($dados as $key => $valores) {

            // echo "<pre>";

            // print_r($valores);
            $somar =  $valores['qtd_registros_processado'] + $valores['qtd_status_egth'];

            if ($somar == $valores['qtd_registros']) {

                echo "<pre>";
                echo "valores a ser processado\n";

                print_r($valores);

                //localizado vou realizar o update na base 
                $resultado = $this->filtros->up_status_eight($valores);
                // echo "<pre>";
                // echo "meu resultado vindo aqui?\n";

                // print_r($resultado);


                if (isset($resultado) && $resultado['status'] == 2) {


                    //realizo o update para true dentro do processo para finalizar o job e gerar o resultado.s
                    $result_up = $this->filtros->up_status_finish_eight($valores['processo_id']);
                }
            }
        }
    }


    // public function process_finalizar_status_parar_processos($dados_enviados)
    // {

    //    $dados_para_alterar = [];

    // 	foreach ($dados_enviados as $key => $values) {

    // 		if ($values['status'] == 12) {
    // 			$contar = 100;

    // 			$returns =  $this->filtros->list_processo_parar($values['processo_id'], $contar, false);

    // 			if (isset($returns)) {
    // 				$re = self::get_dados_id($returns);
    // 			}
    // 		}

    // 		if($values['status'] >)
    // 	}
    // }

    public function treat_dados_die($dados)
    {

        $idBusca = [];

        foreach ($dados as $key => $die_process) {

            echo  "<pre>";
            echo  "MEU STATUS 12";
            print_r($die_process['qta_processar_status']);
            //$die_process['qta_processar_status']
            //varialvel acima recebe o status 12 assim que o valor chegar em 0, ele vai fazer o processo para pegar o que e status 3 processados e fazer as atualizacoes dos valores

            if ($die_process['qta_processar_status'] > 0) {
                $idBusca = $die_process['processo_id'];
            } else if ($die_process['qta_processar'] > 0) {
                $idBusca = $die_process['processo_id'];
            }
        }

        echo "<pre>";
        echo "MEU ID DE BUSCA";

        print_r($idBusca);

        if (isset($idBusca)) {

            $this->GravarUpdateDieProcess->UpdateBatchDieProcess($idBusca);
        }

        $retorno_transacao = self::alter_dados_process($dados);
    }

    //depois que ocorrer tudo vem para esta funcao para calcular o que tem e fazer os novos calculos
    public function alter_dados_process($dados)
    {

        $retorno_path_arquivo = $this->arquivos_json->env_json('path_arquivos_info');
        $arquivos = $retorno_path_arquivo  . DIRECTORY_SEPARATOR .  'meu_arquivo.json';
        $dados_mongo = [];

        // $dados_mongo = $this->instance->get_dados_parar($dados);

        // $documento = file_get_contents($arquivos);

        // $dados_json = json_decode($documento, true);

        // $novoJson = null;

        $upProcessDaddos = [];
        $valorTotal = 0;


        echo "<pre>";
        echo "passei nos dados\n";

        print_r($dados);

        foreach ($dados as $key => $valores) {

            $somataria_dados_processado =  $valores['qta'] - $valores['qta_processado'];


            if ($valores['qta_processar_status'] == 0 && $valores['qta_paralizado'] > 0) {

                $redeLoja =  $this->CapturaRedeLojaDoContrato->execute($valores['contrato']);
                list($valorLoteConsulta, $modulo) =    $this->BuscaValorLotePorConsulta->calcula($valores['codcns'], $redeLoja['rede'], $valores['qta_processado'], $valores['contrato']);
                $upProcessDaddos[] = [
                    'processo_id' => $valores['processo_id'],
                    'valor_lote' => $valorLoteConsulta,
                    'modulo' => $modulo,
                    'total_registros_parados' => $somataria_dados_processado,
                    'data_atualizacao' => date('Y-m-d H:i:s'),
                    'qta_processado' => $valores['qta_processado'],
                    'qta' => $valores['qta']
                ];
                $valorTotal += $valorLoteConsulta;
                //update na tabela processo
            } else if ($valores['qta_processar_status'] == 0 && $valores['qta_processado'] > 0) {
                $redeLoja =  $this->CapturaRedeLojaDoContrato->execute($valores['contrato']);
                list($valorLoteConsulta, $modulo) =    $this->BuscaValorLotePorConsulta->calcula($valores['codcns'], $redeLoja['rede'], $valores['qta_processado'], $valores['contrato']);
                $upProcessDaddos[] = [
                    'processo_id' => $valores['processo_id'],
                    'valor_lote' => $valorLoteConsulta,
                    'modulo' => $modulo,
                    'total_registros_parados' => $somataria_dados_processado,
                    'data_atualizacao' => date('Y-m-d H:i:s'),
                    'qta_processado' => $valores['qta_processado'],
                    'qta' => $valores['qta']
                ];
                $valorTotal += $valorLoteConsulta;
            }
        }

        echo "<pre>";
        echo "passei nos dados\n";

        print_r($upProcessDaddos);

        // die();
        foreach ($dados as $index => $key) {
            if (is_array($key) && isset($key['processo_id'])) {
                foreach ($upProcessDaddos as $processData) {

                    if ($key['processo_id'] == $processData['processo_id']) {
                        $modulo = $processData['modulo'];

                        $msg = 'O Total de ' . $processData['total_registros_parados'] . ' registros do processo (job) foi pausado. o id ' . $processData['processo_id'] . '
						     gerado no valor de R$ ' . number_format($valorLoteConsulta, 2, ',', '.') .  ' refereente a ' . $valores['qta_processado'] . ' registros processados. Valor atualizado na tabela!';
                        if ($modulo) {
                            $msg .=  "<br><span style='color:red;font-weight:bold'> Atenção: esta consulta é do tipo modular. O valor informado representa o teto máximo, e poderá sofrer ajustes ao final do processamento.</span>";
                        }

                        // Atualizar dados no JSON
                        $dados[$index]['valor_lote'] = $processData['valor_lote'];
                        $dados[$index]['modulo'] = $processData['modulo'];
                        $dados[$index]['id_process'] = (string)$processData['processo_id'];
                        $dados[$index]['total_registros'] = $processData['total_registros_parados'];
                        $dados[$index]['data_atualizacao'] = $processData['data_atualizacao'];
                        $dados[$index]['Qta_Original'] = $processData['total_registros_parados'];
                        $dados[$index]['infos'] = 'Qta_Original  ' . $processData['qta'] . ' Qta_Afetada ' . $processData['qta_processado'];
                        $dados[$index]['msg'] = $msg;
                        $result_alter = $this->filtros->alter_value_process_status($processData['processo_id'], $processData['valor_lote']);
                    }
                }
            }
        }

        $json_enconde = json_encode($dados, true);

        if (isset($json_enconde)) {
            $return = $this->instance->inset_json_dados($json_enconde, 'meu_arquivo.json');
        }
    }
}
