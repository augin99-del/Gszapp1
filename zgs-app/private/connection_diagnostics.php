<?php
declare(strict_types=1);
function gszDatabaseFields(array $in):array {
 $host=trim((string)($in['db_host']??''));$port=(string)($in['db_port']??'3306');if(preg_match('/^([a-z0-9.-]+):([0-9]+)$/iD',$host,$m)){$host=$m[1];$port=$m[2];}
 if($host===''||strlen($host)>253||(!filter_var($host,FILTER_VALIDATE_IP)&&!preg_match('/^[a-z0-9](?:[a-z0-9.-]*[a-z0-9])?$/iD',$host)))throw new RuntimeException('GSZ_DB_HOST_FORMAT');$port=filter_var($port,FILTER_VALIDATE_INT);if(!$port||$port<1||$port>65535)throw new RuntimeException('GSZ_DB_PORT');$out=['db_host'=>$host,'db_port'=>$port,'db_name'=>(string)($in['db_name']??''),'db_user'=>(string)($in['db_user']??''),'db_pass'=>(string)($in['db_pass']??''),'db_socket'=>''];foreach(['db_name','db_user'] as $key)if($out[$key]===''||strlen($out[$key])>190||preg_match('/[;\x00-\x20\x7f]/',$out[$key]))throw new RuntimeException('GSZ_DB_FIELDS');if($out['db_pass']===''||strlen($out['db_pass'])>1024||str_contains($out['db_pass'],"\0"))throw new RuntimeException('GSZ_DB_PASSWORD_MISSING');return $out;
}
function gszSafeError(Throwable $e):array {
 $code=$e->getMessage();$mysql=$e instanceof PDOException?(int)($e->errorInfo[1]??0):0;
 if($e instanceof ParseError)$code='GSZ_CONFIG_PARSE';elseif($e instanceof PDOException)$code=match($mysql){1045=>'GSZ_DB_AUTH',1044=>'GSZ_DB_PERMISSION',1049=>'GSZ_DB_NAME',2005=>'GSZ_DB_DNS',2002,2003=>'GSZ_DB_NETWORK',default=>'GSZ_DB_FAILED'};
 if($e instanceof PDOException&&$mysql===2002&&preg_match('/getaddrinfo|php_network_getaddresses|Name or service not known/i',$e->getMessage()))$code='GSZ_DB_DNS';
 $messages=[
 'GSZ_CONFIG_MISSING'=>'Die konfigurierte externe Datei fehlt. GSZ_CONFIG_FILE im Hosting prüfen oder im geschützten Assistenten die alte Konfiguration bewusst übergehen.',
 'GSZ_CONFIG_PARSE'=>'Die bisherige config.php enthält einen PHP-Syntaxfehler. Über den geschützten Assistenten eine neue verwaltete Konfiguration einrichten; die alte Datei bleibt erhalten.',
 'GSZ_CONFIG_READ'=>'Die private Konfiguration ist nicht lesbar. Dateieigentümer und Zugriffsrechte im STRATO-Dateimanager prüfen oder den geschützten Assistenten verwenden.',
 'GSZ_CONFIG_FORMAT'=>'Die bisherige config.php liefert keine Konfigurationsliste. Im geschützten Assistenten die Verbindung neu einrichten.',
 'GSZ_CONFIG_PRIVATE'=>'Die Konfiguration liegt innerhalb der Webwurzel oder verwendet einen Symlink. Domain-Ziel auf zgs-app/public setzen; Installation bis dahin gesperrt.',
 'GSZ_DB_DRIVER'=>'PDO/MySQL fehlt. Im STRATO-PHP-Bereich pdo_mysql aktivieren.',
 'GSZ_DB_PLACEHOLDER'=>'Die Beispielwerte sind noch aktiv. Datenbank im geschützten Assistenten einrichten.',
 'GSZ_DB_HOST_FORMAT'=>'Datenbankhost ist ungültig. Nur den Host aus STRATO, ohne https:// oder Datenbankpfad, eingeben.',
 'GSZ_DB_PORT'=>'Datenbankport muss zwischen1 und65535 liegen.',
 'GSZ_DB_FIELDS'=>'Datenbankname und Benutzername aus der STRATO-Datenbankverwaltung vollständig eingeben.',
 'GSZ_DB_PASSWORD_MISSING'=>'Das Datenbankpasswort fehlt. Nur im geschützten HTTPS-Formular eingeben.',
 'GSZ_DB_AUTH'=>'Die Datenbank hat die Anmeldung abgelehnt. Benutzer, Passwort und Zuordnung des Benutzers zur STRATO-Testdatenbank prüfen.',
 'GSZ_DB_PERMISSION'=>'Anmeldung möglich, Zugriff auf diese Datenbank jedoch verweigert. Benutzerrechte in STRATO prüfen.',
 'GSZ_DB_NAME'=>'Die angegebene Datenbank wurde auf diesem Server nicht gefunden. Datenbankname und Host aus STRATO prüfen.',
 'GSZ_DB_DNS'=>'Der Datenbankhostname konnte nicht aufgelöst werden. Den vollständigen STRATO-Datenbankhost prüfen.',
 'GSZ_DB_NETWORK'=>'Der Datenbankserver ist nicht erreichbar oder die Verbindung wurde abgewiesen. Host, Port und Hostingfreigabe prüfen.',
 'GSZ_DB_FAILED'=>'Die Datenbankverbindung konnte nicht bestätigt werden. Im geschützten Assistenten Verbindung und Schemazustand prüfen.',
 'GSZ_PRIVATE_WEBROOT'=>'Private Dateien liegen innerhalb der gemeldeten Webwurzel. STRATO-Domain direkt auf zgs-app/public richten; keine bloße Browserweiterleitung verwenden.',
 'GSZ_PRIVATE_PERMISSIONS'=>'Private Dateirechte sind zu offen. PHP-Dateieigentümer und Rechte im STRATO-Dateimanager prüfen.',
 'GSZ_PRIVATE_WRITE'=>'PHP kann im privaten Programmordner nicht sicher schreiben. Dateieigentümer und Hostingrechte prüfen.',
 'GSZ_PRIVATE_READ'=>'Private Einrichtungs-/Konfigurationsdatei ist nicht sicher lesbar. Rechte und vollständige gemeinsame Dateisicherung prüfen.',
 'GSZ_MANAGED_CONFIG'=>'Verwaltete Konfiguration und Schlüssel passen nicht zusammen. Beide aus derselben geschützten Sicherung wiederherstellen.',
 ];if(!isset($messages[$code]))$code='GSZ_DB_FAILED';return ['code'=>$code,'message'=>$messages[$code]];
}
function gszConfigSources():array {
 $keys=[];foreach(['db_host','db_port','db_socket','db_name','db_user','db_pass','config_file','secrets_file','secrets_key_file'] as $key)if(getenv('GSZ_'.strtoupper($key))!==false)$keys[]='GSZ_'.strtoupper($key);
 return ['legacy_file_exists'=>is_file(__DIR__.'/config.php'),'managed_exists'=>is_file(__DIR__.'/server-config/managed.json'),'environment_keys'=>$keys,'priority'=>'Assistentenwerte > Umgebungswerte > Vault > private/config.php > Vorlage'];
}
