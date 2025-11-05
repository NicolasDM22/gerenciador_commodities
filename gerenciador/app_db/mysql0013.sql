SET @OLD_SQL_SAFE_UPDATES = @@SQL_SAFE_UPDATES;
SET SQL_SAFE_UPDATES = 0;

UPDATE `users` AS u
INNER JOIN (
    SELECT id, CONCAT(usuario, '@example.local') AS new_email
    FROM `users`
    WHERE `email` IS NULL OR TRIM(COALESCE(`email`, '')) = ''
) AS filler ON filler.id = u.id
SET u.email = filler.new_email
WHERE u.id = filler.id;

ALTER TABLE `users`
    ADD COLUMN `telefone` varchar(20) COLLATE utf8mb4_unicode_ci NULL AFTER `email`,
    ADD COLUMN `endereco` varchar(255) COLLATE utf8mb4_unicode_ci NULL AFTER `telefone`;

UPDATE `users` AS u
INNER JOIN (
    SELECT id,
           COALESCE(NULLIF(TRIM(`telefone`), ''), '0000000000') AS phone_fill,
           COALESCE(NULLIF(TRIM(`endereco`), ''), 'Endereco nao informado') AS address_fill
    FROM `users`
    WHERE `telefone` IS NULL
       OR `endereco` IS NULL
       OR TRIM(COALESCE(`telefone`, '')) = ''
       OR TRIM(COALESCE(`endereco`, '')) = ''
) AS filler ON filler.id = u.id
SET u.telefone = filler.phone_fill,
    u.endereco = filler.address_fill
WHERE u.id = filler.id;

ALTER TABLE `users`
    MODIFY `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
    MODIFY `telefone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
    MODIFY `endereco` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL;

SET SQL_SAFE_UPDATES = @OLD_SQL_SAFE_UPDATES;
