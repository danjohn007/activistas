-- Migration to add social media and payment account fields to usuarios table
-- Run this SQL script to add the new columns for social media links and payment account

ALTER TABLE usuarios 
ADD COLUMN facebook VARCHAR(255) DEFAULT NULL,
ADD COLUMN instagram VARCHAR(255) DEFAULT NULL,
ADD COLUMN tiktok VARCHAR(255) DEFAULT NULL,
ADD COLUMN x VARCHAR(255) DEFAULT NULL,
ADD COLUMN cuenta_pago VARCHAR(255) DEFAULT NULL;

-- Update the database schema to reflect the changes
-- This migration adds:
-- facebook: URL to Facebook profile/page
-- instagram: URL to Instagram profile
-- tiktok: URL to TikTok profile
-- x: URL to X (formerly Twitter) profile
-- cuenta_pago: Payment account information (could be bank account, PayPal, etc.)