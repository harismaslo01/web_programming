<?php

require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/../dao/RoleDao.php';
require_once __DIR__ . '/../dao/UserDao.php';

class RoleService extends BaseService
{
    private UserDao $userDao;

    public function __construct()
    {
        parent::__construct(new RoleDao(), 'Role');
        $this->userDao = new UserDao();
    }

    protected function validate(array $data, bool $isCreate, ?int $id = null): array
    {
        $this->assertAllowedFields($data, ['name', 'description']);

        if ($isCreate) {
            $this->assertRequired($data, ['name']);
        }

        $this->cleanString($data, 'name', 50);
        $this->cleanString($data, 'description', 255, true);

        // Rule: role names are unique (the column is UNIQUE in MySQL as well).
        if (isset($data['name'])) {
            $existing = $this->dao->getOneBy('name', $data['name']);
            if ($existing !== null && (int) $existing['id'] !== $id) {
                throw new HttpException(409, 'A role with this name already exists.');
            }
        }

        return $data;
    }

    // Rule: a role that is still assigned to users cannot be deleted.
    protected function beforeDelete(array $role): void
    {
        if ($this->userDao->countBy('role_id', $role['id']) > 0) {
            throw new HttpException(409, 'This role is assigned to users and cannot be deleted.');
        }
    }
}
