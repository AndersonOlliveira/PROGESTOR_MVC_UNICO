<?php

namespace App\Models;

use DateTime;
use Exception;
use Core\AppManipularError;
use MongoDB\Driver\Query;
// use MongoDB\BSON\Query;
use App\Core\MongoConect;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;




ini_set('memory_limit', '1256M');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

class instance extends MongoConect
{

    private $manager;
    private $dbname;
    private $collection;
    private $collection_json;
    private $collection_info;
    private $db_colletion_jobs;
    private $manager_local;
    private $db_colletion_json_dados;
    private $db_colletion_json_dados_paralizars;
    private $db_colletion_json_dados_reprocess;
    private $db_colletion_json_dados_cancelar;
    private $db_colletion_json_dados_prepago;





    public function __construct()
    {
        $conn = MongoConect::getInstance();
        // Agora buscamos o Client configurado no MongoConect
        $this->manager = $conn->getManager();
        $this->dbname = $conn->getDBName();

        $this->collection = $conn->getDBColetion();
        $this->collection_json = $conn->getDBColetion_json();
        $this->collection_info = $conn->getDBColetion_info();
        $this->db_colletion_jobs = $conn->getDBColetion_jobs();
        $this->db_colletion_json_dados = $conn->getDBColetion_jobs_dados_json();
        $this->db_colletion_json_dados_reprocess = $conn->getDBColetion_jobs_dados_json_reprocess();
        $this->db_colletion_json_dados_paralizars = $conn->getDBColetion_jobs_dados_paralizar();
        $this->db_colletion_json_dados_cancelar = $conn->getDBColetion_jobs_dados_cancelar();
        $this->db_colletion_json_dados_prepago = $conn->getDBColetion_jobs_dados_pre_pago();

        $this->manipulador = new AppManipularError(__DIR__ . '/../error/error_query_mongo.txt');
    }
    private function getMongoCollection($collectionName)
    {
        return $this->manager->selectCollection($this->dbname, $collectionName);
    }
    public function all()
    {
        $collection = $this->getMongoCollection($this->collection);
        $filter = [];
        $options = [
            'limit' => 1 // Altere para 0 ou remova se quiser trazer TODOS os registros
        ];

        try {
            $cursor = $collection->find($filter, $options);

            return $cursor->toArray();
        } catch (Exception $e) {
            print_R("TENHO ERRO APRESENTANDO AQUI!" . $e->getMessage());

            $this->manipulador->manipuladorDeErros(
                $e->getCode(),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            );
        }
    }

    public function findById($id, $id_transacao)
    {
        // 1. Verifica e monta o filtro de busca
        if (preg_match('/^[a-f0-9]{24}$/i', $id)) {
            $filter = ['id_processo' => new ObjectId($id)];
        } else {
            $filter = [
                'id_processo' => (int) $id,
                'transacao_id' => (int) $id_transacao
            ];
        }

        // 2. Define a projeção (quais campos trazer)
        $options = [
            'projection' => [
                'configuracao_json' => 1,
                'data_cadastro'      => 1,
                'transacao_id'       => 1,
                'id_processo'        => 1,
                'campo_aquisicao'    => 1,
                'status'             => 1,
                'resposta_json'      => 1,
                'resposta'           => 1,
                'new_status'         => 1,
                'sucesso'            => 1,
                'id'                 => 1,
                '_id'                => 0
            ]
        ];

        try {

            $collection = $this->getMongoCollection($this->collection_json);
            $documento = $collection->findOne($filter, $options);

            return $documento ?? null;
        } catch (Exception $e) {
            print_R("TENHO ERRO APRESENTANDO AQUI!" . $e->getMessage());

            $this->manipulador->manipuladorDeErros(
                $e->getCode(),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            );
        }
    }

