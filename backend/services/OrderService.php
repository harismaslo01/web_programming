<?php

require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/../dao/OrderDao.php';
require_once __DIR__ . '/../dao/UserDao.php';
require_once __DIR__ . '/../dao/ServiceDao.php';
require_once __DIR__ . '/../dao/DroneDao.php';
require_once __DIR__ . '/WeatherService.php';
require_once __DIR__ . '/../config/Logger.php';

class OrderService extends BaseService
{
    public const STATUSES = ['draft', 'pending', 'confirmed', 'in_progress', 'completed', 'cancelled'];
    // Orders in these statuses are closed and cannot be changed anymore.
    private const FINAL_STATUSES = ['completed', 'cancelled'];

    private UserDao $userDao;
    private ServiceDao $serviceDao;
    private DroneDao $droneDao;
    private WeatherService $weatherService;

    public function __construct()
    {
        parent::__construct(new OrderDao(), 'Order');
        $this->userDao = new UserDao();
        $this->serviceDao = new ServiceDao();
        $this->droneDao = new DroneDao();
        $this->weatherService = new WeatherService();
    }

    public function getByUserId(int $userId): array
    {
        if ($this->userDao->getById($userId) === null) {
            throw new HttpException(404, 'User not found.');
        }

        return $this->dao->getByUserId($userId);
    }

    public function update(array $data, int $id): bool
    {
        $order = $this->findOrFail($id);

        // Rule: completed and cancelled orders are final.
        if (in_array($order['status'], self::FINAL_STATUSES, true)) {
            throw new HttpException(409, sprintf('A %s order cannot be changed.', $order['status']));
        }

        $data = $this->validate($data, false, $id);

        // Rule: when the date or the location changes, the weather is checked again.
        if (array_key_exists('scheduled_date', $data) || array_key_exists('location', $data)) {
            $this->assertFlyableWeather(
                $data['location'] ?? $order['location'],
                $data['scheduled_date'] ?? $order['scheduled_date']
            );
        }

        return $this->dao->update($id, $data);
    }

    public function add(array $data): int
    {
        $data = $this->validate($data, true);
        $this->assertFlyableWeather($data['location'], $data['scheduled_date']);
        return $this->dao->create($data);
    }

    /*
     * Rule: an order cannot be booked when the forecast for the booked time shows rain, snow,
     * fog, a thunderstorm, strong wind or gusts. The forecast comes from Open-Meteo (WeatherService).
     * - Dates further away than weather_check_days are accepted (no reliable forecast yet).
     * - If the weather service is down, the booking is accepted so the application keeps
     *   working; the failure is already in error.log and a warning is added here.
     */
    private function assertFlyableWeather(?string $location, ?string $scheduledDate): void
    {
        if ($location === null || $scheduledDate === null) {
            return;
        }

        try {
            $check = $this->weatherService->checkBookingWeather($location, $scheduledDate);
        } catch (HttpException $exception) {
            if ($exception->getStatusCode() === 503) {
                Logger::error(sprintf('Order weather check skipped for %s at %s: weather service unavailable.', $location, $scheduledDate));
                return;
            }
            throw $exception;
        }

        if (!$check['can_fly']) {
            throw new HttpException(400, sprintf(
                'Drones do not fly in this weather. Forecast for %s on %s: %s Please choose another date or time.',
                $check['location']['name'],
                date('d.m.Y. \\a\\t H:i', strtotime($scheduledDate)),
                implode(' ', $check['reasons'])
            ));
        }
    }

    protected function validate(array $data, bool $isCreate, ?int $id = null): array
    {
        $this->assertAllowedFields($data, [
            'user_id', 'service_id', 'drone_id', 'order_number', 'title', 'location',
            'description', 'scheduled_date', 'status', 'total_price', 'notes'
        ]);

        if ($isCreate) {
            // A booking needs a place and a time, because the weather is checked for both.
            $this->assertRequired($data, ['user_id', 'service_id', 'title', 'location', 'scheduled_date']);
        }

        $this->cleanInt($data, 'user_id', 1);
        $this->cleanInt($data, 'service_id', 1);
        $this->cleanInt($data, 'drone_id', 1, null, true);
        $this->cleanString($data, 'order_number', 50);
        $this->cleanString($data, 'title', 150);
        $this->cleanString($data, 'location', 150);
        $this->cleanString($data, 'description', 5000, true);
        $this->cleanString($data, 'notes', 5000, true);
        $this->cleanDate($data, 'scheduled_date', true);
        $this->cleanEnum($data, 'status', self::STATUSES);
        $this->cleanDecimal($data, 'total_price', 0, 99999999.99);

        // Rule: the customer must exist and be active.
        if (isset($data['user_id'])) {
            $user = $this->userDao->getById($data['user_id']);
            if ($user === null) {
                throw new HttpException(400, 'The selected user does not exist.');
            }
            if (!(bool) $user['is_active']) {
                throw new HttpException(400, 'The selected user account is not active.');
            }
        }

        // Rule: the service must exist; new orders can only use active services.
        $service = null;
        if (isset($data['service_id'])) {
            $service = $this->serviceDao->getById($data['service_id']);
            if ($service === null) {
                throw new HttpException(400, 'The selected service does not exist.');
            }
            if ($isCreate && !(bool) $service['active']) {
                throw new HttpException(400, 'The selected service is not active.');
            }
        }

        // Rule: a drone in maintenance or retired cannot be assigned to an order.
        if (isset($data['drone_id'])) {
            $drone = $this->droneDao->getById($data['drone_id']);
            if ($drone === null) {
                throw new HttpException(400, 'The selected drone does not exist.');
            }
            if (in_array($drone['status'], ['maintenance', 'retired'], true)) {
                throw new HttpException(400, sprintf('The selected drone is in %s status and cannot be assigned.', $drone['status']));
            }
        }

        // Rule: a new order cannot be scheduled in the past.
        if ($isCreate && isset($data['scheduled_date']) && $data['scheduled_date'] < date('Y-m-d H:i:s')) {
            throw new HttpException(400, 'The scheduled date cannot be in the past.');
        }

        // Rule: order numbers are unique.
        if (isset($data['order_number'])) {
            $existing = $this->dao->getOneBy('order_number', $data['order_number']);
            if ($existing !== null && (int) $existing['id'] !== $id) {
                throw new HttpException(409, 'An order with this order number already exists.');
            }
        }

        if ($isCreate) {
            // Rule: the order number is generated when the client does not send one (DP-2026-0003).
            $data['order_number'] ??= $this->generateOrderNumber();
            // Rule: the price defaults to the price of the selected service.
            $data['total_price'] ??= (float) $service['price'];
            $data['status'] ??= 'pending';
        }

        return $data;
    }

    private function generateOrderNumber(): string
    {
        $year = date('Y');
        // Start after the current number of orders, e.g. 2 orders -> DP-2026-0003.
        $sequence = count($this->dao->getAll());

        // Loop until a free number is found (some numbers may already be taken manually).
        do {
            $sequence++;
            $orderNumber = sprintf('DP-%s-%04d', $year, $sequence);
        } while ($this->dao->getOneBy('order_number', $orderNumber) !== null);

        return $orderNumber;
    }
}
