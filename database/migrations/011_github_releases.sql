ALTER TABLE system_releases
    ADD COLUMN github_release_id BIGINT UNSIGNED NULL UNIQUE AFTER created_by,
    ADD COLUMN release_url VARCHAR(500) NULL AFTER github_release_id;