    public function findByMultiple($dados)
    {
        $filtros = [];

        echo "<pre>";
        echo "DADOS ENVIADOS\n";
        print_r($dados);


        foreach ($dados as $values) {
            if (preg_match('/^[a-f0-9]{24}$/i', $values['processo_id'])) {
                $filtros[] = [
                    'id_processo' => new ObjectId($values['processo_id'])
                ];
            } else {
                $filtros[] = [
                    'id_processo'  => (int) $values['processo_id'],
                    'transacao_id' => (int) $values['transacao_id']
                ];
            }
        }

        // Se o array de dados veio vazio, aborta antes de consultar o banco
        if (empty($filtros)) {
            return [];
        }

        echo "<pre>";
        echo "FILTROS MOTADO ENVIADOS\n";
        print_r($filtros);


        // 2. Define as opções de projeção (quais campos retornar)
        $options = [
            'projection' => [
                'configuracao_json' => 1,
                'data_cadastro'     => 1,
                'transacao_id'      => 1,
                'id_processo'       => 1,
                'campo_aquisicao'   => 1,
                'status'            => 1,
                'resposta_json'     => 1,
                'resposta'          => 1,
                'new_status'        => 1,
                'sucesso'           => 1,
                'id'                => 1,
                '_id'               => 0
            ]
        ];

        try {
            // 3. Obtém a coleção correta através do seu método helper
            $collection = $this->getMongoCollection($this->collection_json);

            // 4. Executa a busca passando o operador $or e as opções diretamente no find()
            $cursor = $collection->find(['$or' => $filtros], $options);;

            // 5. Converte o cursor e retorna o array de documentos encontrados
            return $cursor->toArray();
        } catch (Exception $e) {
            print_R("TENHO ERRO APRESENTANDO AQUI!" .  $e->getMessage());

            $this->manipulador->manipuladorDeErros(
                $e->getCode(),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            );
        }
    }
    public function listarDadosDosProcessos()
    {
        $option = [
            'projection' => [
                'id_processo' => 1,
                'status' => 1,
                'resposta_json' => 1,
                'new_status' => 1,
                'sucesso' => 1,
                '_id' => 0
            ]
        ];

        try {

            $collection = $this->getMongoCollection($this->collection_json);
            // $query = new Query([], $option);
            $cursor = $collection->find([], $option);
            // $cursor = $this->manager->executeQuery("{$this->dbname}.{$this->collection_json}", $query);
            return $cursor->toArray();
        } catch (Exception $e) {
            print_R("TENHO ERRO APRESENTANDO AQUI!" . $e->getMessage());

            $this->manipulador->manipuladorDeErros(
                $e->getCode(),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            );
        }
    }
    public function insert($data)
    {
        $operacoes = [];

        foreach ($data as $dados) {
            if (!isset($dados['transacao_id'])) {
                continue;
            }

            // Monta a operação no formato esperado pela biblioteca do Client
            $operacoes[] = [
                'updateOne' => [
                    ['transacao_id' => $dados['transacao_id']], // Filtro
                    ['$set' => $dados],                         // Modificação
                    ['upsert' => true]                          // Opções (multi não é necessário no updateOne)
                ]
            ];
        }

        try {


            if (!empty($operacoes)) {
                $collection = $this->getMongoCollection($this->collection_json);


                return $collection->bulkWrite($operacoes);
            }
            return false;
        } catch (Exception $e) {
            print_R("TENHO ERRO APRESENTANDO AQUI!" . $e->getMessage());

            $this->manipulador->manipuladorDeErros(
                $e->getCode(),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            );
        }
    }
    public function update($id, $data)
    {
        $collection = $this->getMongoCollection($this->collection);

        $result = $collection->updateOne(
            ['_id' => new ObjectId($id)],
            ['$set' => $data],
            ['multi' => false, 'upsert' => false]
        );

        try {
            return $collection->bulkWrite($result);
        } catch (Exception $e) {
            print_R("TENHO ERRO APRESENTANDO AQUI!" . $e->getMessage());

            $this->manipulador->manipuladorDeErros(
                $e->getCode(),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            );
        }
    }

    public function delete($id)
    {
        // Obtém a coleção correta através do seu método helper
        $collection = $this->getMongoCollection($this->collection_json);

        try {
            $result = $collection->deleteOne(['_id' => new ObjectId($id)]);

            if ($result->getDeletedCount() > 0) {
                echo "Documento deletado com sucesso!\n";
            } else {
                echo " Nenhum documento encontrado com esse ID.\n";
            }

            return $result;
        } catch (Exception $e) {

            print_R("TENHO ERRO APRESENTANDO AQUI!" . $e->getMessage());

            $this->manipulador->manipuladorDeErros(
                $e->getCode(),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            );

            echo " Erro ao deletar documento: " . $e->getMessage() . "\n";

            return false;
        }
    }


    public function delete_all_teste($dados)
    {
        $ids = array_map(function ($id) {
            return new ObjectId($id);
        }, $dados);

        if (empty($ids)) {
            return false;
        }
        $collection = $this->getMongoCollection($this->collection_json);

        try {
            // Deleta todos os documentos cujo _id esteja na lista
            $result = $collection->deleteMany(['_id' => ['$in' => $ids]]);

            if ($result->getDeletedCount() > 0) {
                echo "Total de documentos deletados: " . $result->getDeletedCount() . "\n";
            } else {
                echo " Nenhum documento encontrado com os IDs informados.\n";
            }

            return $result;
        } catch (Exception $e) {
            echo " Erro ao deletar documentos: " . $e->getMessage() . "\n";

            print_R("TENHO ERRO APRESENTANDO AQUI!" . $e->getMessage());

            $this->manipulador->manipuladorDeErros(
                $e->getCode(),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            );

            return false;
        }
    }

    public function delete_all($dados)
    {
        $operacoes = [];

        // Monta o lote de exclusões
        foreach ($dados as $id) {
            $operacoes[] = [
                'deleteOne' => [
                    ['_id' => new ObjectId($id)]
                ]
            ];
        }

        if (empty($operacoes)) {
            return false;
        }

        $collection = $this->getMongoCollection($this->collection_json);

        try {
            // Executa o lote de deleções
            $result = $collection->bulkWrite($operacoes);

            if ($result->getDeletedCount() > 0) {
                echo "Total de documentos deletados: " . $result->getDeletedCount() . "\n";
            } else {
                echo " Nenhum documento encontrado com os IDs informados.\n";
            }

            return $result;
        } catch (Exception $e) {
            echo " Erro ao deletar documentos: " . $e->getMessage() . "\n";
            print_R("TENHO ERRO APRESENTANDO AQUI!" . $e->getMessage());

            $this->manipulador->manipuladorDeErros(
                $e->getCode(),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            );

            return false;
        }
    }



