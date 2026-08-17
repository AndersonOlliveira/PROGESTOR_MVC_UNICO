<?php


namespace App\Models;

use PDO;
use Core\Model;
use Core\Logs;
use PDOException;
use Exception;
use Core\Functions;
use Core\AppManipularError;
use App\Utilis\Model_ConsultaPrePago_Action_pretpsaldo;



class CapturaCamposConsultas extends Model
{

    public function Consultation_new_header_validades($dados, $headers)
    {




        $description = array_map('trim', explode(';', $headers));

        $orderBy = "CASE cpovar ";
        foreach ($description as $i => $campo) {
            $campo = addslashes($campo);
            $orderBy .= "WHEN '{$campo}' THEN {$i} ";
        }
        $orderBy .= "END";

        $ids = $dados;
        $idConsultation = array_map('intval', $dados);
        $ids = implode(',', $ids);
        $placeholders = implode(',', array_fill(0, count($idConsultation), '?'));

        $sql = "";
        $sql = "SELECT
        	DISTINCT cpovar, cpodsc, regcod,
             {$orderBy} AS ordem
            FROM
            rdecns inner join 
            rdecnsreg on rdecnsid = rdecnsregrdecns inner join 
            regcpo on rdecnsregreg = regcporeg  inner join
            cpo on cpoid = regcpocpo
            inner join
            reg on regid = rdecnsregreg
            WHERE rdecnsid IN ($placeholders)
            AND cpo.cpovar IN (" . implode(',', array_fill(0, count($description), '?')) . ")
            ORDER BY ordem, regcod";

        try {

            $params = array_merge(
                $idConsultation,
                $description
            );



            $results = $this->db->prepare($sql);
            $results->execute($params);

            $cpovar = [];
            $dados_geral = [];
            $cpodsc = [];
            $plugin = [];



            if ($results->rowCount() > 0) {

                while ($row = $results->fetch(PDO::FETCH_ASSOC)) {

                    $cpovar[] = trim($row['cpovar']);

                    $dados_geral[] = [
                        $row['cpovar'],
                        $row['regcod'],
                        mb_convert_encoding($row['cpodsc'], 'UTF-8', 'ISO-8859-1')
                    ];
                    $plugin[] = $row['regcod'];
                    $cpodsc[] = mb_convert_encoding($row['cpodsc'], 'UTF-8', 'ISO-8859-1');
                }

                $headersNew = [];
                foreach ($idConsultation as $id) {
                    $headersNew[$id] = self::heades($id, $headers);
                }

                $result = [
                    'cpovars' => array_unique($cpovar),
                    // 'plugin' => $plugin,
                    'descriptions' => array_unique($cpodsc),
                    'geral' => $dados_geral,
                    'headersNew' => $headersNew
                ];


                $retorno_consulta =  $this->consult_header_plugin($result);

                return $result;
            }
            return false;
        } catch (PDOException $e) {
            return 'Erro ao buscar consultas: ' . $e->getMessage();
        }
    }


    public function Consultation_description($description)
    {
        // $placeholders = implode(',', array_fill(0, count($description), '?'));

        $description = array_map('trim', explode(',', $description));
        $placeholders = implode(',', array_fill(0, count($description), '?'));
        echo "<pre>";
        echo "meu pla";

        print_r($placeholders);

        $cpovar = [];


        $orderByCase = "CASE cpovar ";
        $i = 1;
        $paramIndex = 1;

        foreach ($description as $value) {
            $orderByCase .= "WHEN $" . $paramIndex . " THEN $i ";
            $i++;
            $paramIndex++;
        }
        $orderByCase .= "END";

        echo "<pre>";
        echo "meur";

        print_r($orderByCase);
        $sql = "";
        $sql = "SELECT
        	DISTINCT  cpodsc, 
            $orderByCase as ordem
            FROM
            rdecns inner join 
            rdecnsreg on rdecnsid = rdecnsregrdecns inner join 
            regcpo on rdecnsregreg = regcporeg  inner join
            cpo on cpoid = regcpocpo
            inner join
            reg on regid = rdecnsregreg
            where
            cpovar in ($placeholders)
            order by ordem;";

        try {

            echo "<pre>";
            echo "meu select\n";

            print_r($sql);


            $results = $this->db->prepare($sql);

            $results->execute($description);

            if ($results->rowCount() > 0) {

                while ($row = $results->fetch(PDO::FETCH_ASSOC)) {


                    $cpovar[] = self::limparCampo(mb_convert_encoding($row['cpodsc'], 'UTF-8', 'ISO-8859-1'));

                    echo "<pre>";
                }
            }
        } catch (Exception $e) {

            return $e->getMessage();
        }


        return $cpovar;
    }

