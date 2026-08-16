<?php

namespace App\Models;

use App\Core\MongoConect;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Query;
use Exception;

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
    }
    private function getMongoCollection($collectionName)
    {
        return $this->manager->selectCollection($this->dbname, $collectionName);
    }

    public function all()
    {
        // Seleciona a coleção correta no MongoDB
        $col = $this->getMongoCollection($this->collection);

        // Passa o limite direto no array de opções como segundo parâmetro
        $cursor = $col->find([], ['limit' => 1]);

        // Retorna o array de resultados
        return $cursor->toArray();
    }

    public function findById($id, $id_transacao)
    {
        if (preg_match('/^[a-f0-9]{24}$/i', $id)) {
            $filter = ['id_processo' => new ObjectId($id)];
        } else {
            $filter = [
                'id_processo' => (int) $id,
                'transacao_id' => (int) $id_transacao
            ];
        }

        $options = [
            'projection' => [
                'configuracao_json' => 1,
                'data_cadastro' => 1,
                'transacao_id' => 1,
                'id_processo' => 1,
                'campo_aquisicao' => 1,
                'status' => 1,
                'resposta_json' => 1,
                'resposta' => 1,
                'new_status' => 1,
                'sucesso' => 1,
                'id' => 1,
                '_id' => 0
            ]
        ];

        $col = $this->getMongoCollection($this->collection_json);
        $cursor = $col->find($filter, $options);

        return $cursor->toArray() ?: null;
    }

    public function findByMultiple($dados)
    {
        $filtros = [];
        foreach ($dados as $values) {
            if (preg_match('/^[a-f0-9]{24}$/i', $values['processo_id'])) {
                $filtros[] = ['id_processo' => new ObjectId($values['processo_id'])];
            } else {
                $filtros[] = [
                    'id_processo'  => $values['processo_id'],
                    'transacao_id' => $values['transacao_id']
                ];
            }
        }

        if (empty($filtros)) {
            return [];
        }

        $options = [
            'projection' => [
                'configuracao_json' => 1,
                'data_cadastro' => 1,
                'transacao_id' => 1,
                'id_processo' => 1,
                'campo_aquisicao' => 1,
                'status' => 1,
                'resposta_json' => 1,
                'resposta' => 1,
                'new_status' => 1,
                'sucesso' => 1,
                'id' => 1,
                '_id' => 0
            ]
        ];

        $col = $this->getMongoCollection($this->collection_json);
        $cursor = $col->find(['$or' => $filtros], $options);

        return $cursor->toArray();
    }

    public function listarDadosDosProcessos()
    {
        $options = [
            'projection' => [
                'id_processo' => 1,
                'status' => 1,
                'resposta_json' => 1,
                'new_status' => 1,
                'sucesso' => 1,
                '_id' => 0
            ]
        ];

        $col = $this->getMongoCollection($this->collection_json);
        $cursor = $col->find([], $options);
        return $cursor->toArray();
    }

    public function insert($data)
    {
        $col = $this->getMongoCollection($this->collection_json);
        $operacoes = 0;
        $result = null;

        // O pacote do Composer utiliza BulkWrite de forma encapsulada
        foreach ($data as $dados) {
            if (!isset($dados['transacao_id'])) {
                continue;
            }

            // Realiza múltiplos updates/upserts usando a API nativa do pacote
            $result = $col->updateOne(
                ['transacao_id' => $dados['transacao_id']],
                ['$set' => $dados],
                ['upsert' => true]
            );
            $operacoes++;
        }

        return $operacoes > 0 ? $result : false;
    }

    public function update($id, $data)
    {
        $col = $this->getMongoCollection($this->collection);
        return $col->updateOne(
            ['_id' => new ObjectId($id)],
            ['$set' => $data]
        );
    }

    public function delete($id)
    {
        $col = $this->getMongoCollection($this->collection_json);
        try {
            $result = $col->deleteOne(['_id' => new ObjectId($id)]);

            if ($result->getDeletedCount() > 0) {
                echo "Documento deletado com sucesso!\n";
            } else {
                echo "Nenhum documento encontrado com esse ID.\n";
            }

            return $result;
        } catch (Exception $e) {
            echo "Erro ao deletar documento: " . $e->getMessage() . "\n";
        }
    }


    public function delete_all($dados)
    {
        $col = $this->getMongoCollection($this->collection_json);

        $ids = array_map(function ($id) {
            return new ObjectId($id);
        }, $dados);

        try {
            // Deleta múltiplos documentos de uma só vez usando o $in
            $result = $col->deleteMany(['_id' => ['$in' => $ids]]);

            if ($result->getDeletedCount() > 0) {
                echo "Documentos deletados com sucesso!\n";
            } else {
                echo "Nenhum documento encontrado com esses IDs.\n";
            }

            return $result;
        } catch (Exception $e) {
            echo "Erro ao deletar documentos: " . $e->getMessage() . "\n";
        }
    }
    public function truncate_collection_json()
    {
        $col = $this->getMongoCollection($this->collection_json);
        try {
            // Remove todos os registros limpando a collection
            return $col->deleteMany([]);
        } catch (Exception $e) {
            echo "Erro ao limpar tabela: " . $e->getMessage() . "\n";
        }
    }



    public function data_alla()
    {


        $bulk = new MongoDB\Driver\BulkWrite();


        try {

            $manager = new MongoDB\Driver\Manager("mongodb://{$this->manager_local}");

            $bulk->delete([]);

            // $writeConcern = new MongoDB\Driver\WriteConcern(MongoDB\Driver\WriteConcern::MAJORITY, 1000);
            // $result = $this->manager->executeBulkWrite("{$this->dbname}.{$this->collection_json}", $bulk, $writeConcern);
            $result = $manager->executeBulkWrite("{$this->dbname}.{$this->collection_json}", $bulk);


            if ($result->getDeletedCount() > 0) {
                echo "Documento(s) deletado(s) com sucesso!\n";
            } else {
                echo "Nenhum documento encontrado para deletar.\n";
            }
        } catch (MongoDB\Driver\Exception\Exception $e) {
            echo "Erro ao executar operação: " . $e->getMessage() . "\n";
        }
    }


    public function get_size_database()
    {
        try {
            $command = new MongoDB\Driver\Command(['dbStats' => 1]);
            $stats = $this->manager->executeCommand($this->dbname, $command);
            $statsArray = $stats->toArray();
            if (count($statsArray) > 0) {
                $sizeInBytes = $statsArray[0]->dataSize;
                return $sizeInBytes;
            } else {
                echo "Nenhum dado retornado para as estatísticas do banco de dados.\n";
                return null;
            }
        } catch (MongoDB\Driver\Exception\Exception $e) {
            echo "Erro ao obter estatísticas do banco de dados: " . $e->getMessage() . "\n";
            return null;
        }
    }

    public function get_qta_row()
    {
        try {


            $command = new MongoDB\Driver\Command([
                'count' => $this->collection_json
            ]);

            $result = $this->manager->executeCommand($this->dbname, $command);
            $response = current($result->toArray());

            if ($response->n > 0) {
                return $response->n;
            } else {
                echo "Nenhum dado retornado para as estatísticas do banco de dados.\n";
                return null;
            }
        } catch (MongoDB\Driver\Exception\Exception $e) {
            echo "Erro ao obter estatísticas do banco de dados: " . $e->getMessage() . "\n";
            return null;
        }
    }


    public function up_valor_modulos($data)
    {

        echo "<pre>";
        print_R($data);

        $dados_final = [
            'processo_id'       => (int) $data['processo_id'],
            'valor_original'    => (float) $data['valor_original'],
            'valor_geral'       => (float) $data['valor_geral'],
            'data_atualizacao' => $data['data_atualizacao'],
            'dados'             => $data[0]['dados']
        ];


        $bulk = new MongoDB\Driver\BulkWrite;


        if (isset($dados_final['processo_id'])) {
            $filter = ['processo_id' => $dados_final['processo_id']];


            $query = new MongoDB\Driver\Query($filter);
            $cursor = $this->manager->executeQuery(
                "{$this->dbname}.{$this->collection_info}",
                $query
            );


            $resultado = $cursor->toArray();
            $jaExiste = false;

            if (count($resultado) > 0) {
                $jaExiste = true;
            }

            if ($jaExiste) {
                $info_return =  [
                    'status'  => false,
                    'message' => 'Registro já existe, não foi inserido'
                ];
            }

            if (!$jaExiste) {
                $bulk->insert($dados_final);

                $result = $this->manager->executeBulkWrite(
                    "{$this->dbname}.{$this->collection_info}",
                    $bulk
                );

                echo "Inseridos:  " . $result->getUpsertedCount() . "\n";
                echo "Atualizados: " . $result->getModifiedCount() . "\n";

                $info_return =  [
                    'status'  => true,
                    'message' => 'Registro inserido com sucesso'
                ];
            }

            return $info_return;

            // $update = ['$set' => $data];

            // $bulk->update(
            //     $filter,
            //     $update,
            //     ['upsert' => true, 'multi' => false]
            // );


            // if (!empty(($data))) {

            //     $result = $this->manager->executeBulkWrite("{$this->dbname}.{$this->collection_info}", $bulk);
            // }

            // echo "Inseridos:  " . $result->getUpsertedCount() . "\n";
            // echo "Atualizados: " . $result->getModifiedCount() . "\n";
        }


        // $filter = ['processo_id' => $dados['processo_id']];

        // $inser = ['$set' => $data];

        // $bulk->update(
        //     $filter,
        //     $inser,
        //     ['upsert' => true, 'multi' => false]
        // );

        // $bulk->insert($data);
    }

    public function inset_json_dados($dadosJson, $nome_arquivo)
    {


        $db_conect = $nome_arquivo == 'infoReprocess.json' ? $this->db_colletion_json_dados_reprocess : $this->db_colletion_json_dados;
        // echo "estou chamando dentro da instancia\n";

        // print_r("Coleção: {$this->dbname}.{$this->db_colletion_json_dados}\n");

        $bulk = new MongoDB\Driver\BulkWrite;
        $operacoes = 0;


        $data  = json_decode($dadosJson, true);

        foreach ($data as $dados) {

            if (!isset($dados['id_process'])) {
                continue;
            }

            $filter = ['id_process' => (string)$dados['id_process']];
            $inser  = ['$set' => $dados];
            //MUDAR O PARALIZAR PARA FALSE

            $bulk->update(
                $filter,
                $inser,
                ['upsert' => true, 'multi' => false]
            );

            $operacoes++;
        }

        try {

            if ($operacoes > 0) {

                $result = $this->manager->executeBulkWrite(
                    "{$this->dbname}.{$db_conect}",
                    $bulk
                );

                return [
                    'success' => true,
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
        } catch (MongoDB\Driver\Exception\Exception $e) {

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    //para inserir o paralizar pegando o finger e data e e id do processo 

    public function insert_all_paralizar($dadosJson)
    {


        $bulk = new MongoDB\Driver\BulkWrite;

        // $bulk = new MongoDB\Driver\BulkWrite;

        $data  = json_decode($dadosJson, true);

        $query = new MongoDB\Driver\Query(
            ['id_processo' => $data['id_processo']],
            ['projection' => ['finger' => 1]]
        );

        $cursor = $this->manager->executeQuery(
            "{$this->dbname}.{$this->db_colletion_json_dados_paralizars}",
            $query
        );

        $document = current($cursor->toArray());
        $finger_inicial = $document->finger ?? null;
        $operacoes = 0;



        if (!is_array($data)) {
            return [
                'success' => false,
                'message' => 'JSON inválido ou não é um array'
            ];
        }


        $filter = ['id_processo' => $data['id_processo']];
        $inser  = [
            '$set' => $data,

            '$push' => [
                'historico_solicitacao_paralizar' => [

                    'data_solicitacao' => new MongoDB\BSON\UTCDateTime(),
                    'finger_old' => $finger_inicial
                ]
            ]
        ];

        $bulk->update(
            $filter,
            $inser,
            ['upsert' => true, 'multi' => false]
        );

        $operacoes++;

        try {

            if ($operacoes > 0) {

                $result = $this->manager->executeBulkWrite(
                    "{$this->dbname}.{$this->db_colletion_json_dados_paralizars}",
                    $bulk
                );

                return [
                    'success' => true,
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
        } catch (MongoDB\Driver\Exception\Exception $e) {

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function insert_all_cancelar($dadosJson)
    {


        $bulk = new MongoDB\Driver\BulkWrite;

        // $bulk = new MongoDB\Driver\BulkWrite;

        print_r($dadosJson);

        $data  = json_decode($dadosJson, true);



        $query = new MongoDB\Driver\Query(
            ['id_processo' => $data['id_processo']],
            ['projection' => ['finger' => 1]]
        );

        $cursor = $this->manager->executeQuery(
            "{$this->dbname}.{$this->db_colletion_json_dados_cancelar}",
            $query
        );

        $document = current($cursor->toArray());
        $finger_inicial = $document->finger ?? null;
        $operacoes = 0;



        if (!is_array($data)) {
            return [
                'success' => false,
                'message' => 'JSON inválido ou não é um array'
            ];
        }


        $filter = ['id_processo' => $data['id_processo']];
        $inser  = [
            '$set' => $data,

            '$push' => [
                'historico_solicitacao_cancelar' => [

                    'data_solicitacao' => new MongoDB\BSON\UTCDateTime(),
                    'finger_old' => $finger_inicial
                ]
            ]
        ];

        $bulk->update(
            $filter,
            $inser,
            ['upsert' => true, 'multi' => false]
        );

        $operacoes++;

        try {

            if ($operacoes > 0) {

                $result = $this->manager->executeBulkWrite(
                    "{$this->dbname}.{$this->db_colletion_json_dados_cancelar}",
                    $bulk
                );

                return [
                    'success' => true,
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
        } catch (MongoDB\Driver\Exception\Exception $e) {

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }


    //BUSCO A DATA PARA NO MONGO PARA SANER 

    public function get_data_paralizar()
    {
        $option = [
            'projection' => [
                'id_processo' => 1,
                'contrato' => 1,
                'paralisado' => 1,
                'data' => 1,
                'data_finalizacao' => 1,
                '_id' => 0
            ]
        ];

        $filtro = [
            'contrato' => [
                '$exists' => true,
                '$ne' => null,
                '$ne' => '' // (ignora vazio)
            ]
        ];


        $query = new MongoDB\Driver\Query($filtro, $option);

        $cursor = $this->manager->executeQuery("{$this->dbname}.{$this->db_colletion_json_dados_paralizars}", $query);
        return iterator_to_array($cursor);
    } //BUSCO A DATA PARA NO MONGO PARA SANER 

    public function get_finger_paralizar($id)
    {

        $filtros = [];
        if (preg_match('/^[a-f0-9]{24}$/i', $id)) {
            $filtros[] = [
                'contrato' => new MongoDB\BSON\ObjectId($id)
            ];
        } else {

            $filtros[] = [
                'contrato'  => $id
                // 'id_processo'  => (string)184,
            ];
        }

        if (empty($filtros)) {
            return [];
        }


        $option = [
            'projection' => [
                'processo_id'    => '$id_processo',    // Use o $ aqui também
                'data_solicitacao' => '$data',           // Seu alias
                'paralisado'     => '$paralisado',     // E aqui...
                'finger_paralisado'         => '$finger',
                'historico_paralisacao'           => '$historico_paralisacao',
                'historico_solicitacao_paralizar' => '$historico_solicitacao_paralizar',
                '_id' => 0
            ]
        ];

        $query = new MongoDB\Driver\Query(
            ['$or' => $filtros],
            $option
        );

        try {


            $cursor = $this->manager->executeQuery("{$this->dbname}.{$this->db_colletion_json_dados_paralizars}", $query);



            $dados = iterator_to_array($cursor);

            foreach ($dados as $item => $values) {

                if ($values->data_solicitacao instanceof MongoDB\BSON\UTCDateTime) {
                    $new_data_solicitacao = $values->data_solicitacao->toDateTime();
                    $values->data_solicitacao = $new_data_solicitacao->format('d/m/Y H:i:s');
                }

                // $values->finger_paralisado =  mb_convert_encoding($values->finger_paralisado, "UTF-8", "ISO-8859-1");
                // $values->finger_paralisado = iconv('UTF-8', 'ASCII//TRANSLIT', $values->finger_paralisado);
                $values->finger_paralisado =  self::removerAcentos($values->finger_paralisado);

                $historico_paralisacao = isset($values->historico_paralisacao) ? $values->historico_paralisacao : null;

                if ($historico_paralisacao !== null) {
                    foreach ($historico_paralisacao as $dados_paralizar) {

                        if ($dados_paralizar->data_solicitacao instanceof MongoDB\BSON\UTCDateTime) {
                            $data = $dados_paralizar->data_solicitacao->toDateTime();
                            $dados_paralizar->data_solicitacao = $data->format('d/m/Y H:i:s');
                        } else {
                            $data = new DateTime($dados_paralizar->data_solicitacao);
                        }

                        // $dados[$dados_paralizar->data_solicitacao] = $data->format('d/m/Y H:i:s');
                    }
                }
            }


            foreach ($dados as $item => $values) {

                $historico_solicitacao_paralizar = isset($values->historico_solicitacao_paralizar) ? $values->historico_solicitacao_paralizar : null;

                if ($historico_solicitacao_paralizar !== null) {
                    foreach ($historico_solicitacao_paralizar as $dados_paralizar_historico) {

                        if ($dados_paralizar_historico->data_solicitacao instanceof MongoDB\BSON\UTCDateTime) {
                            $data = $dados_paralizar_historico->data_solicitacao->toDateTime();
                            $dados_paralizar_historico->data_solicitacao = $data->format('d/m/Y H:i:s');
                        } else {
                            $data = new DateTime($dados_paralizar_historico->data_solicitacao);
                        }

                        // $dados[$dados_paralizar_historico->data_solicitacao] = $data->format('d/m/Y H:i:s');
                    }
                }
            }
            // foreach ($dados as  $item => $values) {

            //     foreach ($values->historico_paralisacao as $item => $dados_paralizar) {

            //         if ($dados_paralizar->data_solicitacao instanceof MongoDB\BSON\UTCDateTime) {
            //             $data = $dados_paralizar->data_solicitacao->toDateTime();
            //             $dados_paralizar->data_solicitacao = $data->format('d/m/Y H:i:s');
            //         } else {
            //             $data = new DateTime($dados_paralizar->data_solicitacao->data_solicitacao);
            //         }

            //         $dados[$dados_paralizar->data_solicitacao] = $data->format('d/m/Y H:i:s');
            //     }

            //     foreach ($values->historico_solicitacao_paralizar as $item => $historico_solicitacao_paralizar) {

            //         if ($historico_solicitacao_paralizar->data_solicitacao instanceof MongoDB\BSON\UTCDateTime) {
            //             $data = $historico_solicitacao_paralizar->data_solicitacao->toDateTime();
            //             $historico_solicitacao_paralizar->data_solicitacao = $data->format('d/m/Y H:i:s');
            //         } else {
            //             $data = new DateTime($historico_solicitacao_paralizar->data_solicitacao->data_solicitacao);
            //         }

            //         $dados[$historico_solicitacao_paralizar->data_solicitacao] = $data->format('d/m/Y H:i:s');
            //     }
            // }

            return $dados ?: null;
        } catch (MongoDB\Driver\Exception\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    public function get_finger_info_reprocess($id)
    {
        $filtros = [];
        if (preg_match('/^[a-f0-9]{24}$/i', $id)) {
            $filtros[] = [
                'id_process' => new MongoDB\BSON\ObjectId($id)
            ];
        } else {

            $filtros[] = [
                'id_process'  => (string)$id
                // 'id_processo'  => (string)184,
            ];
        }

        if (empty($filtros)) {
            return [];
        }


        $option = [
            'projection' => [
                'id_process' => 1,
                'contrato' => 1,
                'requested' => 1,
                'reprocessado_day' => 1,
                'new_id_process' => 1,
                '_id' => 0
            ]
        ];

        $query = new MongoDB\Driver\Query(
            ['$or' => $filtros],
            $option
        );

        try {

            $cursor = $this->manager->executeQuery("{$this->dbname}.{$this->db_colletion_json_dados}", $query);

            $dados = iterator_to_array($cursor);

            return $dados ?: null;
        } catch (MongoDB\Driver\Exception\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function get_finger_parar_reprocessar($id)
    {


        $filtros = [];
        if (preg_match('/^[a-f0-9]{24}$/i', $id)) {
            $filtros[] = [
                'id_process' => new MongoDB\BSON\ObjectId($id)
            ];
        } else {

            $filtros[] = [
                //busca por um string e como esta salvo dentro do mongo
                'id_process' => (string)$id
            ];
        }

        if (empty($filtros)) {
            return [];
        }



        $option = [
            'projection' => [
                'processo_id'    => '$id_process',
                'data_solicitacao_parar' => '$data_alteracao',
                'finger' => '$info_auditoria_finger',
                'paralizar' => '$paralizar_processos',
                '_id' => 0
            ]
        ];

        $query = new MongoDB\Driver\Query(
            ['$or' => $filtros],
            $option
        );

        try {
            //collection finger
            $cursor = $this->manager->executeQuery("{$this->dbname}.{$this->db_colletion_json_dados}", $query);


            $result = iterator_to_array($cursor);

            foreach ($result as $key => $values) {


                $values->finger = self::removerAcentos($values->finger);


                $result[$key] = $values;
            };

            return $result ?: null;
        } catch (MongoDB\Driver\Exception\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function insert_all_paralizar_reprocesar_jobs($id, $paralisadoAtual, $acao, $fingerUsuario)
    {
        $bulk = new MongoDB\Driver\BulkWrite;

        $query = new MongoDB\Driver\Query(
            ['id_processo' => $id],
            ['projection' => ['data' => 1]]
        );

        $cursor = $this->manager->executeQuery(
            "{$this->dbname}.{$this->db_colletion_json_dados_paralizars}",
            $query
        );

        $document = current($cursor->toArray());
        $dataInicial = $document->data ?? null;

        $filter = ['id_processo' => $id];

        $update = [
            '$set' => [
                'paralisado' => $paralisadoAtual,
                'data' => null
            ],
            '$push' => [
                'historico_paralisacao' => [
                    'acao' => $acao == 1 ? 'paralisar' : 'desparalisar',
                    'data_solicitacao' => new MongoDB\BSON\UTCDateTime(),
                    'finger' => $fingerUsuario,
                    'dataInicial_paralisazao' => $dataInicial
                ]
            ]
        ];

        $bulk->update(
            $filter,
            $update,
            ['upsert' => true, 'multi' => false]
        );

        try {
            $result = $this->manager->executeBulkWrite(
                "{$this->dbname}.{$this->db_colletion_json_dados_paralizars}",
                $bulk
            );
            return [
                'success' => true,
                'inserted' => $result->getInsertedCount(),
                'modified' => $result->getModifiedCount(),
                'upserted' => $result->getUpsertedCount(),
                'matched'  => $result->getMatchedCount(),
            ];
        } catch (MongoDB\Driver\Exception\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function busca_dados_finger_parar($id)
    {
        $bulk = new MongoDB\Driver\BulkWrite;

        $query = new MongoDB\Driver\Query(
            ['id_process' => $id],
            ['projection' => ['data_alteracao' => 1, '']]
        );


        try {
            $cursor = $this->manager->executeQuery(
                "{$this->dbname}.{$this->db_colletion_json_dados}",
                $query
            );
            $document = current($cursor->toArray());
            $data_alteracao = $document->data_alteracao ?? null;
            return $data_alteracao;
        } catch (MongoDB\Driver\Exception\Exception $e) {

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function get_dados_parar($dados)
    {
        $bulk = new MongoDB\Driver\BulkWrite;

        $filtros = [];

        foreach ($dados as $values) {

            if (preg_match('/^[a-f0-9]{24}$/i', $values['processo_id'])) {

                $filtros[] = [
                    'id_process' => new MongoDB\BSON\ObjectId($values['processo_id'])
                ];
            } else {

                $filtros[] = [
                    'id_process'  => (string)$values['processo_id'],
                ];
            }
        }

        if (empty($filtros)) {
            return [];
        }

        $option = ['projection' => [
            '_id' => 1,
            'id_process' => 1,
            'data_alteracao' => 1,
            'info_auditoria_finger' => 1,
            'paralizar_processos' => 1,
            'status' => 1
        ]];

        $query = new MongoDB\Driver\Query(
            ['$or' => $filtros],
            $option
        );

        try {
            //collection finger
            $cursor = $this->manager->executeQuery("{$this->dbname}.{$this->db_colletion_json_dados}", $query);

            return iterator_to_array($cursor);
        } catch (MongoDB\Driver\Exception\Exception $e) {

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    public function get_dados_info_reprocess($dados)
    {
        $filtros = [];

        if (preg_match('/^[a-f0-9]{24}$/i', $dados)) {
            $filtros[] = [
                'id_process' => new MongoDB\BSON\ObjectId($dados)
            ];
        } else {
            $filtros[] = [
                'id_process' => (string)$dados,
            ];
        }

        $option = [
            'projection' => [
                'info_reprocess' => 1,
                'msg' => 1,
                'data_alteracao' => 1,
                '_id' => 0
            ]
        ];

        $query = new MongoDB\Driver\Query(
            ['$or' => $filtros],
            $option
        );

        try {

            $cursor = $this->manager->executeQuery(
                "{$this->dbname}.{$this->db_colletion_json_dados}",
                $query
            );

            $result = current($cursor->toArray());

            return $result ?: null;
        } catch (MongoDB\Driver\Exception\Exception $e) {

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function get_dados_info_paralizar_die($dados)
    {
        $filtros = [];

        if (preg_match('/^[a-f0-9]{24}$/i', $dados)) {
            $filtros[] = [
                'id_processo' => new MongoDB\BSON\ObjectId($dados)
            ];
        } else {
            $filtros[] = [
                'id_processo' => (string)$dados,
            ];
        }

        $option = [
            'projection' => [
                'data_finalizacao' => 1,
                'processo_finalizado' => 1,

                '_id' => 0
            ]
        ];

        $query = new MongoDB\Driver\Query(
            ['$or' => $filtros],
            $option
        );

        try {

            $cursor = $this->manager->executeQuery(
                "{$this->dbname}.{$this->db_colletion_json_dados_paralizars}",
                $query
            );

            $result = current($cursor->toArray());

            if ($result && !empty($result->data_finalizacao->date)) {

                $data = new DateTime($result->data_finalizacao->date);
                $dataFormatada = $data->format('d/m/Y H:i:s');

                $result->data_finalizacao = $dataFormatada;
            }

            $tamanho = count(get_object_vars($result));

            return $tamanho  > 0 ? $result : null;
        } catch (MongoDB\Driver\Exception\Exception $e) {

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }


    public function get_fingers_cancelar($contrato)
    {

        $filtros = [];
        if (preg_match('/^[a-f0-9]{24}$/i', $contrato)) {
            $filtros[] = [
                'contrato' => new MongoDB\BSON\ObjectId($contrato)
            ];
        } else {

            $filtros[] = [
                'contrato'  => $contrato

            ];
        }

        if (empty($filtros)) {
            return [];
        }

        $option = [
            'projection' => [
                'processo_id'    => '$id_processo',
                'data_cancelado' => '$data',
                'cancelado' => '$cancelado',
                // 'contrato' => '$cont',
                'finger' => '$finger',


                '_id' => 0
            ]
        ];

        $query = new MongoDB\Driver\Query(
            ['$or' => $filtros],
            $option
        );

        try {

            $cursor = $this->manager->executeQuery(
                "{$this->dbname}.{$this->db_colletion_json_dados_cancelar}",
                $query
            );

            $result = $cursor->toArray();


            if ($result && !isset($result->data_cancelado)) {

                foreach ($result as $key => $values) {

                    if ($values->data_cancelado instanceof MongoDB\BSON\UTCDateTime) {
                        $data = $values->data_cancelado->toDateTime();
                        $dataFormatada = $data->format('Y/m/d H:i:s');
                        $result->data_cancelado = $dataFormatada;
                    }

                    $values->finger = self::removerAcentos($values->finger);


                    $result[$key] = $values;
                }
            }


            if (isset($result) && !empty($result)) {
                $tamanho = count($result);
            } else {
                $tamanho = 0;
            }



            return $tamanho  > 0 ? $result : null;
        } catch (MongoDB\Driver\Exception\Exception $e) {

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    function removerAcentos($texto)
    {
        return preg_replace(array("/(á|à|ã|â|ä)/", "/(Á|À|Ã|Â|Ä)/", "/(é|è|ê|ë)/", "/(É|È|Ê|Ë)/", "/(í|ì|î|ï)/", "/(Í|Ì|Î|Ï)/", "/(ó|ò|õ|ô|ö)/", "/(Ó|Ò|Õ|Ô|Ö)/", "/(ú|ù|û|ü)/", "/(Ú|Ù|Û|Ü)/", "/(ñ)/", "/(Ñ)/"), explode(" ", "a A e E i I o O u U n N"), $texto);
    }

    public function prePaggoInfo($dados)
    {
        // $this->db_colletion_json_dados_prepago


        $bulk = new MongoDB\Driver\BulkWrite;

        $dados = json_decode($dados);

        $query = new MongoDB\Driver\Query(
            ['id_processo' => $dados->id_processo],
            [
                'projection' => [
                    'data_solicitacao' => 1,
                    'info_pagamento' => 1,
                    'status' => 1,
                    '_id' => 0
                ]
            ]
        );

        $cursor = $this->manager->executeQuery(
            "{$this->dbname}.{$this->db_colletion_json_dados_prepago}",
            $query
        );

        $document = current($cursor->toArray());
        $dataInicial = $document->data_solicitacao ?? null;
        $info_pagamento = $document->info_pagamento ?? null;
        $status = $document->status ?? null;

        $info = true;

        $filter = ['id_processo' => $dados->id_processo];

        $historico = [
            'acao_prepago' => $dados->info_pagamento == 1 ? true : false,
            'data_solicitacao' => empty($dataInicial) ? $dados->data_solicitacao : $dataInicial,
            'info_pagamento' => empty($info_pagamento) ? $dados->info_pagamento : $info_pagamento,
            'status' => empty($status) ? $dados->status : $status,
        ];

        // 2. Adicionamos a data apenas se a condição ($info) for atendida
        if ($info) {
            $historico['data_process_pagamento'] = new MongoDB\BSON\UTCDateTime();
        }
        // 3. Montamos o update final
        $update = [
            '$set' => array_merge(
                (array) $dados,
                [
                    'info_pagamento' => $info ? true : $info_pagamento,
                    'status' => $info ? 0 : $status,
                ]
            ),
            '$push' => [
                'historico_solicitacao_preapago' => $historico,
            ]
        ];
        $bulk->update(
            $filter,
            $update,
            ['upsert' => true, 'multi' => false]
        );

        try {
            $result = $this->manager->executeBulkWrite(
                "{$this->dbname}.{$this->db_colletion_json_dados_prepago}",
                $bulk
            );
            $da = [
                'success' => true,
                'inserted' => $result->getInsertedCount(),
                'modified' => $result->getModifiedCount(),
                'upserted' => $result->getUpsertedCount(),
                'matched'  => $result->getMatchedCount(),
            ];
            echo "<pre>";

            print_R($da);
        } catch (MongoDB\Driver\Exception\Exception $e) {

            // echo $e->getMessage();
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function get_prePago_info()
    {

        $bulk = new MongoDB\Driver\BulkWrite;

        $option = [
            'projection' => [
                'id_processo'    => 1,
                'contrato' => 1,
                'info_pagamento' => 1,
                'status' => 1,
                '_id' => 0
            ]
        ];

        //filtro acima para o que for maior
        // $filter = ['status' => ['$gt' => 18]];
        //COLO O FILTRO PARA TRAZER OS DADOS SE O E SE FOR TRUE, PARA QUE POSSA ATUALIZAR OS DADOS DENTRO DA BASE

        // $filter = ['status' => ['$eq' => 18], 'info_pagamento' => false];

        $query = new MongoDB\Driver\Query(
            // $filter,
            [],
            $option,

        );

        try {

            $cursor = $this->manager->executeQuery(
                "{$this->dbname}.{$this->db_colletion_json_dados_prepago}",
                $query
            );
            $result = $cursor->toArray();

            if (count($result) > 0) {
                return $result;
            }


            return false;
        } catch (\MongoDB\Driver\Exception\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
