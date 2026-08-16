<?php
require_once __DIR__ . '/../core/MongoConect.php';

class LogModel
{
    protected $mongo;

    public function __construct()
    {
        $this->mongo = MongoConect::getInstance()->getDatabase();
    }

    public function salvarLog($dados)
    {
        $this->mongo->logs->insertOne($dados);
    }

    public function listarLogs()
    {
        return $this->mongo->logs->find()->toArray();
    }
}