    public function Consultation_header_new($dados, $headers)
    {
        $description = array_map('trim', explode(';', $headers));

        $orderBy = "CASE cpovar ";
        foreach ($description as $i => $campo) {
            $campo = addslashes($campo);
            $orderBy .= "WHEN '{$campo}' THEN {$i} ";
        }
        $orderBy .= "END";

        $ids = $dados;
        $idConsultation = array_map('intval', (array)$dados);
        $ids = implode(',', $ids);
        $placeholders = implode(',', array_fill(0, count($idConsultation), '?'));

        $sql = "";
        $sql = "SELECT
        	DISTINCT cpovar, cpodsc, regcod,
             {$orderBy} AS ordem
            FROM
            rdecns inner join 
            rdecnsreg on rdecnsid = rdecnsregrdecns inner join 
            regcpo on rdecnsregreg = regcporeg  inner join
            cpo on cpoid = regcpocpo
            inner join
            reg on regid = rdecnsregreg
            WHERE rdecnsid IN ($placeholders)
            AND cpo.cpovar IN (" . implode(',', array_fill(0, count($description), '?')) . ")
            ORDER BY ordem, regcod";

        try {

            $params = array_merge(
                $idConsultation,
                $description
            );
            $results = $this->db->prepare($sql);
            $results->execute($params);

            $cpovar = [];
            $dados_geral = [];
            $cpodsc = [];
            $plugin = [];



            if ($results->rowCount() > 0) {

                while ($row = $results->fetch(PDO::FETCH_ASSOC)) {

                    $cpovar[] = trim($row['cpovar']);

                    $dados_geral[] = [
                        $row['cpovar'],
                        $row['regcod'],
                        mb_convert_encoding($row['cpodsc'], 'UTF-8', 'ISO-8859-1')
                    ];
                    $plugin[] = $row['regcod'];
                    $cpodsc[] = mb_convert_encoding($row['cpodsc'], 'UTF-8', 'ISO-8859-1');
                }

                $headersNew = [];
                foreach ($idConsultation as $id) {
                    $headersNew[$id] = self::heades($id, $headers);
                }

                $result = [
                    'cpovars' => array_unique($cpovar),
                    // 'plugin' => $plugin,
                    'descriptions' => array_unique($cpodsc),
                    'geral' => $dados_geral,
                    'headersNew' => $headersNew
                ];


                $retorno_consulta =  $this->consult_header_plugin($result);

                return $retorno_consulta;
            }
            return false;
        } catch (PDOException $e) {
            return 'Erro ao buscar consultas: ' . $e->getMessage();
        }
    }
    public function heades($codConsulta, $headers)
    {

        echo "<pre>";
        echo "MEUS COD " . $codConsulta;
        echo "MEUS headers " . $headers;


        $description = array_map('trim', explode(';', $headers));

        $orderBy = "CASE cpovar ";
        foreach ($description as $i => $campo) {
            $campo = addslashes($campo);
            $orderBy .= "WHEN '{$campo}' THEN {$i} ";
        }
        $orderBy .= "END";


        $sql = "";
        $sql = "SELECT
        	DISTINCT cpovar, cpodsc, regcod,
             {$orderBy} AS ordem
            FROM
            rdecns inner join 
            rdecnsreg on rdecnsid = rdecnsregrdecns inner join 
            regcpo on rdecnsregreg = regcporeg  inner join
            cpo on cpoid = regcpocpo
            inner join
            reg on regid = rdecnsregreg
            WHERE rdecnsid = ?
            AND cpo.cpovar IN (" . implode(',', array_fill(0, count($description), '?')) . ")
            ORDER BY ordem, regcod;";

        try {

            $results =  $this->db->prepare($sql);
            $params = array_merge([$codConsulta], $description);
            $results->execute($params);

            $cpovar = [];

            if ($results->rowCount() > 0) {

                while ($row = $results->fetch(PDO::FETCH_ASSOC)) {

                    $cpovar[] = trim($row['cpovar']);
                }

                return [
                    $codConsulta => [
                        'cpovars'       => array_values(array_unique($cpovar)),
                        'tes'       => trim(implode(',', array_unique($cpovar))),

                    ]
                ];
            }
            return false;
        } catch (PDOException $e) {

            return 'Erro ao buscar consultas: ' . $e->getMessage();
        }
    }

