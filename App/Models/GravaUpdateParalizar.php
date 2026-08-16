<?php


namespace App\Models;

use PDO;
use Core\Model;
use Core\Logs;
use PDOException;
use Core\Functions;
use Core\AppManipularError;


class GravaUpdateParalizar extends Model
{

    public function insertBatch(array $dados)
    {


        if (empty($dados)) {
            return false;
        }

        // echo "<pre>";

        // print_R('vim parar nesta tela');

        // var_dump($dados);


        $this->db->beginTransaction();

        try {

            $i = 0;

            $sql = "UPDATE progestor.transacao 
             SET status = ? WHERE id_processo = ? 
             AND transacao_id = ? ";

            $stmt = $this->db->prepare($sql);

            foreach ($dados['ids'] as $r) {

                $stmt->execute([17, $r['id_processo'], $r['transacao_id']]);

                $i++;

                // A cada 200 registros, faz commit e abre nova transação
                if ($i % 200 == 0) {
                    $this->db->commit();
                    $this->db->beginTransaction();
                }
            }

            // Commit final (caso não seja múltiplo de 200)
            $this->db->commit();
        } catch (PDOException $e) {
            $this->db->rollBack();

            throw $e;
        }
    }
}
