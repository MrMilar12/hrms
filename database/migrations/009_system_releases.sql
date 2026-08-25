CREATE TABLE IF NOT EXISTS system_releases (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    version VARCHAR(30) NOT NULL UNIQUE,
    title VARCHAR(150) NOT NULL,
    changes TEXT NOT NULL,
    released_at DATETIME NOT NULL,
    is_published TINYINT(1) NOT NULL DEFAULT 0,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_system_releases_published (is_published, released_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS user_release_views (
    user_id INT UNSIGNED NOT NULL,
    release_id INT UNSIGNED NOT NULL,
    viewed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, release_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (release_id) REFERENCES system_releases(id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT IGNORE INTO system_releases (version, title, changes, released_at, is_published)
VALUES (
    '1.1.0',
    'What\'s New in HRMS',
    'New system update announcements after login.\nA complete history of published changes.\nSystem version displayed throughout HRMS.',
    NOW(),
    1
);