    public function consult_header_plugin($dados_consult)
    {
        $plugins_ids = [];
        $plugins_nomes = [];

        foreach ($dados_consult['geral'] as $values) {

            if (isset($values[1])) {
                $plugins_ids[] = $values[1];
            }
            if (isset($values[2])) {
                $plugins_nomes[] = $values[2];
            }
        }

        if (empty($plugins_ids)) {
            return [];
        }

        if (empty($plugins_nomes)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($plugins_ids), '?'));
        $sql = "";
        $sql = "SELECT
                *
            FROM progestor.plugin_campos_input
            WHERE numero_plugin IN ($placeholders)  AND data_cadastro is not null;";
        try {

            $results = $this->db->prepare($sql);

            $results->execute($plugins_ids);

            $all_rows = $results->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {

            return $e->getMessage();
        }



        $dados_pesquisas_limpos = array_map(function ($item) {
            return self::limparCampo($item);
        }, $plugins_nomes);

        $pluginFiltrado = array_filter($all_rows, function ($item) use ($plugins_ids) {
            return in_array($item['numero_plugin'], $plugins_ids);
        });

        $pluginFiltrado = array_values($pluginFiltrado);



        $campoExiste = false;


        $camposObrigatoriosPorPlugin = [];

        foreach ($pluginFiltrado as $plugin) {


            $camposObrigatoriosPorPlugin[$plugin['numero_plugin']] =
                self::camposObrigatoriosDoPlugin($plugin, $dados_pesquisas_limpos);
        }



        $camposObrigatoriosTotais = [];

        foreach ($camposObrigatoriosPorPlugin as $listaCampos) {
            $camposObrigatoriosTotais = array_merge($camposObrigatoriosTotais, $listaCampos);
        }


        $camposObrigatoriosTotais = array_unique($camposObrigatoriosTotais);

        //Agora marca no $dados_consult['descriptions']
        if (!empty($camposObrigatoriosTotais) && !empty($dados_consult['descriptions'])) {

            foreach ($dados_consult['descriptions'] as $key => $descricaoOriginal) {

                // Limpa a descrição para poder comparar
                $descricaoLimpa = (self::limparCampo($descricaoOriginal));

                // Se for obrigatório 
                if (in_array($descricaoLimpa, $camposObrigatoriosTotais)) {
                    $dados_consult['descriptions'][$key] = rtrim($descricaoOriginal) . ';obrigatorio';
                    $dados_consult['campos'][$key] = $dados_consult['cpovars'][$key];
                }
            }
        }

        if (!$campoExiste) {

            return $dados_consult;

            // return $camposObrigatoriosPorPlugin;
        }
    }


    public static function limparCampo($string)
    {
        $string = trim($string);
        $string = preg_replace('/^\d+\s*-\s*/', '', $string); // remove "01 - "
        $string = str_replace(['-', '/'], [' ', ' '], $string); // troca - e /
        $string = preg_replace('/\s+/', ' ', $string); // remove espaços duplicados
        $string = mb_strtoupper($string); // deixa tudo em maiúsculo


        return $string;
    }
    public static function camposObrigatoriosDoPlugin(array $plugin, array $campos)
    {
        $camposEncontrados = [];
        for ($i = 1; $i <= 10; $i++) {
            $param = isset($plugin["parametro{$i}"]) ? trim($plugin["parametro{$i}"]) : '';

            $param =  mb_convert_encoding($param, 'UTF-8', 'ISO-8859-1');
            $paramLimpo = self::limparCampo($param);

            if ($param != '' &&  in_array($paramLimpo, $campos)) {
                $camposEncontrados[] = $paramLimpo;
            }
        }
        return array_unique($camposEncontrados);
    }






    public  function Consultation_header_tdados($idConsultation)
    {

        $sql = " SELECT DISTINCT TRIM(cpovar) as cpovar
    FROM rdecns
    INNER JOIN rdecnsreg ON rdecnsid = rdecnsregrdecns
    INNER JOIN regcpo     ON rdecnsregreg = regcporeg
    INNER JOIN cpo        ON cpoid = regcpocpo
    WHERE rdecnsid = ?
      AND TRIM(cpovar) = 'tcpfcnpj'
    LIMIT 1;";

        $dados = [];
        $dados[] = $idConsultation;

        $result = $this->db->prepare($sql);
        $result->execute($dados);

        $dadosRetorno = [];

        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $dadosRetorno[] = trim($row['cpovar']);
        }

        if (empty($dadosRetorno)) {
            return false;
        }

        return $dadosRetorno;
    }
}
