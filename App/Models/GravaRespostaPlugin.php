<?php

namespace App\Models;

use PDO;
use Core\Model;
use Core\Logs;
use PDOException;
use RuntimeException;
use Core\Functions;
use Core\AppManipularError;

class GravaRespostaPlugin extends Model
{
    protected $funciton;
    protected $manipulador;
    ##[Override]
    public function __construct()
    {
        $this->funciton  = new Functions();
        $this->manipulador = new AppManipularError(__DIR__ . '/../../error/error_insert.txt');
        parent::__construct();
    }

    public  function execute($plugin, $resposta, $transacaoId, $header = "")
    {
        ini_set('memory_limit', '1024M');
        if (!self::existe($plugin, $resposta, $transacaoId)) { // grava se não existir no banco


            $sql = "INSERT INTO progestor.respostas_plugin (
					id_transacao, plugin, resposta, header_arquivo)
					VALUES (?, ?, ?, ?);";

            $dados = array();
            $dados[] = $transacaoId;
            $dados[] = $plugin;
            $dados[] = $resposta;
            $dados[] = $header;

            $result = $this->db->prepare($sql);

            $result->execute($dados);
        }
    }

    private  function existe($plugin, $resposta, $transacaoId)
    {


        $sql = "SELECT respostas_id FROM progestor.respostas_plugin WHERE id_transacao = ? and plugin = ? and resposta = ? LIMIT 1";

        $dados = array();
        $dados[] = $transacaoId;
        $dados[] = $plugin;
        $dados[] = $resposta;

        $result = $this->db->prepare($sql);

        $result->execute($dados);

        $existe = false;
        if ($row = $result->fetch(PDO::FETCH_ASSOC)) {

            if (!is_null($row['respostas_id'])) {
                $existe = true;
            }
        }

        return $existe;
    }

    public function insert_all_Respost_pluglin(array $registros)
    {
        
    if (empty($registros)) {

            return false;
        }

        // Inicia a transação (insert em lote)
        $this->db->beginTransaction();


        $values = [];
        $params = [];

        foreach ($registros as $i => $r) {


            $id_transacao = (int)$r['transacaoId']  ?? null;
            $plugin =  (int)$r['plugin'] ?? null;
            $resposta   = $r['resposta']  ?? null;
            $header_arquivo  = $r['header']   ?? null;


            $values[] = "(?, ?, ?,?)";
            array_push($params, $id_transacao, $plugin, $resposta, $header_arquivo);
        }

        try {
            //busca como o ttipo integer colunas do banco estão definidas assim
            $sql = "
            INSERT INTO progestor.respostas_plugin (id_transacao, plugin, resposta, header_arquivo)
            SELECT v.id_transacao::integer, v.plugin::integer, v.resposta::text, v.header_arquivo::text

            FROM (VALUES " . implode(", ", $values) . ") 
                AS v(id_transacao, plugin, resposta, header_arquivo)
            WHERE NOT EXISTS (
                SELECT 1 FROM progestor.respostas_plugin r
                WHERE r.id_transacao = v.id_transacao::integer AND r.plugin = v.plugin::integer
            )RETURNING respostas_id;
        ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $idGerado = $stmt->fetchColumn();
            $INFO_ID  = 'MEU ID DO RESPOSTA PUBLIGIN ' . ($idGerado ?: 'NÃO INSERIU / DUPLICATA');
            $mensagem_erro['ID'] = $INFO_ID;

            $caminho_log = "./meu_log_de_erros.log"; // O caminho do arquivo
            error_log(print_r($mensagem_erro, true), 3, $caminho_log);
            $this->db->commit();
            return true;


        } catch (PDOException $e) {

            $this->manipulador->manipuladorDeErros(
                $e->getCode(),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            );
            $this->db->rollBack();
            $INFO_ID  = 'ERRO INSERT PLUGLIN ' . $e->getMessage();
            $mensagem_erro['ERRO-PLUGIN'] = $INFO_ID;

            echo "<pre>";
            print($mensagem_erro);
            $caminho_log = "./meu_log_de_erros.log"; // O caminho do arquivo
            error_log(print_r($mensagem_erro, true), 3, $caminho_log);

            throw $e;
        }
    }
    
    public function update_all_Respost_pluglin(array $registros)
{
  

    if (empty($registros)) {
        return false;
    }

    try {

        $this->db->beginTransaction();

        $sql = "
            UPDATE progestor.respostas_plugin
            SET header_arquivo = :header
            WHERE id_transacao = :id_transacao
              AND plugin = :plugin
        ";

        $stmt = $this->db->prepare($sql);

        $totalAtualizados = 0;

        foreach ($registros as $r) {

            $id_transacao = isset($r['transacaoId'])
                ? (int) $r['transacaoId']
                : null;

            $plugin = isset($r['plugin'])
                ? (int) $r['plugin']
                : null;

            $header_arquivo = $r['header_'] ?? null;

            if ($id_transacao === null || $plugin === null) {
                continue;
            }

            $stmt->execute([
                ':header'       => $header_arquivo,
                ':id_transacao' => $id_transacao,
                ':plugin'       => $plugin
            ]);

            $totalAtualizados += $stmt->rowCount();
        }

        $this->db->commit();

        $mensagem_erro = [
            'INFO' => 'UPDATE RESPOSTA PLUGIN',
            'TOTAL_ATUALIZADOS' => $totalAtualizados
        ];

        $caminho_log = "./meu_log_de_erros.log";

        error_log(
            print_r($mensagem_erro, true),
            3,
            $caminho_log
        );

        return true;

    } catch (PDOException $e) {

        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }

        $this->manipulador->manipuladorDeErros(
            $e->getCode(),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        );

        $mensagem_erro = [
            'ERRO-PLUGIN' => 'ERRO UPDATE PLUGIN',
            'MENSAGEM' => $e->getMessage()
        ];

        $caminho_log = "./meu_log_de_erros.log";

        error_log(
            print_r($mensagem_erro, true),
            3,
            $caminho_log
        );

        throw $e;
    }
}
}
