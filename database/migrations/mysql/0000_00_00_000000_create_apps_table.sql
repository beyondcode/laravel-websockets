CREATE TABLE IF NOT EXISTS apps (
    `id` VARCHAR(255) NOT NULL,
    `key` VARCHAR(255) NOT NULL,
    `secret` VARCHAR(255) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `host` VARCHAR(255),
    `path` VARCHAR(255),
    `enable_client_messages` BOOLEAN DEFAULT 0,
    `enable_statistics` BOOLEAN DEFAULT 1,
    `capacity` INT,
    `allowed_origins` VARCHAR(255)
)
