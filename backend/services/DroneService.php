<?php

require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/../dao/DroneDao.php';
require_once __DIR__ . '/../dao/OrderDao.php';

class DroneService extends BaseService
{
    public const STATUSES = ['available', 'reserved', 'maintenance', 'retired'];

    private OrderDao $orderDao;

    public function __construct()
    {
        parent::__construct(new DroneDao(), 'Drone');
        $this->orderDao = new OrderDao();
    }

    public function search(string $term): array
    {
        return $this->dao->search($term);
    }

    public function filter(array $filters): array
    {
        $allowedFilters = ['status', 'category', 'brand'];
        $filters = array_intersect_key($filters, array_flip($allowedFilters));

        foreach ($filters as $filter => $value) {
            if (!is_string($value)) {
                throw new HttpException(
                    400,
                    sprintf('The %s filter must be a string.', $filter)
                );
            }

            if ($filter === 'status' && !in_array($value, self::STATUSES, true)) {
                throw new HttpException(400, 'Invalid drone status filter.');
            }
        }

        return $this->dao->filter($filters);
    }

    protected function validate(array $data, bool $isCreate, ?int $id = null): array
    {
        $this->assertAllowedFields($data, [
            'brand',
            'model',
            'category',
            'serial_number',
            'status',
            'last_service_date',
            'battery_level',
            'purchase_date',
            'image_url'
        ]);

        if ($isCreate) {
            $this->assertRequired($data, ['brand', 'model', 'serial_number']);
        }

        $this->cleanString($data, 'brand', 100);
        $this->cleanString($data, 'model', 100);
        $this->cleanString($data, 'category', 100, true);
        $this->cleanString($data, 'serial_number', 100);
        $this->cleanString($data, 'image_url', 500, true);
        $this->cleanEnum($data, 'status', self::STATUSES);
        $this->cleanInt($data, 'battery_level', 0, 100, true);
        $this->cleanDate($data, 'last_service_date');
        $this->cleanDate($data, 'purchase_date');

        // Rule: serial numbers are unique.
        if (isset($data['serial_number'])) {
            $existing = $this->dao->getOneBy('serial_number', $data['serial_number']);
            if ($existing !== null && (int) $existing['id'] !== $id) {
                throw new HttpException(409, 'A drone with this serial number already exists.');
            }
        }

        // Rule: a drone cannot be serviced before it was purchased.
        $purchaseDate = $data['purchase_date'] ?? null;
        $serviceDate = $data['last_service_date'] ?? null;
        if ($purchaseDate !== null && $serviceDate !== null && $serviceDate < $purchaseDate) {
            throw new HttpException(400, 'The last service date cannot be before the purchase date.');
        }

        return $data;
    }

    // Rule: a drone assigned to orders cannot be deleted (set status = retired instead).
    protected function beforeDelete(array $drone): void
    {
        if ($this->orderDao->countBy('drone_id', $drone['id']) > 0) {
            throw new HttpException(409, 'This drone is assigned to orders and cannot be deleted. Set its status to retired instead.');
        }
    }
}