    public function data_all()
    {
        // 1. Obtém a coleção correta através do seu método helper (reutilizando a conexão existente)
        $collection = $this->getMongoCollection($this->collection_json);

        try {
            // 2. Um filtro vazio [] no deleteMany remove TODOS os documentos da coleção
            $result = $collection->deleteMany([]);

            // 3. Verifica e exibe a quantidade de registros limpos
            if ($result->getDeletedCount() > 0) {
                echo "Documento(s) deletado(s) com sucesso! Total: " . $result->getDeletedCount() . "\n";
            } else {
                echo "Nenhum documento encontrado para deletar.\n";
            }

            return $result;
        } catch (Exception $e) {
            echo "Erro ao executar operação: " . $e->getMessage() . "\n";
            print_R("TENHO ERRO APRESENTANDO AQUI!" . $e->getMessage());

            $this->manipulador->manipuladorDeErros(
                $e->getCode(),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            );
            return false;
        }
    }


    public function get_size_database()
    {
        try {
            // 1. Acessa o banco de dados diretamente através do Client
            $database = $this->manager->selectDatabase($this->dbname);

            // 2. Executa o comando administrativo de estatísticas de forma direta
            $stats = $database->command(['dbStats' => 1])->toArray();

            // 3. No Client, o retorno é um array onde o primeiro item possui os dados
            if (!empty($stats) && isset($stats[0]->dataSize)) {
                // Retorna o tamanho em Bytes (ex: 4194304)
                return $stats[0]->dataSize;
            } else {
                echo "Nenhum dado retornado para as estatísticas do banco de dados.\n";
                return null;
            }
        } catch (Exception $e) {
            print_R("TENHO ERRO APRESENTANDO AQUI!" . $e->getMessage());

            $this->manipulador->manipuladorDeErros(
                $e->getCode(),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            );
            echo "Erro ao obter estatísticas do banco de dados: " . $e->getMessage() . "\n";
            return null;
        }
    }
    public function get_qta_row()
    {
        try {

            $database = $this->manager->selectDatabase($this->dbname);
            $stats = $database->command(['dbStats' => 1])->toArray();
            $collection = $this->getMongoCollection($this->collection_json);

            // $command = new Command([
            //     'count' => $this->collection_json
            // ]);

            $result = $stats->command($collection);
            $response = current($result->toArray());

            if ($response->n > 0) {
                return $response->n;
            } else {
                echo "Nenhum dado retornado para as estatísticas do banco de dados.\n";
                return null;
            }
        } catch (Exception $e) {
            print_R("TENHO ERRO APRESENTANDO AQUI!" . $e->getMessage());

            $this->manipulador->manipuladorDeErros(
                $e->getCode(),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            );
            echo "Erro ao obter estatísticas do banco de dados: " . $e->getMessage() . "\n";
            return null;
        }
    }


    public function up_valor_modulos($data)
    {
        // Opcional: manter os prints de debug se ainda precisar testar
        // echo "<pre>"; print_r($data);

        // 1. Organiza os dados e garante a tipagem correta
        $dados_final = [
            'processo_id'      => (int) $data['processo_id'],
            'valor_original'   => (float) $data['valor_original'],
            'valor_geral'      => (float) $data['valor_geral'],
            'data_atualizacao' => $data['data_atualizacao'],
            'dados'            => $data[0]['dados'] ?? [] // Evita erro se 'dados' não estiver definido
        ];

        if (!isset($dados_final['processo_id'])) {
            return [
                'status'  => false,
                'message' => 'ID do processo não foi informado.'
            ];
        }

        // 2. Obtém a coleção correta através do seu método helper
        $collection = $this->getMongoCollection($this->collection_info);

        try {
            // 3. Verifica se o registro já existe de forma simples com findOne
            $filter = ['processo_id' => $dados_final['processo_id']];
            $documentoExiste = $collection->findOne($filter);

            if ($documentoExiste) {
                return [
                    'status'  => false,
                    'message' => 'Registro já existe, não foi inserido'
                ];
            }

            // 4. Caso não exista, realiza a inserção direta
            $result = $collection->insertOne($dados_final);

            // Se quiser ver o ID gerado pelo Mongo na tela
            // echo "Inserido ID: " . $result->getInsertedId() . "\n";

            return [
                'status'  => true,
                'message' => 'Registro inserido com sucesso'
            ];
        } catch (Exception $e) {
            print_R("TENHO ERRO APRESENTANDO AQUI!" . $e->getMessage());

            $this->manipulador->manipuladorDeErros(
                $e->getCode(),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            );
            return [
                'status'  => false,
                'message' => 'Erro ao processar operação: ' . $e->getMessage()
            ];
        }
    }

