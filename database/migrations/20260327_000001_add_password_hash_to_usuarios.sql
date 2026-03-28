ALTER TABLE `usuarios`
    ADD COLUMN `password_hash` VARCHAR(255) NULL AFTER `password`;

