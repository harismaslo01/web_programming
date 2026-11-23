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

    public function search(string $term): array
    {
        $statement = $this->connection->prepare(
            'SELECT * FROM drones
             WHERE brand LIKE :brand
                OR model LIKE :model
                OR category LIKE :category
                OR serial_number LIKE :serial_number
             ORDER BY brand, model'
        );
        $searchTerm = '%' . $term . '%';
        $statement->execute([
            'brand' => $searchTerm,
            'model' => $searchTerm,
            'category' => $searchTerm,
            'serial_number' => $searchTerm
        ]);

        return $statement->fetchAll();
    }

    public function filter(array $filters): array
    {
        $filterColumns = [
            'status' => 'status',
            'category' => 'category',
            'brand' => 'brand'
        ];
        $conditions = [];
        $parameters = [];

        foreach ($filters as $filter => $value) {
            if (!isset($filterColumns[$filter])) {
                continue;
            }

            $conditions[] = $filterColumns[$filter] . ' = :' . $filter;
            $parameters[$filter] = $value;
        }

        $sql = 'SELECT * FROM drones';
        if ($conditions !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }
        $sql .= ' ORDER BY brand, model';

        $statement = $this->connection->prepare($sql);
        $statement->execute($parameters);

        return $statement->fetchAll();
    }
}
