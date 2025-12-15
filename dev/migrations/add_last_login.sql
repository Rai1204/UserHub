-- Add last_login column to users table
-- Run this in your MySQL client (phpMyAdmin, MySQL Workbench, or command line)

USE user_management;

ALTER TABLE users ADD COLUMN last_login TIMESTAMP NULL DEFAULT NULL AFTER password;
