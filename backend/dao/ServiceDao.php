<?php

require_once __DIR__ . '/BaseDao.php';

class ServiceDao extends BaseDao
{
    public function __construct()
    {
        parent::__construct('services');
    }

    public function getActiveServices(): array
    {
        $statement = $this->connection->prepare(
            'SELECT * FROM services WHERE active = 1 ORDER BY name'
        );
        $statement->execute();
        return $statement->fetchAll();
    }
}
