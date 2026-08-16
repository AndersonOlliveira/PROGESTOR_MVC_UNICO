<?php


namespace App\Models;

use PDO;
use Core\Model;
use Core\Logs;
use PDOException;
use Core\Functions;
use Core\AppManipularError;

ini_set('display_errors', 1);
error_reporting(E_ALL);

class GravarUpdateDieProcess extends Model
{

    protected $filtros;

    public function __construct()
    {
        parent::__construct();
        // require_once __DIR__ . '/../models/process.php';
        // $this->filtros = new process();
    }

    public function UpdateBatchDieProcess($dados)
    {

        if (empty($dados)) {
            return false;
        }

        $this->db->beginTransaction();

        $ids_transation = $this->filtros->list_transation($dados);

        try {

            $i = 0;
            $sql = "";
            $sql = "  UPDATE progestor.transacao 
             SET status = ? WHERE id_processo = ? 
             AND transacao_id = ? ";

            $stmt = $this->db->prepare($sql);


            foreach ($ids_transation as $values) {

                $stmt->execute([6, $values['processo_id'], $values['transacao_id']]);

                $i++;

                // A cada 200 registros, faz commit e abre nova transação
                if ($i % 200 == 0) {
                    $this->db->commit();
                    $this->db->beginTransaction();
                }
            }

            // Commit final (caso não seja múltiplo de 200)
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
