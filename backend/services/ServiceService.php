<?php

require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/../dao/ServiceDao.php';
require_once __DIR__ . '/../dao/OrderDao.php';

/*
 * Business logic for the "services" table (aerial services offered to customers,
 * e.g. Drone Photography). The class name follows the <Entity>Service convention.
 */
class ServiceService extends BaseService
{
    private OrderDao $orderDao;

    public function __construct()
    {
        parent::__construct(new ServiceDao(), 'Service');
        $this->orderDao = new OrderDao();
    }

    public function getActive(): array
    {
        return array_map([$this, 'present'], $this->dao->getActiveServices());
    }

    protected function present(array $service): array
    {
        $service['active'] = (bool) $service['active'];
        return $service;
    }

    protected function validate(array $data, bool $isCreate, ?int $id = null): array
    {
        $this->assertAllowedFields($data, ['name', 'description', 'price', 'duration_days', 'image_url', 'active']);

        if ($isCreate) {
            $this->assertRequired($data, ['name', 'price']);
        }

        $this->cleanString($data, 'name', 150);
        $this->cleanString($data, 'description', 5000, true);
        $this->cleanString($data, 'image_url', 500, true);
        // Rule: price cannot be negative and must fit DECIMAL(10,2).
        $this->cleanDecimal($data, 'price', 0, 99999999.99);
        // Rule: a service lasts between 1 and 365 days.
        $this->cleanInt($data, 'duration_days', 1, 365);
        $this->cleanBool($data, 'active');

        return $data;
    }

    // Rule: a service used by orders cannot be deleted (set active = false instead).
    protected function beforeDelete(array $service): void
    {
        if ($this->orderDao->countBy('service_id', $service['id']) > 0) {
            throw new HttpException(409, 'This service is used by orders and cannot be deleted. Deactivate it instead.');
        }
    }
}