    public function inset_json_dados($dadosJson, $nome_arquivo)
    {
        // 1. Define qual coleção usar com base no nome do arquivo
        $db_conect = ($nome_arquivo == 'infoReprocess.json')
            ? $this->db_colletion_json_dados_reprocess
            : $this->db_colletion_json_dados;

        // 2. Transforma a string JSON em array do PHP
        $data = json_decode($dadosJson, true);

        // Se o JSON for inválido ou estiver vazio, aborta a operação
        if (!is_array($data) || empty($data)) {
            return [
                'success' => false,
                'message' => 'JSON inválido ou vazio.'
            ];
        }

        $operacoes = [];

        // 3. Monta o lote de operações no formato do Client
        foreach ($data as $dados) {
            if (!isset($dados['id_process'])) {
                continue;
            }

            $operacoes[] = [
                'updateOne' => [
                    ['id_process' => (string)$dados['id_process']], // Filtro
                    ['$set' => $dados],                             // Dados
                    ['upsert' => true]                              // Opções (multi não se aplica a updateOne)
                ]
            ];
        }

        try {
            // 4. Se houver operações válidas no array, executa no banco
            if (!empty($operacoes)) {
                $collection = $this->getMongoCollection($db_conect);
                $result = $collection->bulkWrite($operacoes);

                // Retorna as estatísticas reais da execução utilizando os métodos do Client
                return [
                    'success'  => true,
                    'inserted' => $result->getInsertedCount(),
                    'modified' => $result->getModifiedCount(),
                    'upserted' => $result->getUpsertedCount(),
                    'matched'  => $result->getMatchedCount(),
                ];
            }

            return [
                'success' => false,
                'message' => 'Nenhuma operação executada'
            ];
        } catch (Exception $e) {
            print_R("TENHO ERRO APRESENTANDO AQUI!" . $e->getMessage());

            $this->manipulador->manipuladorDeErros(
                $e->getCode(),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            );
            return [
                'success' => false,
                'error'   => $e->getMessage()
            ];
        }
    }
    //para inserir o paralizar pegando o finger e data e e id do processo 

    public function insert_all_paralizar($dadosJson)
    {
        // 1. Transforma a string JSON em array do PHP
        $data = json_decode($dadosJson, true);

        // Validação inicial do formato do JSON
        if (!is_array($data) || !isset($data['id_processo'])) {
            return [
                'success' => false,
                'message' => 'JSON inválido, não é um array ou id_processo não foi informado.'
            ];
        }

        // 2. Obtém a coleção correta através do seu método helper
        $collection = $this->getMongoCollection($this->db_colletion_json_dados_paralizars);

        try {
            // 3. Busca o documento atual para capturar o "finger" inicial (Substitui Query/executeQuery)
            $filter = ['id_processo' => $data['id_processo']];
            $optionsBusca = ['projection' => ['finger' => 1]];

            $document = $collection->findOne($filter, $optionsBusca);
            $finger_inicial = $document->finger ?? null;

            // 4. Monta a estrutura de atualização ($set e $push com a data atual formatada para o Mongo)
            $updateData = [
                '$set' => $data,
                '$push' => [
                    'historico_solicitacao_paralizar' => [
                        'data_solicitacao' => new UTCDateTime(), // Tipo BSON correto
                        'finger_old'       => $finger_inicial
                    ]
                ]
            ];

            // 5. Executa a atualização/inserção (Substitui BulkWrite/executeBulkWrite)
            $result = $collection->updateOne($filter, $updateData, ['upsert' => true]);

            // Retorna as estatísticas exatas da operação usando a API do Client
            return [
                'success'  => true,
                'inserted' => $result->getUpsertedCount() > 0 ? 1 : 0, // No updateOne, se houve upsert, conta como inserido
                'modified' => $result->getModifiedCount(),
                'upserted' => $result->getUpsertedCount(),
                'matched'  => $result->getMatchedCount(),
            ];
        } catch (Exception $e) {
            print_R("TENHO ERRO APRESENTANDO AQUI!" . $e->getMessage());

            $this->manipulador->manipuladorDeErros(
                $e->getCode(),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            );
            return [
                'success' => false,
                'error'   => $e->getMessage()
            ];
        }
    }
    public function insert_all_cancelar($dadosJson)
    {
        // Opcional: mantido para debug como no seu código original
        // print_r($dadosJson);

        // 1. Transforma a string JSON em array do PHP
        $data = json_decode($dadosJson, true);

        // Validação inicial do formato do JSON
        if (!is_array($data) || !isset($data['id_processo'])) {
            return [
                'success' => false,
                'message' => 'JSON inválido, não é um array ou id_processo não foi informado.'
            ];
        }

        // 2. Obtém a coleção correta através do seu método helper
        $collection = $this->getMongoCollection($this->db_colletion_json_dados_cancelar);

        try {
            // 3. Busca o documento atual para capturar o "finger" inicial (Substitui Query/executeQuery)
            $filter = ['id_processo' => $data['id_processo']];
            $optionsBusca = ['projection' => ['finger' => 1]];

            $document = $collection->findOne($filter, $optionsBusca);
            $finger_inicial = $document->finger ?? null;

            // 4. Monta a estrutura de atualização ($set e $push com a data atual formatada para o Mongo)
            $updateData = [
                '$set' => $data,
                '$push' => [
                    'historico_solicitacao_cancelar' => [
                        'data_solicitacao' => new UTCDateTime(), // Tipo BSON correto
                        'finger_old'       => $finger_inicial
                    ]
                ]
            ];

            // 5. Executa a atualização/inserção direta (Substitui BulkWrite/executeBulkWrite)
            $result = $collection->updateOne($filter, $updateData, ['upsert' => true]);

            // Retorna as estatísticas exatas da operação usando a API moderna do Client
            return [
                'success'  => true,
                'inserted' => $result->getUpsertedCount() > 0 ? 1 : 0, // Ajustado para ler se houve Upsert (inserção)
                'modified' => $result->getModifiedCount(),
                'upserted' => $result->getUpsertedCount(),
                'matched'  => $result->getMatchedCount(),
            ];
        } catch (Exception $e) {
            print_R("TENHO ERRO APRESENTANDO AQUI!" . $e->getMessage());

            $this->manipulador->manipuladorDeErros(
                $e->getCode(),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            );
            return [
                'success' => false,
                'error'   => $e->getMessage()
            ];
        }
    }


