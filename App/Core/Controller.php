<?php

namespace Core;

class Controller
{
    protected $arquivoLog;


    /**
     * Define os cabeçalhos padrão para respostas JSON e CORS.
     */
    protected function setApiHeaders(): void
    {
        header("Access-Control-Allow-Origin: http://localhost:5173");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");
        header("Content-Type: application/json; charset=utf-8");

        // Resolve o problema de requisições de teste (Pre-flight OPTIONS) do Frontend
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit();
        }
    }

    public static function handleCorsPreflight(): void
    {
        header("Access-Control-Allow-Origin: http://localhost:5173");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit();
        }
    }

    /**
     * Helper opcional para enviar respostas JSON estruturadas
     */
    protected function json(array $data, int $statusCode = 200): void
    {
        $this->setApiHeaders();
        http_response_code($statusCode);
        echo json_encode($data);
        exit();
    }
}
