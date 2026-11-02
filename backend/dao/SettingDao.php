<?php

require_once __DIR__ . '/BaseDao.php';

class SettingDao extends BaseDao
{
    public function __construct()
    {
        parent::__construct('settings');
    }

    public function getByKey(string $key): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT * FROM settings WHERE key_name = :key_name LIMIT 1'
        );
        $statement->execute(['key_name' => $key]);
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }
}