    //BUSCO A DATA PARA NO MONGO PARA SANER 

    public function get_data_paralizar()
    {
        $options = [
            'projection' => [
                'id_processo'      => 1,
                'contrato'         => 1,
                'paralisado'       => 1,
                'data'             => 1,
                'data_finalizacao' => 1,
                '_id'              => 0
            ]
        ];

        $filtro = [
            'contrato' => [
                '$exists' => true,
                '$nin'    => [null, '']
            ]
        ];

        try {
            $collection = $this->getMongoCollection($this->db_colletion_json_dados_paralizars);

            $cursor = $collection->find($filtro, $options);

            return $cursor->toArray();
        } catch (Exception $e) {

            print_R("TENHO ERRO APRESENTANDO AQUI!" . $e->getMessage());

            $this->manipulador->manipuladorDeErros(
                $e->getCode(),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            );

            echo "Erro ao buscar dados de paralisação: " . $e->getMessage() . "\n";
            return [];
        }
    }

    public function get_finger_paralizar($id)
    {
        $filtros = [];
        if (preg_match('/^[a-f0-9]{24}$/i', $id)) {
            $filtros[] = [
                'contrato' => new ObjectId($id)
            ];
        } else {
            $filtros[] = [
                'contrato' => $id
            ];
        }

        if (empty($filtros)) {
            return [];
        }

        $options = [
            'projection' => [
                'processo_id'                     => '$id_processo',
                'data_solicitacao'                => '$data',
                'paralisado'                      => '$paralisado',
                'finger_paralisado'               => '$finger',
                'historico_paralisacao'           => '$historico_paralisacao',
                'historico_solicitacao_paralizar' => '$historico_solicitacao_paralizar',
                '_id'                             => 0
            ]
        ];

        try {
            $collection = $this->getMongoCollection($this->db_colletion_json_dados_paralizars);

            $cursor = $collection->find(['$or' => $filtros], $options);

            $dados = $cursor->toArray();

            foreach ($dados as $values) {
                if (isset($values->data_solicitacao) && $values->data_solicitacao instanceof UTCDateTime) {
                    $values->data_solicitacao = $values->data_solicitacao->toDateTime()->format('d/m/Y H:i:s');
                }
                if (isset($values->finger_paralisado)) {
                    $values->finger_paralisado = self::removerAcentos($values->finger_paralisado);
                }

                if (isset($values->historico_paralisacao) && (is_array($values->historico_paralisacao) || is_object($values->historico_paralisacao))) {
                    foreach ($values->historico_paralisacao as $dados_paralizar) {
                        if (isset($dados_paralizar->data_solicitacao)) {
                            if ($dados_paralizar->data_solicitacao instanceof UTCDateTime) {
                                $dados_paralizar->data_solicitacao = $dados_paralizar->data_solicitacao->toDateTime()->format('d/m/Y H:i:s');
                            } else {
                                $dataObj = new DateTime($dados_paralizar->data_solicitacao);
                                $dados_paralizar->data_solicitacao = $dataObj->format('d/m/Y H:i:s');
                            }
                        }
                    }
                }

                // Processa o segundo bloco de histórico (historico_solicitacao_paralizar)
                if (isset($values->historico_solicitacao_paralizar) && (is_array($values->historico_solicitacao_paralizar) || is_object($values->historico_solicitacao_paralizar))) {
                    foreach ($values->historico_solicitacao_paralizar as $dados_paralizar_historico) {
                        if (isset($dados_paralizar_historico->data_solicitacao)) {
                            if ($dados_paralizar_historico->data_solicitacao instanceof UTCDateTime) {
                                $dados_paralizar_historico->data_solicitacao = $dados_paralizar_historico->toDateTime()->format('d/m/Y H:i:s');
                            } else {
                                $dataObj = new DateTime($dados_paralizar_historico->data_solicitacao);
                                $dados_paralizar_historico->data_solicitacao = $dataObj->format('d/m/Y H:i:s');
                            }
                        }
                    }
                }
            }

            return !empty($dados) ? $dados : null;
        } catch (Exception $e) {

            print_R("TENHO ERRO APRESENTANDO AQUI!" . $e->getMessage());

            $this->manipulador->manipuladorDeErros(
                $e->getCode(),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            );
            return [
                'success' => false,
                'error'   => $e->getMessage()
            ];
        }
    }
    public function get_finger_info_reprocess($id)
    {
        $filtros = [];
        if (preg_match('/^[a-f0-9]{24}$/i', $id)) {
            $filtros[] = [
                'id_process' => new ObjectId($id)
            ];
        } else {
            $filtros[] = [
                'id_process' => (string)$id
            ];
        }

        if (empty($filtros)) {
            return [];
        }

        $options = [
            'projection' => [
                'id_process'       => 1,
                'contrato'         => 1,
                'requested'        => 1,
                'reprocessado_day' => 1,
                'new_id_process'   => 1,
                '_id'              => 0
            ]
        ];

        try {
            $collection = $this->getMongoCollection($this->db_colletion_json_dados);
            $cursor = $collection->find(['$or' => $filtros], $options);

            $dados = $cursor->toArray();

            return !empty($dados) ? $dados : null;
        } catch (Exception $e) {

            print_R("TENHO ERRO APRESENTANDO AQUI!" . $e->getMessage());

            $this->manipulador->manipuladorDeErros(
                $e->getCode(),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            );

            return [
                'success' => false,
                'error'   => $e->getMessage()
            ];
        }
    }

