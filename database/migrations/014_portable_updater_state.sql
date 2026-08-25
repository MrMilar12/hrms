CREATE TABLE IF NOT EXISTS system_update_state (
    id TINYINT UNSIGNED PRIMARY KEY,
    deployed_commit CHAR(40) NULL,
    deployed_version VARCHAR(30) NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT IGNORE INTO system_update_state (id, deployed_commit, deployed_version)
VALUES (1, NULL, NULL);
