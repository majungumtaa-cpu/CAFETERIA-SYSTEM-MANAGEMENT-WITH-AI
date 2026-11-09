-- Add status column to order table if it doesn't exist
-- Run this if you have an existing database

ALTER TABLE `order` ADD COLUMN `status` VARCHAR(50) DEFAULT 'pending' AFTER `order_date`;
