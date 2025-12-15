-- Add GitHub OAuth support to existing users table
-- Run this SQL in phpMyAdmin or MySQL console

USE user_management;

-- Add github_id column
ALTER TABLE users 
ADD COLUMN github_id VARCHAR(50) NULL UNIQUE AFTER password,
ADD INDEX idx_github_id (github_id);

-- Make password nullable (for GitHub OAuth users)
ALTER TABLE users 
MODIFY COLUMN password VARCHAR(255) NULL;
