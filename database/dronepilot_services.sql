-- Import as UTF-8 so names such as Hodžić and Kurtović are stored correctly.
SET NAMES utf8mb4;

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
    location VARCHAR(150) NULL,
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

-- All sample accounts use the password: DronePilot123! (bcrypt hash from password_hash()).
INSERT INTO users (id, first_name, last_name, email, password_hash, phone, role_id, is_active) VALUES
(1, 'Haris', 'Kovacovic', 'admin@dronepilot.ba',
 '$2y$12$cUJrlBOsxeZCZWGLcqhyIeGl5wOwp1QrzcgfO38.CnyuEaDex/Aby', '+38761000000', 1, TRUE),
(2, 'Demo', 'User', 'haris@stu.ibu.edu.ba',
 '$2y$12$cUJrlBOsxeZCZWGLcqhyIeGl5wOwp1QrzcgfO38.CnyuEaDex/Aby', '+38761000001', 2, TRUE),
(3, 'Adnan', 'Hodžić', 'adnan.hodzic@example.ba',
 '$2y$12$cUJrlBOsxeZCZWGLcqhyIeGl5wOwp1QrzcgfO38.CnyuEaDex/Aby', '+38762111222', 2, TRUE),
(4, 'Marina', 'Tomić', 'marina.tomic@example.ba',
 '$2y$12$cUJrlBOsxeZCZWGLcqhyIeGl5wOwp1QrzcgfO38.CnyuEaDex/Aby', '+38763222333', 2, TRUE),
(5, 'Emir', 'Begović', 'emir.begovic@gradnjabh.ba',
 '$2y$12$cUJrlBOsxeZCZWGLcqhyIeGl5wOwp1QrzcgfO38.CnyuEaDex/Aby', '+38761333444', 2, TRUE),
(6, 'Lejla', 'Kurtović', 'lejla.kurtovic@example.ba',
 '$2y$12$cUJrlBOsxeZCZWGLcqhyIeGl5wOwp1QrzcgfO38.CnyuEaDex/Aby', '+38765444555', 2, TRUE),
(7, 'Tarik', 'Mehmedović', 'tarik.mehmedovic@example.ba',
 '$2y$12$cUJrlBOsxeZCZWGLcqhyIeGl5wOwp1QrzcgfO38.CnyuEaDex/Aby', NULL, 2, FALSE);

INSERT INTO services (id, name, description, price, duration_days, image_url, active) VALUES
(1, 'Snimanje dronom', 'Profesionalne fotografije i video iz zraka za nekretnine, događaje i kampanje.', 150.00, 1, 'frontend/static/assets/images/sa_photo2.JPG', TRUE),
(2, 'Inspekcija dronom', 'Vizualna inspekcija krovova, fasada, dalekovoda i industrijskih objekata.', 300.00, 2, 'frontend/static/assets/images/insp.JPG', TRUE),
(3, '3D mapiranje terena', 'Fotogrametrija, ortofoto snimci i 3D modeli terena.', 500.00, 3, 'frontend/static/assets/images/map.JPG', TRUE),
(4, 'Snimanje događaja', 'Video snimanje vjenčanja, koncerata i sportskih događaja iz zraka.', 250.00, 1, 'frontend/static/assets/images/sa_photo.JPG', TRUE),
(5, 'Termalna inspekcija', 'Termovizijska kontrola solarnih panela i izolacije objekata.', 450.00, 2, NULL, FALSE);

INSERT INTO drones (id, brand, model, category, serial_number, status, last_service_date, battery_level, purchase_date, image_url) VALUES
(1, 'DJI', 'Lito 1', 'Compact', 'DJI-LITO-001', 'available', '2026-09-15', 100, '2024-01-20', 'frontend/static/assets/images/lito1.JPG'),
(2, 'DJI', 'Air 3S', 'Consumer', 'DJI-AIR3S-001', 'available', '2026-10-01', 95, '2025-04-10', 'frontend/static/assets/images/air3s.JPG'),
(3, 'DJI', 'Mavic 4 Pro', 'Professional', 'DJI-MAVIC4-001', 'reserved', '2026-09-25', 88, '2025-08-15', 'frontend/static/assets/images/mavic.JPG'),
(4, 'DJI', 'Mini 4 Pro', 'Compact', 'DJI-MINI4-001', 'available', '2026-10-20', 76, '2025-02-03', NULL),
(5, 'DJI', 'Matrice 350 RTK', 'Enterprise', 'DJI-M350-001', 'maintenance', '2026-11-10', 40, '2024-06-12', NULL),
(6, 'DJI', 'Avata 2', 'FPV', 'DJI-AVATA2-001', 'retired', '2026-03-02', NULL, '2023-11-05', NULL);

