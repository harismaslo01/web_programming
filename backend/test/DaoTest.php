<?php

require_once __DIR__ . '/../dao/RoleDao.php';
require_once __DIR__ . '/../dao/UserDao.php';
require_once __DIR__ . '/../dao/ServiceDao.php';
require_once __DIR__ . '/../dao/DroneDao.php';
require_once __DIR__ . '/../dao/OrderDao.php';
require_once __DIR__ . '/../dao/MediaDao.php';
require_once __DIR__ . '/../dao/SettingDao.php';

function assertTest(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
    echo "[PASS] {$message}" . PHP_EOL;
}

try {
    $roleDao = new RoleDao();
    assertTest(count($roleDao->getAll()) >= 2, 'roles contain sample data');

    $userDao = new UserDao();
    assertTest(count($userDao->getAll()) >= 2, 'users contain sample data');

    $serviceDao = new ServiceDao();
    $activeServices = $serviceDao->getActiveServices();
    assertTest(count($activeServices) >= 1, 'active services can be read');

    $droneDao = new DroneDao();
    assertTest(count($droneDao->getAvailableDrones()) >= 1, 'available drones can be read');

    $orderDao = new OrderDao();
    assertTest(is_array($orderDao->getAll()), 'orders support list reads');

    $mediaDao = new MediaDao();
    assertTest(is_array($mediaDao->getAll()), 'media support list reads');

    $settingDao = new SettingDao();
    assertTest($settingDao->getByKey('site_name') !== null, 'settings support key lookup');

    $testKey = 'dao_test_' . time();
    $settingId = $settingDao->create([
        'key_name' => $testKey,
        'value_text' => 'created',
        'description' => 'Temporary DAO verification record'
    ]);
    assertTest($settingId > 0, 'a setting can be created');

    assertTest(
        $settingDao->update($settingId, ['value_text' => 'updated']),
        'a setting can be updated'
    );

    assertTest(
        $settingDao->getByKey($testKey)['value_text'] === 'updated',
        'an updated setting can be read'
    );

    assertTest($settingDao->delete($settingId), 'a setting can be deleted');
    assertTest($settingDao->getByKey($testKey) === null, 'a deleted setting is no longer readable');

    echo "All DAO checks passed." . PHP_EOL;
} catch (Throwable $error) {
    fwrite(STDERR, "[FAIL] " . $error->getMessage() . PHP_EOL);
    exit(1);
}
