-- Schema 56.1
START TRANSACTION;
    ALTER TABLE `users` ADD `default_role` ENUM('user', 'admin') NOT NULL DEFAULT 'user';
COMMIT;
