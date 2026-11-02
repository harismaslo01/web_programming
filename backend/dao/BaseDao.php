<?php

require_once __DIR__ . '/../config/Database.php';

abstract class BaseDao
{
    protected PDO $connection;
    protected string $table;

    public function __construct(string $table)
    {
        $this->connection = Database::getConnection();
        $this->table = $table;
    }

    public function getAll(): array
    {
        $statement = $this->connection->query("SELECT * FROM {$this->table}");
        return $statement->fetchAll();
    }

    public function getById(int $id): ?array
    {
        $statement = $this->connection->prepare(
            "SELECT * FROM {$this->table} WHERE id = :id LIMIT 1"
        );
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    public function create(array $data): int
    {
        if (empty($data)) {
            throw new InvalidArgumentException('Create data cannot be empty.');
        }

        $columns = array_keys($data);
        $this->validateColumns($columns);
        $columnList = implode(', ', $columns);
        $placeholders = implode(', ', array_map(fn ($column) => ':' . $column, $columns));

        $statement = $this->connection->prepare(
            "INSERT INTO {$this->table} ({$columnList}) VALUES ({$placeholders})"
        );
        $statement->execute($this->namedParameters($data));

        return (int) $this->connection->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        if (empty($data)) {
            throw new InvalidArgumentException('Update data cannot be empty.');
        }

        $assignments = [];
        foreach (array_keys($data) as $column) {
            $assignments[] = "{$column} = :{$column}";
        }
        $this->validateColumns(array_keys($data));

        $parameters = $this->namedParameters($data);
        $parameters[':id'] = $id;

        $statement = $this->connection->prepare(
            "UPDATE {$this->table} SET " . implode(', ', $assignments) . " WHERE id = :id"
        );

        return $statement->execute($parameters);
    }

    public function delete(int $id): bool
    {
        $statement = $this->connection->prepare(
            "DELETE FROM {$this->table} WHERE id = :id"
        );
        return $statement->execute(['id' => $id]);
    }

    protected function namedParameters(array $data): array
    {
        $parameters = [];
        foreach ($data as $key => $value) {
            $parameters[':' . $key] = $value;
        }
        return $parameters;
    }

    private function validateColumns(array $columns): void
    {
        foreach ($columns as $column) {
            if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $column)) {
                throw new InvalidArgumentException('Invalid database column name.');
            }
        }
    }
}
