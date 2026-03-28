ALTER TABLE `usuarios`
    MODIFY `password` VARCHAR(255) NOT NULL DEFAULT '';

ALTER TABLE `usuarios`
    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE `cliente`
    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE `producto`
    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE `proveedor`
    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE `usuarios`
    ENGINE=InnoDB;

ALTER TABLE `cliente`
    ENGINE=InnoDB;

ALTER TABLE `producto`
    ENGINE=InnoDB;

ALTER TABLE `proveedor`
    ENGINE=InnoDB;

