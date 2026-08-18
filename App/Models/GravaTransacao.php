<?php

namespace App\Models;

use PDO;
use Core\Model;
use Core\Logs;
use PDOException;
use Core\Functions;
use Core\AppManipularError;



class GravaTransacao extends Model
{

	protected $funciton;
	protected $manipulador;
	##[Override]
	public function __construct()
	{
	 parent::__construct();

		$this->funciton = new Functions();

		$this->manipulador = new AppManipularError(
			__DIR__ . '/../../error/error_insert.txt'
		);
	}

	public function execute($processoId, $campoAquisicao, $status = 0, $sucesso = true, $resposta = null, $respostaJson = null)
	{


		echo "<pre>";
		print_R($campoAquisicao);

		ini_set('memory_limit', '1024M');




		$sql = "INSERT INTO progestor.log_transacao (
				id_processo, campo_aquisicao, status, sucesso, resposta, resposta_json)
				VALUES (?, ?, ?, ?, ?, ?) RETURNING id_processo;";

		$dados = array();
		$dados[] = $processoId;
		$dados[] = $campoAquisicao;
		$dados[] = $status;
		$dados[] = $sucesso;
		$dados[] = $resposta;
		$dados[] = $respostaJson;

		$result = $this->db->prepare($sql);


		echo "<pre>";
		try {
			$result->execute($dados);
			$idFake = $result->fetchColumn();
			// echo ' Minha  quantidade inseriada' .	$idFake . "\n";

			return $idFake;
		} catch (PDOException $e) {
			$this->manipulador->manipuladorDeErros(
				$e->getCode(),
				$e->getMessage(),
				$e->getFile(),
				$e->getLine()
			);
			echo "ERRO AO INSERIR: " . $e->getMessage() . "\n";
			print_r($dados);
		}
		// print_r($dados);





		// echo "O banco aceitou o INSERT. ID gerado (não será salvo): $idFake\n";

		// $this->db->rollBack();




		// echo "<pre>";
		// echo "Meu ultimo id " . $this->db->lastInsertId();
	}
	public function insertBatch(array $registros)
	{
		if (empty($registros)) {
			return false;
		}
		if (!$this->db instanceof PDO) {
    throw new RuntimeException('Conexão PDO não inicializada.');
}

		// Inicia a transação (insert em lote)
		$this->db->beginTransaction();

		try {
			$sql = "INSERT INTO progestor.log_transacao (
				id_processo, campo_aquisicao, status, sucesso, resposta, resposta_json)
				VALUES ";

			$values = [];
			$params = [];

			foreach ($registros as $i => $r) {
				// Define valores padrão (como o execute)
				$processoId     = $r['processo_id']     ?? null;
				$campoAquisicao = $r['camposAquisicao'] ?? null;
				$status         = $r['status']          ?? 0;
				$sucesso        = $r['sucesso']         ?? true;
				$resposta       = $r['resposta']        ?? null;
				$respostaJson   = $r['resposta_json']   ?? null;

				$values[] = "(?, ?, ?, ?, ?, ?)";
				array_push($params, $processoId, $campoAquisicao, $status, $sucesso, $resposta, $respostaJson);
			}

			// Monta a query completa
			$sql .= implode(", ", $values);

			// Executa em um único INSERT
			$stmt = $this->db->prepare($sql);
			$stmt->execute($params);

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
			throw $e;
		}
	}
}
