-- ============================================================
--  Back2U — College Lost & Found
--  Full database setup — run this on any new device
--  phpMyAdmin → nmims_db → SQL tab → paste → Go
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS `nmims_db`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `nmims_db`;

-- ------------------------------------------------------------
-- Table: users
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id`            INT          NOT NULL AUTO_INCREMENT,
    `name`          VARCHAR(100) NOT NULL,
    `email`         VARCHAR(100) NOT NULL,
    `password`      VARCHAR(255) NOT NULL,
    `role`          ENUM('user','admin') NOT NULL DEFAULT 'user',
    `profile_photo` VARCHAR(255)          DEFAULT NULL,
    `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: items
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `items` (
    `id`             INT          NOT NULL AUTO_INCREMENT,
    `owner_id`       INT                   DEFAULT NULL,
    `title`          VARCHAR(100)          DEFAULT NULL,
    `description`    TEXT                  DEFAULT NULL,
    `image_path`     VARCHAR(255)          DEFAULT NULL,
    `status`         ENUM('registered','lost','pending','claimed') NOT NULL DEFAULT 'registered',
    `found_by`       INT                   DEFAULT NULL,
    `claimed_by`     INT                   DEFAULT NULL,
    `college_domain` VARCHAR(100)          DEFAULT NULL,
    `updated_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `owner_id`       (`owner_id`),
    KEY `found_by`       (`found_by`),
    KEY `claimed_by`     (`claimed_by`),
    KEY `college_domain` (`college_domain`),
    CONSTRAINT `items_owner`   FOREIGN KEY (`owner_id`)   REFERENCES `users`(`id`) ON DELETE SET NULL,
    CONSTRAINT `items_founder` FOREIGN KEY (`found_by`)   REFERENCES `users`(`id`) ON DELETE SET NULL,
    CONSTRAINT `items_claimer` FOREIGN KEY (`claimed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: found_reports
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `found_reports` (
    `id`          INT  NOT NULL AUTO_INCREMENT,
    `finder_id`   INT  NOT NULL,
    `image_path`  VARCHAR(255) DEFAULT NULL,
    `description` TEXT         NOT NULL,
    `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `finder_id` (`finder_id`),
    CONSTRAINT `fr_finder` FOREIGN KEY (`finder_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: messages
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `messages` (
    `id`         INT  NOT NULL AUTO_INCREMENT,
    `item_id`    INT  NOT NULL,
    `sender_id`  INT  NOT NULL,
    `message`    TEXT NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `item_id`   (`item_id`),
    KEY `sender_id` (`sender_id`),
    CONSTRAINT `msg_item`   FOREIGN KEY (`item_id`)   REFERENCES `items`(`id`)  ON DELETE CASCADE,
    CONSTRAINT `msg_sender` FOREIGN KEY (`sender_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: allowed_domains
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `allowed_domains` (
    `id`         INT          NOT NULL AUTO_INCREMENT,
    `domain`     VARCHAR(100) NOT NULL,
    `college`    VARCHAR(150)          DEFAULT NULL,
    `approved`   TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `domain` (`domain`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pre-seeded college domains
INSERT IGNORE INTO `allowed_domains` (`domain`, `college`, `approved`) VALUES
('nmims.edu',         'NMIMS',              1),
('nmims.in',          'NMIMS',              1),
('iitb.ac.in',        'IIT Bombay',         1),
('iitd.ac.in',        'IIT Delhi',          1),
('iitm.ac.in',        'IIT Madras',         1),
('iitk.ac.in',        'IIT Kanpur',         1),
('bits-pilani.ac.in', 'BITS Pilani',        1),
('vit.ac.in',         'VIT',                1),
('manipal.edu',       'Manipal University', 1),
('christ.edu',        'Christ University',  1),
('du.ac.in',          'Delhi University',   1),
('mu.ac.in',          'Mumbai University',  1),
('pu.ac.in',          'Pune University',    1),
('somaiya.edu',       'Somaiya Vidyavihar', 1),
('spit.ac.in',        'SP Jain',            1);

-- ------------------------------------------------------------
-- Admin account
-- Email   : admin@back2u.org
-- Password: admin123
-- Change after first login!
-- ------------------------------------------------------------
INSERT IGNORE INTO `users` (`name`, `email`, `password`, `role`) VALUES
('Admin', 'admin@back2u.org',
 '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B9bd/C2',
 'admin');

-- ------------------------------------------------------------
-- If upgrading from old database — add college_domain column
-- (safe to run even if column already exists)
-- ------------------------------------------------------------
ALTER TABLE `items`
  ADD COLUMN IF NOT EXISTS `college_domain` VARCHAR(100) DEFAULT NULL;

