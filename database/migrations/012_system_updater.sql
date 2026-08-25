CREATE TABLE IF NOT EXISTS schema_migrations (
    migration VARCHAR(190) PRIMARY KEY,
    applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS system_deployments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    developer_id INT UNSIGNED NULL,
    from_version VARCHAR(30) NULL,
    to_version VARCHAR(30) NULL,
    from_commit CHAR(40) NULL,
    to_commit CHAR(40) NULL,
    status ENUM('success','failed') NOT NULL,
    details TEXT NULL,
    backup_files VARCHAR(500) NULL,
    backup_database VARCHAR(500) NULL,
    started_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (developer_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_system_deployments_started (started_at)
) ENGINE=InnoDB;
