<?php

/*
 * Antes dos testes: garante que o banco "facilmed_testes" existe.
 *
 * Assim ninguém precisa criar nada no phpMyAdmin para rodar
 * `php artisan test`. O banco de desenvolvimento ("facilmed") nunca é
 * tocado: os testes apagam e recriam tudo, e por isso usam outro nome.
 */

require __DIR__ . '/../vendor/autoload.php';

$host  = getenv('DB_HOST') ?: '127.0.0.1';
$porta = getenv('DB_PORT') ?: '3306';
$banco = getenv('DB_DATABASE') ?: 'facilmed_testes';

if ($banco === 'facilmed') {
    fwrite(STDERR, "\nRecusado: os testes apagam o banco. Use outro nome em phpunit.xml (ex.: facilmed_testes).\n");
    exit(1);
}

try {
    $pdo = new PDO("mysql:host={$host};port={$porta}", getenv('DB_USERNAME') ?: 'root', getenv('DB_PASSWORD') ?: '');
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$banco}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
} catch (PDOException $e) {
    fwrite(STDERR, "\nNao consegui conectar no MySQL/MariaDB ({$host}:{$porta}). O MySQL do XAMPP esta ligado?\n{$e->getMessage()}\n");
    exit(1);
}
