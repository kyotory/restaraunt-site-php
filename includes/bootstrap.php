<?php

function bootstrap_database(mysqli $mysqli)
{
    static $bootstrapped = false;

    if ($bootstrapped) {
        return;
    }

    $bootstrapped = true;
    drop_legacy_memory_offer_table($mysqli);
    ensure_memory_news_table($mysqli);
}

function drop_legacy_memory_offer_table(mysqli $mysqli)
{
    $mysqli->query("DROP TABLE IF EXISTS `memory_offer`");
}

function ensure_memory_news_table(mysqli $mysqli)
{
    $mysqli->query(
        "CREATE TABLE IF NOT EXISTS `memory_news` (
            `id_memory_news` INT NOT NULL AUTO_INCREMENT,
            `title_news` VARCHAR(180) COLLATE utf8mb4_unicode_ci NOT NULL,
            `excerpt_news` VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,
            `content_news` VARCHAR(2000) COLLATE utf8mb4_unicode_ci NOT NULL,
            `publish_date` DATE NOT NULL,
            `image_path` VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '/assets/img/logo.svg',
            PRIMARY KEY (`id_memory_news`)
        ) ENGINE=MEMORY DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $mysqli->query("ALTER TABLE `memory_news` ENGINE = MEMORY");
}