    public function get_finger_parar_reprocessar($id)
    {
        $filtros = [];
        if (preg_match('/^[a-f0-9]{24}$/i', $id)) {
            $filtros[] = [
                'id_process' => new ObjectId($id)
            ];
        } else {
            $filtros[] = [
                'id_process' => (string)$id
            ];
        }

        if (empty($filtros)) {
            return [];
        }

        $options = [
            'projection' => [
                'processo_id'            => '$id_process',
                'data_solicitacao_parar' => '$data_alteracao',
                'finger'                 => '$info_auditoria_finger',
                'paralizar'              => '$paralizar_processos',
                '_id'                    => 0
            ]
        ];

        try {
            $collection = $this->getMongoCollection($this->db_colletion_json_dados);

            $cursor = $collection->find(['$or' => $filtros], $options);

            $result = $cursor->toArray();

            foreach ($result as $values) {
                if (isset($values->finger)) {
                    $values->finger = self::removerAcentos($values->finger);
                }
            }

            return !empty($result) ? $result : null;
        } catch (Exception $e) {

            print_R("TENHO ERRO APRESENTANDO AQUI!" . $e->getMessage());

            $this->manipulador->manipuladorDeErros(
                $e->getCode(),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            );

            return [
                'success' => false,
                'error'   => $e->getMessage()
            ];
        }
    }
    public function insert_all_paralizar_reprocesar_jobs($id, $paralisadoAtual, $acao, $fingerUsuario)
    {
        $collection = $this->getMongoCollection($this->db_colletion_json_dados_paralizars);

        try {
            $filter = ['id_processo' => $id];
            $optionsBusca = ['projection' => ['data' => 1]];

            $document = $collection->findOne($filter, $optionsBusca);
            $dataInicial = $document->data ?? null;

            $update = [
                '$set' => [
                    'paralisado' => $paralisadoAtual,
                    'data'       => null
                ],
                '$push' => [
                    'historico_paralisacao' => [
                        'acao'                    => $acao == 1 ? 'paralisar' : 'desparalisar',
                        'data_solicitacao'        => new UTCDateTime(), // Tipo BSON nativo correto
                        'finger'                  => $fingerUsuario,
                        'dataInicial_paralisazao' => $dataInicial
                    ]
                ]
            ];


            $result = $collection->updateOne($filter, $update, ['upsert' => true]);


            return [
                'success'  => true,
                'inserted' => $result->getUpsertedCount() > 0 ? 1 : 0, // Checa se houve um Upsert (inserção)
                'modified' => $result->getModifiedCount(),
                'upserted' => $result->getUpsertedCount(),
                'matched'  => $result->getMatchedCount(),
            ];
        } catch (Exception $e) {
            print_R("TENHO ERRO APRESENTANDO AQUI!" . $e->getMessage());

            $this->manipulador->manipuladorDeErros(
                $e->getCode(),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            );

            return [
                'success' => false,
                'error'   => $e->getMessage()
            ];
        }
    }
    public function busca_dados_finger_parar($id)
    {
        // 1. Define a projeção corrigindo o erro de sintaxe original
        $options = [
            'projection' => [
                'data_alteracao' => 1,
                '_id'            => 0 // Opcional: evita trazer o _id se não for usar
            ]
        ];
        $collection = $this->getMongoCollection($this->db_colletion_json_dados);

        try {
            $filter = ['id_process' => $id];
            $document = $collection->findOne($filter, $options);

            $data_alteracao = $document->data_alteracao ?? null;

            return $data_alteracao;
        } catch (Exception $e) {

            print_R("TENHO ERRO APRESENTANDO AQUI!" . $e->getMessage());

            $this->manipulador->manipuladorDeErros(
                $e->getCode(),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            );

            return [
                'success' => false,
                'error'   => $e->getMessage()
            ];
        }
    }
    public function get_dados_parar($dados)
    {
        $filtros = [];

        foreach ($dados as $values) {
            if (preg_match('/^[a-f0-9]{24}$/i', $values['processo_id'])) {
                $filtros[] = [
                    'id_process' => new ObjectId($values['processo_id'])
                ];
            } else {
                $filtros[] = [
                    'id_process' => (string)$values['processo_id'],
                ];
            }
        }

        if (empty($filtros)) {
            return [];
        }

        $options = [
            'projection' => [
                '_id'                   => 1,
                'id_process'            => 1,
                'data_alteracao'        => 1,
                'info_auditoria_finger' => 1,
                'paralizar_processos'   => 1,
                'status'                => 1
            ]
        ];

        try {
            $collection = $this->getMongoCollection($this->db_colletion_json_dados);

            $cursor = $collection->find(['$or' => $filtros], $options);


            return $cursor->toArray();
        } catch (Exception $e) {
            print_R("TENHO ERRO APRESENTANDO AQUI!" . $e->getMessage());

            $this->manipulador->manipuladorDeErros(
                $e->getCode(),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            );

            return [
                'success' => false,
                'error'   => $e->getMessage()
            ];
        }
    }
    public function get_dados_info_reprocess($dados)
    {
        $filtros = [];

        if (preg_match('/^[a-f0-9]{24}$/i', $dados)) {
            $filtros[] = [
                'id_process' => new ObjectId($dados)
            ];
        } else {
            $filtros[] = [
                'id_process' => (string)$dados,
            ];
        }

        $options = [
            'projection' => [
                'info_reprocess' => 1,
                'msg'            => 1,
                'data_alteracao' => 1,
                '_id'            => 0
            ]
        ];

        try {

            $collection = $this->getMongoCollection($this->db_colletion_json_dados);

            $result = $collection->findOne(['$or' => $filtros], $options);

            return $result ?: null;
        } catch (Exception $e) {

            print_R("TENHO ERRO APRESENTANDO AQUI!" . $e->getMessage());

            $this->manipulador->manipuladorDeErros(
                $e->getCode(),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            );

            return [
                'success' => false,
                'error'   => $e->getMessage()
            ];
        }
    }

