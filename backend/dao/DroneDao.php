<?php

require_once __DIR__ . '/BaseDao.php';

class DroneDao extends BaseDao
{
    public function __construct()
    {
        parent::__construct('drones');
    }

    public function getAvailableDrones(): array
    {
        $statement = $this->connection->prepare(
            'SELECT * FROM drones WHERE status = :status ORDER BY brand, model'
        );
        $statement->execute(['status' => 'available']);
        return $statement->fetchAll();
    }
}