INSERT INTO orders (id, user_id, service_id, drone_id, order_number, title, description, location, scheduled_date, status, total_price, notes) VALUES
(1, 2, 1, 1, 'DP-2026-0001', 'Fotografisanje nekretnine', 'Fotografije iz zraka za oglas za prodaju kuće.', 'Sarajevo', '2026-11-05 10:00:00', 'completed', 150.00, 'Obrađene fotografije isporučiti u JPG formatu.'),
(2, 2, 2, 3, 'DP-2026-0002', 'Inspekcija krova', 'Vizualna inspekcija krova poslovnog objekta.', 'Mostar', '2026-11-27 09:30:00', 'pending', 300.00, 'Potrebna saglasnost upravitelja zgrade.'),
(3, 3, 1, 2, 'DP-2026-0003', 'Promotivni video hotela', 'Kratki promotivni video hotela i plaže.', 'Neum', '2026-10-14 11:00:00', 'completed', 150.00, NULL),
(4, 4, 2, 5, 'DP-2026-0004', 'Inspekcija dalekovoda', 'Pregled izolatora i vodova na dionici od 3 km.', 'Zenica', '2026-10-20 08:00:00', 'completed', 300.00, 'Izvještaj s fotografijama oštećenja.'),
(5, 5, 3, 3, 'DP-2026-0005', '3D model gradilišta', 'Ortofoto i 3D model gradilišta stambenog naselja.', 'Tuzla', '2026-11-02 09:00:00', 'completed', 500.00, NULL),
(6, 3, 4, 2, 'DP-2026-0006', 'Snimanje vjenčanja', 'Snimanje ceremonije i dolaska gostiju iz zraka.', 'Banja Luka', '2026-11-24 15:00:00', 'confirmed', 250.00, 'Let samo uz dozvolu organizatora.'),
(7, 6, 3, 3, 'DP-2026-0007', 'Mapiranje parcele', 'Geodetsko mapiranje poljoprivredne parcele.', 'Bihać', '2026-11-21 10:00:00', 'in_progress', 500.00, NULL),
(8, 4, 1, NULL, 'DP-2026-0008', 'Fotografije poslovnog objekta', 'Fotografije novog poslovnog centra za web stranicu.', 'Sarajevo', '2026-12-03 12:00:00', 'pending', 150.00, NULL),
(9, 5, 2, NULL, 'DP-2026-0009', 'Inspekcija fasade', 'Pregled fasade nakon nevremena.', 'Mostar', '2026-10-28 10:00:00', 'cancelled', 300.00, 'Otkazano zbog jakog vjetra.'),
(10, 6, 4, 4, 'DP-2026-0010', 'Snimanje koncerta', 'Snimanje koncerta na otvorenom.', 'Sarajevo', '2026-12-12 20:00:00', 'draft', 250.00, 'Čeka potvrdu termina.');

INSERT INTO media (order_id, file_name, file_type, file_url) VALUES
(1, 'nekretnina-zrak-01.jpg', 'image/jpeg', '/uploads/orders/nekretnina-zrak-01.jpg'),
(1, 'nekretnina-zrak-02.jpg', 'image/jpeg', '/uploads/orders/nekretnina-zrak-02.jpg'),
(2, 'krov-pregled.jpg', 'image/jpeg', '/uploads/orders/krov-pregled.jpg'),
(3, 'hotel-promo.mp4', 'video/mp4', '/uploads/orders/hotel-promo.mp4'),
(4, 'dalekovod-izvjestaj.pdf', 'application/pdf', '/uploads/orders/dalekovod-izvjestaj.pdf'),
(5, 'gradiliste-ortofoto.tiff', 'image/tiff', '/uploads/orders/gradiliste-ortofoto.tiff'),
(5, 'gradiliste-3d-model.zip', 'application/zip', '/uploads/orders/gradiliste-3d-model.zip');

INSERT INTO settings (key_name, value_text, description) VALUES
('site_name', 'DronePilot Services', 'Display name for the application'),
('contact_email', 'info@dronepilot.ba', 'Public contact email'),
('default_currency', 'BAM', 'Currency used for prices'),
('support_phone', '+387 33 000 000', 'Customer support phone number'),
('working_hours', '08:00 - 18:00', 'Hours in which flights are scheduled');
