-- Runs once, the first time the database container starts.
-- Creates the separate database used by php artisan test.
CREATE DATABASE IF NOT EXISTS hospital_test;
GRANT ALL PRIVILEGES ON hospital_test.* TO 'hospital'@'%';
FLUSH PRIVILEGES;