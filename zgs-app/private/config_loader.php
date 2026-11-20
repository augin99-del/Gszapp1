<?php
declare(strict_types=1);
/** Never copy credentials from the source archive. Server config remains supported. */
function gszLoadConfig(): array {
    require_once __DIR__.'/config_security.php';require_once __DIR__.'/managed_config.php';require_once __DIR__.'/connection_diagnostics.php';$managed=gszManagedLoad();$configPath=empty($managed['managed_ignore_legacy'])?gszConfigPath():'';
    $config=require __DIR__.'/config.example.php';
    if($configPath!==''&&getenv('GSZ_CONFIG_FILE')!==false&&!is_file($configPath))throw new RuntimeException('GSZ_CONFIG_MISSING');
    if ($configPath!==''&&is_file($configPath)) {
        if(!gszConfigSecurity($configPath)['safe_location'])throw new RuntimeException('GSZ_CONFIG_PRIVATE');
        if(!is_readable($configPath))throw new RuntimeException('GSZ_CONFIG_READ');$local=require $configPath;
        if (!is_array($local)) throw new RuntimeException('GSZ_CONFIG_FORMAT');
        $config=array_replace($config,$local);
    }
    $vault=getenv('GSZ_SECRETS_FILE');$vaultKey=getenv('GSZ_SECRETS_KEY_FILE');
    if(empty($managed['managed_ignore_legacy'])&&($vault!==false||$vaultKey!==false)){
        if(!$vault||!$vaultKey)throw new RuntimeException('Vault und Schlüsselpfad gemeinsam konfigurieren.');
        require_once __DIR__.'/secret_vault.php';$config=array_replace($config,gszVaultLoad($vault,$vaultKey));
    }
    foreach (array_keys($config) as $key) {
        $value=getenv('GSZ_'.strtoupper($key));
        if ($value!==false) $config[$key]=$value;
    }
    $config=array_replace($config,$managed);
    $config['allow_mail_send']=filter_var($config['allow_mail_send']??false,FILTER_VALIDATE_BOOLEAN);
    return $config;
}
function gszConnectDatabase(array $config): PDO {
    require_once __DIR__.'/connection_diagnostics.php';if(!in_array('mysql',PDO::getAvailableDrivers(),true))throw new RuntimeException('GSZ_DB_DRIVER');foreach(['db_host','db_name','db_user','db_pass'] as $key)if(($key!=='db_host'||empty($config['db_socket']))&&str_starts_with((string)($config[$key]??''),'DATENBANK'))throw new RuntimeException('GSZ_DB_PLACEHOLDER');if(empty($config['db_socket']))$config=array_replace($config,gszDatabaseFields($config));
    $dsn=!empty($config['db_socket'])?'mysql:unix_socket='.$config['db_socket']:'mysql:host='.$config['db_host'].';port='.(int)($config['db_port']??3306);
    $pdo = new PDO($dsn.';dbname='.$config['db_name'].';charset=utf8mb4',$config['db_user'],$config['db_pass'],[PDO::ATTR_TIMEOUT=>5,PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
    $pdo->exec("SET time_zone = '+00:00'");
    return $pdo;
}
