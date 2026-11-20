-- Milestone 3: orders need a location so the booking weather check (Open-Meteo) knows where the job is.
-- Run this only on a database imported before Milestone 3.
-- A fresh import of database/dronepilot_services.sql already contains the column.
USE dronepilot_services;

ALTER TABLE orders ADD COLUMN location VARCHAR(150) NULL AFTER description;

UPDATE orders SET location = 'Sarajevo' WHERE order_number = 'DP-2026-0001';
UPDATE orders SET location = 'Mostar' WHERE order_number = 'DP-2026-0002';
