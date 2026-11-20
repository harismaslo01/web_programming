<?php

require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/../dao/UserDao.php';
require_once __DIR__ . '/../dao/RoleDao.php';
require_once __DIR__ . '/../dao/OrderDao.php';

class UserService extends BaseService
{
    private RoleDao $roleDao;
    private OrderDao $orderDao;

    public function __construct()
    {
        parent::__construct(new UserDao(), 'User');
        $this->roleDao = new RoleDao();
        $this->orderDao = new OrderDao();
    }

    // The password hash is never sent to the client.
    protected function present(array $user): array
    {
        unset($user['password_hash']);
        $user['is_active'] = (bool) $user['is_active'];
        return $user;
    }

    protected function validate(array $data, bool $isCreate, ?int $id = null): array
    {
        // The client sends a plain "password"; only its hash is stored in password_hash.
        $this->assertAllowedFields($data, ['first_name', 'last_name', 'email', 'password', 'phone', 'role_id', 'is_active']);

        if ($isCreate) {
            $this->assertRequired($data, ['first_name', 'last_name', 'email', 'password']);
        }

        $this->cleanString($data, 'first_name', 100);
        $this->cleanString($data, 'last_name', 100);
        $this->cleanString($data, 'phone', 50, true);
        $this->cleanBool($data, 'is_active');
        $this->cleanInt($data, 'role_id', 1);

        if (array_key_exists('email', $data)) {
            $this->cleanString($data, 'email', 255);
            $data['email'] = strtolower($data['email']);
            if (filter_var($data['email'], FILTER_VALIDATE_EMAIL) === false) {
                throw new HttpException(400, 'The email address is not valid.');
            }

            // Rule: one account per email address.
            $existing = $this->dao->getOneBy('email', $data['email']);
            if ($existing !== null && (int) $existing['id'] !== $id) {
                throw new HttpException(409, 'A user with this email already exists.');
            }
        }

        if (isset($data['phone']) && !preg_match('/^\+?[0-9 \-\/]{6,20}$/', $data['phone'])) {
            throw new HttpException(400, 'The phone number is not valid.');
        }

        // Rule: the role must exist.
        if (isset($data['role_id']) && $this->roleDao->getById($data['role_id']) === null) {
            throw new HttpException(400, 'The selected role does not exist.');
        }

        // Rule: passwords have at least 8 characters and are stored only as a hash.
        if (array_key_exists('password', $data)) {
            if (!is_string($data['password']) || strlen($data['password']) < 8) {
                throw new HttpException(400, 'The password must have at least 8 characters.');
            }
            $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
            unset($data['password']);
        }

        return $data;
    }

    // Rule: users with orders cannot be deleted (deactivate them with is_active = false instead).
    protected function beforeDelete(array $user): void
    {
        if ($this->orderDao->countBy('user_id', $user['id']) > 0) {
            throw new HttpException(409, 'This user has orders and cannot be deleted. Deactivate the account instead.');
        }
    }
}
