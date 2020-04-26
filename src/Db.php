<?php

namespace splitbrain\unb2flarum;

class Db {
    protected $pdo;
    protected $config;

    public function __construct($config)
    {
        $this->config = $config;

        $dsn = 'mysql:host='.$config['db']['host'];
        $this->pdo = new \PDO($dsn, $config['db']['user'], $config['db']['password']);
        
        // throw exceptions on failure
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        // fetch numbered result columns
        $this->pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_NUM);
        // everthing is run in a transaction
        $this->pdo->beginTransaction();
    }

    /**
     * @return \PDO
     */
    public function getPDO() {
        return $this->pdo;
    }

    public function getUnbPrefix() {
        return $this->config['unb'];
    }

    public function getFlarumPrefix() {
        return $this->config['flarum'];
    }


    public function execute($sql) {
        $this->pdo->exec($sql);
    }
}
