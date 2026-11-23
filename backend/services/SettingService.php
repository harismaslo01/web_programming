<?php

require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/../dao/SettingDao.php';

class SettingService extends BaseService
{
    // Rule: these settings are used by the application and cannot be deleted.
    private const PROTECTED_KEYS = ['site_name', 'contact_email', 'default_currency'];

    public function __construct()
    {
        parent::__construct(new SettingDao(), 'Setting');
    }

    public function getByKey(string $key): array
    {
        $setting = $this->dao->getByKey($key);
        if ($setting === null) {
            throw new HttpException(404, 'Setting not found.');
        }

        return $setting;
    }

    protected function validate(array $data, bool $isCreate, ?int $id = null): array
    {
        $this->assertAllowedFields($data, ['key_name', 'value_text', 'description']);

        if ($isCreate) {
            $this->assertRequired($data, ['key_name']);
        }

        $this->cleanString($data, 'key_name', 100);
        $this->cleanString($data, 'value_text', 5000, true);
        $this->cleanString($data, 'description', 255, true);

        if (isset($data['key_name'])) {
            // Rule: keys are lowercase snake_case, e.g. default_currency.
            if (!preg_match('/^[a-z][a-z0-9_]*$/', $data['key_name'])) {
                throw new HttpException(400, 'The key_name must be lowercase snake_case (e.g. default_currency).');
            }

            $existing = $this->dao->getByKey($data['key_name']);
            if ($existing !== null && (int) $existing['id'] !== $id) {
                throw new HttpException(409, 'A setting with this key already exists.');
            }

            // Rule: a protected key cannot be renamed.
            if (!$isCreate) {
                $current = $this->findOrFail($id);
                if (in_array($current['key_name'], self::PROTECTED_KEYS, true) && $current['key_name'] !== $data['key_name']) {
                    throw new HttpException(409, 'This setting is required by the application and cannot be renamed.');
                }
            }
        }

        // Rule: contact_email must hold a valid email address.
        $key = $data['key_name'] ?? ($isCreate ? null : $this->findOrFail($id)['key_name']);
        if ($key === 'contact_email' && isset($data['value_text'])
            && filter_var($data['value_text'], FILTER_VALIDATE_EMAIL) === false) {
            throw new HttpException(400, 'contact_email must be a valid email address.');
        }

        return $data;
    }

    protected function beforeDelete(array $setting): void
    {
        if (in_array($setting['key_name'], self::PROTECTED_KEYS, true)) {
            throw new HttpException(409, 'This setting is required by the application and cannot be deleted.');
        }
    }
}
