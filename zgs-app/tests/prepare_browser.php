<?php
declare(strict_types=1);
$dsn=getenv('GSZ_TEST_DSN')?:'';
if(!preg_match('/^mysql:unix_socket=[^;]+;dbname=gsz_test_[a-z0-9_]+$/D',$dsn))throw new RuntimeException('Local test DSN required.');
$pdo=new PDO($dsn,'root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
if($pdo->query('SHOW TABLES')->fetchAll())throw new RuntimeException('Empty test database required.');
require dirname(__DIR__).'/private/install_core.php';gszInstallFresh($pdo);
$pdo->prepare('INSERT INTO admins(email,password_hash) VALUES(?,?)')->execute(['owner@example.invalid',password_hash('Local-Test-Only-2026!',PASSWORD_DEFAULT)]);
echo "Synthetic browser fixture ready.\n";
