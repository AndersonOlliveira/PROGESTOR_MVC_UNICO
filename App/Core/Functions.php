<?php

namespace Core;



class Functions
{

    /**
     * Valida um CPF e retorna um array com o resultado.
     * @param string $cpf
     * @return array ['valid' => bool, 'reason' => string]
     */
    public function tratarCpf($cpf)
    {
        $cpf = preg_replace('/[^0-9]/is', '', $cpf);
        // Verifica se foi informado todos os digitos corretamente
        if (strlen($cpf) !== 11) {

            return ['valid' => false, 'reason' => 'Número fora do padrão de CPF OU CNPJ'];
            // return false;
        }
        // Verifica se foi informada uma sequência de digitos repetidos. Ex: 111.111.111-11
        if (preg_match('/(\d)\1{10}/', $cpf)) {
            return ['valid' => false, 'reason' => 'sequência de digitos repetidos'];
            // return false;
        }

        // Faz o calculo para validar o CPF
        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                return ['valid' => false, 'reason' => 'dígito inválido', 'possicao' => $t];
            }
        }

        return ['valid' => true, 'reason' => 'ok'];
    }

    public function limparDados($value)
    {
        $cnpj_cpf = preg_replace("/\D/", '', $value);

        if (strlen($cnpj_cpf) === 11) {
            return $cnpj_cpf;
        }
    }
    function logInfo($mensagem)
    {
        echo "[" . date('H:i:s') . "] $mensagem\n";
    }
     private static function latin1ize($data)
{
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = self::latin1ize($value);
        }
    } elseif (is_string($data)) {
        return trim(
            mb_convert_encoding(
                $data,
                'ISO-8859-1',
                'UTF-8'
            )
        );
    }

    return $data;
}

    public static function convertToLatin1($data)
    {
        return self::latin1ize($data);
    }
}
