<?php

namespace App\Core;

use Exception;

use MongoDB\Client;
use PDO;
use PDOException;
use Core\MailClass;
// use Core\Database;
use Core\AppManipularError;

class MongoConect
{
    protected $mail;
    protected $manipulador;

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

        $this->mail = new MailClass();
        $this->manipulador = new AppManipularError(__DIR__ . '/../error/error_banco_mongo.txt');

        $host =  $_ENV['BD_MONGO_HOST'];
        var_dump($host);
        $port =  $_ENV['BD_MONGO_PORT'] ?: 27017;
        $user =  $_ENV['BD_MONGO_USER'];
        $pass =  $_ENV['BD_MONGO_PASS'];
        $BD_MONGO_BD_AUTH_SOURCE = $_ENV['BD_MONGO_BD_AUTH_SOURCE'] ?? 'admin';

        $this->dbname =  $_ENV['BD_MONGO_BD_NAME'];
        $this->db_colletion =  $_ENV['BD_MONGO_BD_COLLETION'];
        $this->db_colletion_json =  $_ENV['BD_MONGO_BD_COLLETION_JSON'];
        $this->db_colletion_info =  $_ENV['BD_MONGO_BD_COLLETION_INFO'];
        $this->db_colletion_jobs =  $_ENV['BD_MONGO_BD_COLLETION_JOBS'];
        $this->db_colletion_json_dados =  $_ENV['BD_MONGO_BD_COLLETION_JSON_DADOS'];
        $this->db_colletion_json_dados_reprocess =  $_ENV['BD_MONGO_BD_COLLETION_JSON_DADOS_REPROCESS'];
        $this->db_colletion_json_dados_paralizar =  $_ENV['BD_MONGO_BD_COLLETION_JSON_DADOS_PARALIZAR'];
        $this->db_colletion_json_dados_cancelar =  $_ENV['BD_MONGO_BD_COLLETION_JSON_DADOS_CANCELAR'];
        // $this->db_colletion_json_dados_prepago =  $_ENV['BD_MONGO_BD_COLLETION_JSON_DADOS_PREPAGO'];

        $auth = $user ? "$user:$pass@" : "";
        $uri = "mongodb://{$auth}{$host}:{$port}/{$user}?authSource={$BD_MONGO_BD_AUTH_SOURCE}";

        $options = [
            "tls" => true,
            "tlsAllowInvalidCertificates" => true,
        ];

        try {
            // 1. Instancia o cliente (apenas guarda a configuração)
            $this->manager = new Client($uri, $options);

            // 2. FORÇA A CONEXÃO REAL (Faz um ping no banco)
            // Sem isso, o catch NUNCA vai capturar quedas de servidor ou senhas erradas nesta etapa
            $this->manager->selectDatabase('admin')->command(['ping' => 1]);
        } catch (Exception $e) {

            $manipulador = new AppManipularError(__DIR__ . '/../error/error_banco_mongo.txt');

            $assunto = $_ENV['SMTP_SUBJECT'] ?? getenv('SMTP_SUBJECT') ?? 'Erro de conexão com o banco';

            $destinatario = $_ENV['SMTP_DESTINATION'] ?? getenv('SMTP_DESTINATION') ?? null;
            $corpo = "Falha crítica de conexão com o MongoDB:\n\n" . $e->getMessage();

            // Envia o e-mail imediatamente
            if (!empty($destinatario)) {
                $mail = new MailClass();
                $mail->enviar_email($destinatario, $assunto, $corpo);
            }

            $manipulador->manipuladorDeErros(
                $e->getCode(),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            );

            die("Erro ao conectar ao MongoDB : " . $e->getMessage());
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
    public function testarConexao()
    {
        try {
            // Seleciona o banco admin para rodar o comando de ping
            $db = $this->manager->selectDatabase('admin');

            // Executa o comando ping
            $cursor = $db->command(['ping' => 1]);
            $resultado = $cursor->toArray();

            // Se retornou ok, a conexão está funcionando
            if (isset($resultado[0]['ok']) && $resultado[0]['ok'] == 1) {
                return true;
            }

            return false;
        } catch (Exception $e) {
            // Registra o erro se falhar
            $this->manipulador->manipuladorDeErros(
                $e->getCode(),
                "Falha no teste de conexão: " . $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            );
            return false;
        }
    }
}
