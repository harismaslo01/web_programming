<?php

require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/../dao/MediaDao.php';
require_once __DIR__ . '/../dao/OrderDao.php';

/*
 * Media are files (photos, videos, reports) delivered for an order.
 * Milestone 3 stores file metadata and URLs; the actual upload is not part of this milestone.
 */
class MediaService extends BaseService
{
    // Rule: only these file types can be attached to an order.
    public const ALLOWED_TYPES = [
        'image/jpeg', 'image/png', 'image/webp', 'image/tiff',
        'video/mp4', 'video/quicktime', 'application/pdf', 'application/zip'
    ];

    private OrderDao $orderDao;

    public function __construct()
    {
        parent::__construct(new MediaDao(), 'Media');
        $this->orderDao = new OrderDao();
    }

    public function getByOrderId(int $orderId): array
    {
        if ($this->orderDao->getById($orderId) === null) {
            throw new HttpException(404, 'Order not found.');
        }

        return $this->dao->getByOrderId($orderId);
    }

    protected function validate(array $data, bool $isCreate, ?int $id = null): array
    {
        $this->assertAllowedFields($data, ['order_id', 'file_name', 'file_type', 'file_url']);

        if ($isCreate) {
            $this->assertRequired($data, ['order_id', 'file_name', 'file_url']);
        }

        $this->cleanInt($data, 'order_id', 1);
        $this->cleanString($data, 'file_name', 255);
        $this->cleanString($data, 'file_type', 100, true);
        $this->cleanString($data, 'file_url', 500);

        // Rule: media must belong to an existing order.
        if (isset($data['order_id']) && $this->orderDao->getById($data['order_id']) === null) {
            throw new HttpException(400, 'The selected order does not exist.');
        }

        // Rule: file names cannot contain path characters.
        if (isset($data['file_name']) && preg_match('#[\\\\/]|\.\.#', $data['file_name'])) {
            throw new HttpException(400, 'The file name cannot contain slashes or "..".');
        }

        if (isset($data['file_type']) && !in_array($data['file_type'], self::ALLOWED_TYPES, true)) {
            throw new HttpException(400, 'Unsupported file type. Allowed: ' . implode(', ', self::ALLOWED_TYPES) . '.');
        }

        // Rule: the URL is either a site-relative path (/uploads/...) or an http(s) link.
        if (isset($data['file_url']) && !str_starts_with($data['file_url'], '/')
            && !preg_match('#^https?://#i', $data['file_url'])) {
            throw new HttpException(400, 'The file URL must start with / or http(s)://.');
        }

        return $data;
    }
}
