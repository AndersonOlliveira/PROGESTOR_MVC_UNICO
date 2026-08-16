<?php

namespace App\Core;

use Exception;
use Dotenv\Dotenv;
use MongoDB\Client;
// use MongoDB\Driver\Manager;
// use MongoDB\Driver\BulkWrite;
// use MongoDB\Driver\Exception\Exception;

class MongoConect
{
    private static $instance = null;
    private $client;
    private $dbname;
    private $manager;
    private $db_colletion;
    private $db_colletion_json;
    private $db_colletion_info;
    private $db_colletion_jobs;
    private $manager_local;
    private $db_colletion_json_dados;
    private $db_colletion_json_dados_reprocess;
    private $db_colletion_json_dados_paralizar;
    private $db_colletion_json_dados_cancelar;
    private $db_colletion_json_dados_prepago;

    private function __construct()
    {
        // O vlucas/phpdotenv carregado via Composer substitui chamadas manuais se necessário.
        // Certifique-se de que a classe Env está mapeada ou use: \Dotenv\Dotenv::createImmutable(__DIR__ . '/../../')->load();

        $host = getenv('BD_MONGO_HOST');
        $port = getenv('BD_MONGO_PORT') ?: 27017;
        $user = getenv('BD_MONGO_USER');
        $pass = getenv('BD_MONGO_PASS');
        $BD_MONGO_BD_AUTH_SOURCE = getenv('BD_MONGO_BD_AUTH_SOURCE');

        $this->dbname = getenv('BD_MONGO_BD_NAME');
        $this->db_colletion = getenv('BD_MONGO_BD_COLLETION');
        $this->db_colletion_json = getenv('BD_MONGO_BD_COLLETION_JSON');
        $this->db_colletion_info = getenv('BD_MONGO_BD_COLLETION_INFO');
        $this->db_colletion_jobs = getenv('BD_MONGO_BD_COLLETION_JOBS');
        $this->db_colletion_json_dados = getenv('BD_MONGO_BD_COLLETION_JSON_DADOS');
        $this->db_colletion_json_dados_reprocess = getenv('BD_MONGO_BD_COLLETION_JSON_DADOS_REPROCESS');
        $this->db_colletion_json_dados_paralizar = getenv('BD_MONGO_BD_COLLETION_JSON_DADOS_PARALIZAR');
        $this->db_colletion_json_dados_cancelar = getenv('BD_MONGO_BD_COLLETION_JSON_DADOS_CANCELAR');
        $this->db_colletion_json_dados_prepago = getenv('BD_MONGO_BD_COLLETION_JSON_DADOS_PREPAGO');

        $auth = $user ? "$user:$pass@" : "";
        $uri = "mongodb://{$auth}{$host}:{$port}/{$user}?authSource={$BD_MONGO_BD_AUTH_SOURCE}";

        $options = [
            "tls" => true,
            "tlsAllowInvalidCertificates" => true,
        ];

        try {
            // Agora usamos o Client do Composer em vez do Manager nativo
            $this->manager = new Client($uri, $options);
        } catch (Exception $e) {
            die("Erro ao conectar ao MongoDB via Composer: " . $e->getMessage());
        }
    }


    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new MongoConect();
        }
        return self::$instance;
    }

    public function getManager()
    {
        return $this->manager;
    }

    public function getDBName()
    {
        return $this->dbname;
    }

    public function getDBColetion()
    {
        return $this->db_colletion;
    }

    public function getDBColetion_json()
    {
        return $this->db_colletion_json;
    }

    public function getDBColetion_info()
    {
        return $this->db_colletion_info;
    }

    public function getManager_local()
    {
        return $this->manager_local;
    }

    public function getDBColetion_jobs()
    {
        return $this->db_colletion_jobs;
    }

    public function getDBColetion_jobs_dados_json()
    {
        return $this->db_colletion_json_dados;
    }

    public function getDBColetion_jobs_dados_json_reprocess()
    {
        return $this->db_colletion_json_dados_reprocess;
    }

    public function getDBColetion_jobs_dados_paralizar()
    {
        return $this->db_colletion_json_dados_paralizar;
    }

    public function getDBColetion_jobs_dados_cancelar()
    {
        return $this->db_colletion_json_dados_cancelar;
    }

    public function getDBColetion_jobs_dados_pre_pago()
    {
        return $this->db_colletion_json_dados_prepago;
    }

    public function saveLog($data)
    {
        try {
            // O Composer permite selecionar banco e coleção dinamicamente como propriedades/métodos
            // Substitua 'nome_banco' e 'nome_tabela_log' pelas suas variáveis ou strings reais
            $collection = $this->client->selectCollection($this->dbname, 'nome_tabela_log');
            $collection->insertOne($data);
        } catch (Exception $e) {
            echo "Erro ao salvar log: " . $e->getMessage();
        }
    }
    public function getClient()
    {
        return $this->client;
    }
}
