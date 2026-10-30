CREATE DATABASE IF NOT EXISTS dronepilot_services
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE dronepilot_services;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS media;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS drones;
DROP TABLE IF EXISTS services;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS roles;
DROP TABLE IF EXISTS settings;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255) NULL
) ENGINE=InnoDB;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(50) NULL,
    role_id INT UNSIGNED NOT NULL DEFAULT 2,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id)
) ENGINE=InnoDB;

CREATE TABLE services (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    price DECIMAL(10,2) NOT NULL,
    duration_days INT UNSIGNED NOT NULL DEFAULT 1,
    image_url VARCHAR(500) NULL,
    active BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

CREATE TABLE drones (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    brand VARCHAR(100) NOT NULL,
    model VARCHAR(100) NOT NULL,
    category VARCHAR(100) NULL,
    serial_number VARCHAR(100) NOT NULL UNIQUE,
    status ENUM('available', 'reserved', 'maintenance', 'retired') NOT NULL DEFAULT 'available',
    last_service_date DATE NULL,
    battery_level TINYINT UNSIGNED NULL,
    purchase_date DATE NULL,
    image_url VARCHAR(500) NULL,
    CONSTRAINT chk_drones_battery CHECK (battery_level IS NULL OR battery_level <= 100)
) ENGINE=InnoDB;

CREATE TABLE orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    service_id INT UNSIGNED NOT NULL,
    drone_id INT UNSIGNED NULL,
    order_number VARCHAR(50) NOT NULL UNIQUE,
    title VARCHAR(150) NOT NULL,
    description TEXT NULL,
    scheduled_date DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status ENUM('draft', 'pending', 'confirmed', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    total_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    notes TEXT NULL,
    CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_orders_service FOREIGN KEY (service_id) REFERENCES services(id),
    CONSTRAINT fk_orders_drone FOREIGN KEY (drone_id) REFERENCES drones(id)
) ENGINE=InnoDB;

CREATE TABLE media (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_type VARCHAR(100) NULL,
    file_url VARCHAR(500) NOT NULL,
    uploaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_media_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    key_name VARCHAR(100) NOT NULL UNIQUE,
    value_text TEXT NULL,
    description VARCHAR(255) NULL
) ENGINE=InnoDB;

INSERT INTO roles (id, name, description) VALUES
(1, 'Admin', 'Full administrative access'),
(2, 'User', 'Standard customer account');

INSERT INTO users (id, first_name, last_name, email, password_hash, phone, role_id, is_active) VALUES
(1, 'Haris', 'Kovacovic', 'admin@dronepilot.ba',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi',
 '+38761000000', 1, TRUE),
(2, 'Demo', 'User', 'haris@stu.ibu.edu.ba',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi',
 '+38761000001', 2, TRUE);

INSERT INTO services (name, description, price, duration_days, image_url, active) VALUES
('Drone Photography', 'Professional aerial photography sessions.', 150.00, 1, 'frontend/static/assets/images/sa_photo2.JPG', TRUE),
('Drone Inspection', 'Aerial inspection for buildings and infrastructure.', 300.00, 2, 'frontend/static/assets/images/insp.JPG', TRUE),
('3D Terrain Mapping', 'Drone-based terrain mapping and photogrammetry.', 500.00, 3, 'frontend/static/assets/images/map.JPG', TRUE);

INSERT INTO drones (brand, model, category, serial_number, status, last_service_date, battery_level, purchase_date, image_url) VALUES
('DJI', 'Lito 1', 'Compact', 'DJI-LITO-001', 'available', '2026-09-15', 100, '2024-01-20', 'frontend/static/assets/images/lito1.JPG'),
('DJI', 'Air 3S', 'Consumer', 'DJI-AIR3S-001', 'available', '2026-10-01', 95, '2025-04-10', 'frontend/static/assets/images/air3s.JPG'),
('DJI', 'Mavic 4 Pro', 'Professional', 'DJI-MAVIC4-001', 'reserved', '2026-09-25', 88, '2025-08-15', 'frontend/static/assets/images/mavic.JPG');

INSERT INTO orders (user_id, service_id, drone_id, order_number, title, description, scheduled_date, status, total_price, notes) VALUES
(2, 1, 1, 'DP-2026-0001', 'Property photography', 'Aerial photographs for a property listing.', '2026-11-05 10:00:00', 'confirmed', 150.00, 'Deliver edited photos in JPG format.'),
(2, 2, 3, 'DP-2026-0002', 'Roof inspection', 'Visual inspection of a commercial building roof.', '2026-11-08 09:30:00', 'pending', 300.00, 'Operator approval required.');

INSERT INTO media (order_id, file_name, file_type, file_url) VALUES
(1, 'property-aerial-01.jpg', 'image/jpeg', '/uploads/orders/property-aerial-01.jpg'),
(2, 'roof-inspection-preview.jpg', 'image/jpeg', '/uploads/orders/roof-inspection-preview.jpg');

INSERT INTO settings (key_name, value_text, description) VALUES
('site_name', 'DronePilot Services', 'Display name for the application'),
('contact_email', 'info@dronepilot.ba', 'Public contact email'),
('default_currency', 'BAM', 'Currency used for prices');