    public function get_dados_info_paralizar_die($dados)
    {
        $filtros = [];

        if (preg_match('/^[a-f0-9]{24}$/i', $dados)) {
            $filtros[] = [
                'id_processo' => new ObjectId($dados)
            ];
        } else {
            $filtros[] = [
                'id_processo' => (string)$dados,
            ];
        }

        // 1. Define as opções de projeção (ajustado para o plural 'options')
        $options = [
            'projection' => [
                'data_finalizacao'    => 1,
                'processo_finalizado' => 1,
                '_id'                 => 0
            ]
        ];

        try {
            $collection = $this->getMongoCollection($this->db_colletion_json_dados_paralizars);

            $result = $collection->findOne(['$or' => $filtros], $options);

            if ($result) {

                if (isset($result->data_finalizacao) && $result->data_finalizacao instanceof UTCDateTime) {
                    $result->data_finalizacao = $result->data_finalizacao->toDateTime()->format('d/m/Y H:i:s');
                } elseif (isset($result->data_finalizacao->date) && !empty($result->data_finalizacao->date)) {
                    $data = new DateTime($result->data_finalizacao->date);
                    $result->data_finalizacao = $data->format('d/m/Y H:i:s');
                }

                return $result;
            }

            return null;
        } catch (Exception $e) {

            print_R("TENHO ERRO APRESENTANDO AQUI!" . $e->getMessage());

            $this->manipulador->manipuladorDeErros(
                $e->getCode(),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            );

            return [
                'success' => false,
                'error'   => $e->getMessage()
            ];
        }
    }


