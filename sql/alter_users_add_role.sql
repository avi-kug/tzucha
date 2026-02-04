ALTER TABLE users
    ADD COLUMN role ENUM('admin','manager','viewer') NOT NULL DEFAULT 'viewer' AFTER password_hash;
