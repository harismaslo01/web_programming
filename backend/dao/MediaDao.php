<?php

require_once __DIR__ . '/BaseDao.php';

class MediaDao extends BaseDao
{
    public function __construct()
    {
        parent::__construct('media');
    }

    public function getByOrderId(int $orderId): array
    {
        $statement = $this->connection->prepare(
            'SELECT * FROM media WHERE order_id = :order_id ORDER BY uploaded_at DESC'
        );
        $statement->execute(['order_id' => $orderId]);
        return $statement->fetchAll();
    }
}
