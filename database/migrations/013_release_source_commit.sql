ALTER TABLE system_releases
    ADD COLUMN source_commit CHAR(40) NULL UNIQUE AFTER release_url;
