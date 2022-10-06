CREATE TABLE IF NOT EXISTS apps (
    id STRING NOT NULL,
    key STRING NOT NULL,
    secret STRING NOT NULL,
    name STRING NOT NULL,
    host STRING NULLABLE,
    path STRING NULLABLE,
    enable_client_messages BOOLEAN DEFAULT 0,
    enable_statistics BOOLEAN DEFAULT 1,
    capacity INTEGER NULLABLE,
    allowed_origins STRING NULLABLE
)
