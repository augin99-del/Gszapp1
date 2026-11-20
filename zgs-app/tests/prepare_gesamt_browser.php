<?php
declare(strict_types=1);
// Synthetic fixtures only. Never run with production credentials or on a populated schema.
if(PHP_SAPI!=='cli') {http_response_code(403);exit;}
require dirname(__DIR__).'/private/migrate_phase2.php';
require dirname(__DIR__).'/private/migrate_phase3.php';
require dirname(__DIR__).'/private/migrate_gesamt.php';
require dirname(__DIR__).'/private/phase1_import.php';
$dsn=getenv('GSZ_TEST_DSN')?:'';
if(!preg_match('/^mysql:unix_socket=[^;]+;dbname=gsz_test_[a-z0-9_]+$/D',$dsn))throw new RuntimeException('Local synthetic test database required.');
$pdo=new PDO($dsn,'root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
if($pdo->query('SHOW TABLES')->fetchAll())throw new RuntimeException('Empty database required.');
$pdo->exec(file_get_contents(dirname(__DIR__).'/private/fresh_install_v192.sql'));
$pdo->exec(file_get_contents(dirname(__DIR__).'/private/migration_v200.sql'));
gszMigratePhase2($pdo);gszMigratePhase3($pdo);gszMigrateGesamt($pdo);
$pdo->exec("INSERT INTO customers(name,email) VALUES('Synthetic customer','customer@example.invalid')");
$p=gszImportPreview($pdo,'2035-01-01');gszApplyImport($pdo,'2035-01-01',$p['fingerprint']);
$pdo->prepare('INSERT INTO admins(email,password_hash) VALUES(?,?)')->execute(['owner@example.invalid',password_hash('Local-Test-Only-2026!',PASSWORD_DEFAULT)]);
$pdo->exec("INSERT INTO mail_folders(name,uidvalidity,last_uid,last_sync) VALUES('INBOX',1,35,UTC_TIMESTAMP())");
$folder=(int)$pdo->lastInsertId();$q=$pdo->prepare('INSERT INTO mail_messages(folder_id,uidvalidity,remote_uid,sender,recipients,subject,body,sent_at,important) VALUES(?,1,?,?,?,?,?,UTC_TIMESTAMP()-INTERVAL 2 DAY,1)');
for($i=1;$i<=35;$i++)$q->execute([$folder,$i,'customer@example.invalid','owner@example.invalid','Synthetic local message '.$i,'No external content <script>']);
echo "Synthetic overall browser fixture ready (35 messages, 7 objects).",PHP_EOL;
