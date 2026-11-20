<?php

require_once __DIR__ . '/../dao/BaseDao.php';
require_once __DIR__ . '/../middleware/HttpException.php';

/*
 * Shared business logic for every entity service.
 *
 * Request flow: route -> service -> DAO -> database.
 * Services are the ONLY layer that talks to DAOs. Routes never call a DAO directly.
 *
 * Every child service must implement validate(), which:
 *   - rejects unknown fields,
 *   - checks required fields on create,
 *   - checks types, lengths, formats and entity-specific rules,
 *   - returns cleaned data that is safe to give to the DAO.
 * Invalid input is reported with HttpException(400, ...), a missing record with 404,
 * and a conflict with existing data (duplicate, record still in use) with 409.
 */
abstract class BaseService
{
    protected BaseDao $dao;
    protected string $entityName;

    public function __construct(BaseDao $dao, string $entityName)
    {
        $this->dao = $dao;
        $this->entityName = $entityName;
    }

    abstract protected function validate(array $data, bool $isCreate, ?int $id = null): array;

    public function getAll(): array
    {
        return array_map([$this, 'present'], $this->dao->getAll());
    }

    public function getById(int $id): array
    {
        return $this->present($this->findOrFail($id));
    }

    public function add(array $data): int
    {
        return $this->dao->create($this->validate($data, true));
    }

    public function update(array $data, int $id): bool
    {
        $this->findOrFail($id);
        return $this->dao->update($id, $this->validate($data, false, $id));
    }

    public function delete(int $id): bool
    {
        $entity = $this->findOrFail($id);
        $this->beforeDelete($entity);
        return $this->dao->delete($id);
    }

    /* Hook: child services block deletion here when the record is still in use. */
    protected function beforeDelete(array $entity): void
    {
    }

    /* Hook: child services hide or reshape fields before they are returned (e.g. password_hash). */
    protected function present(array $entity): array
    {
        return $entity;
    }

    protected function findOrFail(int $id): array
    {
        $entity = $this->dao->getById($id);
        if ($entity === null) {
            throw new HttpException(404, sprintf('%s not found.', $this->entityName));
        }

        return $entity;
    }

    /* ---------- Validation helpers used by child services ---------- */

    protected function assertAllowedFields(array $data, array $allowedFields): void
    {
        if ($data === []) {
            throw new HttpException(400, sprintf('%s data cannot be empty.', $this->entityName));
        }

        $unknownFields = array_diff(array_keys($data), $allowedFields);
        if ($unknownFields !== []) {
            throw new HttpException(400, 'Unknown field(s): ' . implode(', ', $unknownFields) . '.');
        }
    }

    protected function assertRequired(array $data, array $requiredFields): void
    {
        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $data) || $data[$field] === null
                || (is_string($data[$field]) && trim($data[$field]) === '')) {
                throw new HttpException(400, sprintf('The %s field is required.', $field));
            }
        }
    }

    /* Trims a text field and checks its maximum length. Empty optional text becomes null. */
    protected function cleanString(array &$data, string $field, int $maxLength, bool $nullable = false): void
    {
        if (!array_key_exists($field, $data)) {
            return;
        }

        if ($data[$field] === null && $nullable) {
            return;
        }

        if (!is_string($data[$field])) {
            throw new HttpException(400, sprintf('The %s field must be text.', $field));
        }

        $value = trim($data[$field]);
        if ($value === '') {
            if (!$nullable) {
                throw new HttpException(400, sprintf('The %s field cannot be empty.', $field));
            }
            $data[$field] = null;
            return;
        }

        if (mb_strlen($value) > $maxLength) {
            throw new HttpException(
                400,
                sprintf('The %s field can have at most %d characters.', $field, $maxLength)
            );
        }

        $data[$field] = $value;
    }

    protected function cleanInt(array &$data, string $field, ?int $min = null, ?int $max = null, bool $nullable = false): void
    {
        if (!array_key_exists($field, $data) || ($data[$field] === null && $nullable)) {
            return;
        }

        $value = filter_var($data[$field], FILTER_VALIDATE_INT);
        if ($value === false || ($min !== null && $value < $min) || ($max !== null && $value > $max)) {
            throw new HttpException(400, sprintf('The %s field must be a whole number%s.', $field, $this->rangeText($min, $max)));
        }

        $data[$field] = $value;
    }

    protected function cleanDecimal(array &$data, string $field, float $min = 0, ?float $max = null): void
    {
        if (!array_key_exists($field, $data)) {
            return;
        }

        $value = filter_var($data[$field], FILTER_VALIDATE_FLOAT);
        if ($value === false || $value < $min || ($max !== null && $value > $max)) {
            throw new HttpException(400, sprintf('The %s field must be a number%s.', $field, $this->rangeText($min, $max)));
        }

        // Stored as DECIMAL(10,2), so keep two decimals.
        $data[$field] = round($value, 2);
    }

    /* Accepts true/false, 1/0 or "true"/"false" and stores 1/0, because MySQL BOOLEAN is TINYINT(1). */
    protected function cleanBool(array &$data, string $field): void
    {
        if (!array_key_exists($field, $data)) {
            return;
        }

        $value = filter_var($data[$field], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($value === null) {
            throw new HttpException(400, sprintf('The %s field must be true or false.', $field));
        }

        $data[$field] = $value ? 1 : 0;
    }

    protected function cleanEnum(array &$data, string $field, array $allowedValues): void
    {
        if (array_key_exists($field, $data) && !in_array($data[$field], $allowedValues, true)) {
            throw new HttpException(
                400,
                sprintf('The %s field must be one of: %s.', $field, implode(', ', $allowedValues))
            );
        }
    }

    /* Validates a date (Y-m-d) or date-time (Y-m-d H:i:s) value. */
    protected function cleanDate(array &$data, string $field, bool $withTime = false, bool $nullable = true): void
    {
        if (!array_key_exists($field, $data) || ($data[$field] === null && $nullable)) {
            return;
        }

        $formats = $withTime ? ['Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d\TH:i:s', 'Y-m-d\TH:i'] : ['Y-m-d'];
        foreach ($formats as $format) {
            $date = is_string($data[$field]) ? DateTime::createFromFormat('!' . $format, $data[$field]) : false;
            if ($date !== false && $date->format($format) === $data[$field]) {
                $data[$field] = $date->format($withTime ? 'Y-m-d H:i:s' : 'Y-m-d');
                return;
            }
        }

        throw new HttpException(
            400,
            sprintf('The %s field must be a valid %s.', $field, $withTime ? 'date and time (YYYY-MM-DD HH:MM:SS)' : 'date (YYYY-MM-DD)')
        );
    }

    private function rangeText(int|float|null $min, int|float|null $max): string
    {
        if ($min !== null && $max !== null) {
            return sprintf(' between %s and %s', $min, $max);
        }
        if ($min !== null) {
            return sprintf(' greater than or equal to %s', $min);
        }
        if ($max !== null) {
            return sprintf(' less than or equal to %s', $max);
        }
        return '';
    }
}