    public function get_fingers_cancelar($contrato)
    {
        $filtros = [];
        if (preg_match('/^[a-f0-9]{24}$/i', $contrato)) {
            $filtros[] = [
                'contrato' => new ObjectId($contrato)
            ];
        } else {
            $filtros[] = [
                'contrato' => $contrato
            ];
        }

        if (empty($filtros)) {
            return [];
        }

        // 1. Define as opções de projeção com aliases (ajustado para o plural 'options')
        $options = [
            'projection' => [
                'processo_id'    => '$id_processo',
                'data_cancelado' => '$data',
                'cancelado'      => '$cancelado',
                'finger'         => '$finger',
                '_id'            => 0
            ]
        ];

        try {

            $collection = $this->getMongoCollection($this->db_colletion_json_dados_cancelar);


            $cursor = $collection->find(['$or' => $filtros], $options);


            $result = $cursor->toArray();


            if (!empty($result)) {
                foreach ($result as $values) {


                    if (isset($values->data_cancelado) && $values->data_cancelado instanceof UTCDateTime) {
                        $values->data_cancelado = $values->data_cancelado->toDateTime()->format('Y/m/d H:i:s');
                    }
                    if (isset($values->finger)) {
                        $values->finger = self::removerAcentos($values->finger);
                    }
                }
            }


            return !empty($result) ? $result : null;
        } catch (Exception $e) {

            print_R("TENHO ERRO APRESENTANDO AQUI!" . $e->getMessage());

            $this->manipulador->manipuladorDeErros(
                $e->getCode(),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            );

            return [
                'success' => false,
                'error'   => $e->getMessage()
            ];
        }
    }

    function removerAcentos($texto)
    {
        return preg_replace(array("/(á|à|ã|â|ä)/", "/(Á|À|Ã|Â|Ä)/", "/(é|è|ê|ë)/", "/(É|È|Ê|Ë)/", "/(í|ì|î|ï)/", "/(Í|Ì|Î|Ï)/", "/(ó|ò|õ|ô|ö)/", "/(Ó|Ò|Õ|Ô|Ö)/", "/(ú|ù|û|ü)/", "/(Ú|Ù|Û|Ü)/", "/(ñ)/", "/(Ñ)/"), explode(" ", "a A e E i I o O u U n N"), $texto);
    }

    public function prePaggoInfo($dados)
    {

        $dados = json_decode($dados);

        if (!isset($dados->id_processo)) {
            return [
                'success' => false,
                'message' => 'id_processo não foi informado no JSON.'
            ];
        }

        $collection = $this->getMongoCollection($this->db_colletion_json_dados_prepago);

        try {

            $filter = ['id_processo' => $dados->id_processo];
            $optionsBusca = [
                'projection' => [
                    'data_solicitacao' => 1,
                    'info_pagamento'   => 1,
                    'status'           => 1,
                    '_id'              => 0
                ]
            ];

            $document = $collection->findOne($filter, $optionsBusca);

            $dataInicial    = $document->data_solicitacao ?? null;
            $info_pagamento = $document->info_pagamento ?? null;
            $status         = $document->status ?? null;

            $info = true;


            $historico = [
                'acao_prepago'     => ($dados->info_pagamento == 1),
                'data_solicitacao' => empty($dataInicial) ? $dados->data_solicitacao : $dataInicial,
                'info_pagamento'   => empty($info_pagamento) ? $dados->info_pagamento : $info_pagamento,
                'status'           => empty($status) ? $dados->status : $status,
            ];

            if ($info) {
                $historico['data_process_pagamento'] = new UTCDateTime();
            }

            $setData = array_merge(
                (array) $dados,
                [
                    'info_pagamento' => $info ? true : $info_pagamento,
                    'status'         => $info ? 0 : $status,
                ]
            );


            $update = [
                '$set'  => $setData,
                '$push' => [
                    'historico_solicitacao_preapago' => $historico,
                ]
            ];


            $result = $collection->updateOne($filter, $update, ['upsert' => true]);

            $da = [
                'success'  => true,
                'inserted' => $result->getUpsertedCount() > 0 ? 1 : 0, // Checa se houve um Upsert (inserção)
                'modified' => $result->getModifiedCount(),
                'upserted' => $result->getUpsertedCount(),
                'matched'  => $result->getMatchedCount(),
            ];



            return $da;
        } catch (Exception $e) {

            print_R("TENHO ERRO APRESENTANDO AQUI!" . $e->getMessage());

            $this->manipulador->manipuladorDeErros(
                $e->getCode(),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            );

            return [
                'success' => false,
                'error'   => $e->getMessage()
            ];
        }
    }

    public function get_prePago_info()
    {
        // 1. Define as opções de projeção (ajustado o nome da variável para o plural 'options')
        $options = [
            'projection' => [
                'id_processo'    => 1,
                'contrato'       => 1,
                'info_pagamento' => 1,
                'status'         => 1,
                '_id'            => 0
            ]
        ];

        try {
            // 2. Obtém a coleção correta através do seu método helper
            $collection = $this->getMongoCollection($this->db_colletion_json_dados_prepago);

            // 3. Executa a busca passando um filtro vazio [] para trazer todos os registros
            $cursor = $collection->find([], $options);

            // 4. Converte o cursor diretamente para um array tradicional do PHP
            $result = $cursor->toArray();

            // 5. Retorna a lista de dados encontrada ou false caso esteja vazia
            return !empty($result) ? $result : false;
        } catch (Exception $e) {

            print_R("TENHO ERRO APRESENTANDO AQUI!" . $e->getMessage());

            $this->manipulador->manipuladorDeErros(
                $e->getCode(),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            );

            return [
                'success' => false,
                'error'   => $e->getMessage()
            ];
        }
    }
}
