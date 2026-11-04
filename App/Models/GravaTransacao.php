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

    $this->db->beginTransaction();

    try {
        $lotesDeRegistros = array_chunk($registros, 3000);

        foreach ($lotesDeRegistros as $lote) {
            $sql = "INSERT INTO progestor.log_transacao (
                id_processo, campo_aquisicao, status, sucesso, resposta, resposta_json)
                VALUES ";

            $values = [];
            $params = [];

            foreach ($lote as $r) {
                $processoId     = $r['processo_id']     ?? null;
                $campoAquisicao = $r['camposAquisicao'] ?? null;
                $status         = $r['status']          ?? 0;
                $sucesso        = $r['sucesso']         ?? true;
                $resposta       = $r['resposta']        ?? null;
                $respostaJson   = $r['resposta_json']   ?? null;

                $values[] = "(?, ?, ?, ?, ?, ?)";
                array_push($params, $processoId, $campoAquisicao, $status, $sucesso, $resposta, $respostaJson);
            }

            // Monta e executa a query específica deste lote
            $sql .= implode(", ", $values);
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
        }

        $this->db->commit();
        return true;
    } catch (PDOException $e) {
        $this->db->rollBack();
        
        $this->manipulador->manipuladorDeErros(
            $e->getCode(),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        );
        throw $e;
    }
}
}
