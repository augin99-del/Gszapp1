<?php
declare(strict_types=1);

/** Plain-text SMTP with mandatory STARTTLS and certificate validation. No attachments. */
function zgsSmtpSend(array $config, string $recipient, string $subject, string $body, ?string $replyTo = null, ?array $attachment = null): void {
    if (empty($config['allow_mail_send'])) throw new RuntimeException('E-Mail-Versand ist serverseitig gesperrt. Explizite Freigabe und Testkonto erforderlich.');

    $host = (string)($config['smtp_host'] ?? '');
    $port = (int)($config['smtp_port'] ?? 587);
    $user = (string)($config['mail_user'] ?? '');
    $pass = (string)($config['mail_pass'] ?? '');
    $sender = (string)($config['mail_from'] ?? $user);
    if (!preg_match('/^[a-zA-Z0-9.-]+$/D', $host) || $port !== 587
        || !filter_var($sender, FILTER_VALIDATE_EMAIL) || !filter_var($recipient, FILTER_VALIDATE_EMAIL)
        || $user === '' || $pass === '' || preg_match('/[\r\n]/', $recipient.$sender)) {
        throw new RuntimeException('SMTP ist nicht vollständig oder gültig konfiguriert.');
    }
    if ($replyTo !== null && !filter_var($replyTo, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Ungültige Antwortadresse.');
    if (strlen($body) > 150000 || mb_strlen($subject) > 190) throw new RuntimeException('Nachricht zu lang.');
    $ctx = stream_context_create(['ssl'=>[
        'verify_peer'=>true,'verify_peer_name'=>true,'allow_self_signed'=>false,
        'peer_name'=>$host,'SNI_enabled'=>true
    ]]);
    $socket = @stream_socket_client("tcp://{$host}:{$port}",$errno,$errstr,12,STREAM_CLIENT_CONNECT,$ctx);
    if (!$socket) throw new RuntimeException('SMTP-Server nicht erreichbar.');
    stream_set_timeout($socket,15);
    $read = static function() use ($socket): array {
        $lines=[]; $code=0;
        for ($i=0;$i<60;$i++) {
            $line=fgets($socket,8192);
            if ($line === false) throw new RuntimeException('SMTP-Antwort fehlt.');
            $lines[]=$line;
            if (!preg_match('/^(\d{3})([ -])/', $line,$m)) throw new RuntimeException('Ungültige SMTP-Antwort.');
            $code=(int)$m[1];
            if ($m[2]===' ') return [$code,implode('',$lines)];
        }
        throw new RuntimeException('SMTP-Antwort zu lang.');
    };
    $expect = static function(array $codes) use ($read): string {
        [$code,$response]=$read();
        if (!in_array($code,$codes,true)) throw new RuntimeException('SMTP-Server hat den Vorgang abgelehnt ('.$code.').');
        return $response;
    };
    $command = static function(string $line,array $codes) use ($socket,$expect): string {
        if (fwrite($socket,$line."\r\n") === false) throw new RuntimeException('SMTP-Verbindung unterbrochen.');
        return $expect($codes);
    };
    try {
        $expect([220]);
        $hello=$command('EHLO zgs-app.local',[250]);
        if (!preg_match('/^250[ -]STARTTLS\b/mi',$hello)) throw new RuntimeException('SMTP-Server bietet kein STARTTLS an.');
        $command('STARTTLS',[220]);
        if (@stream_socket_enable_crypto($socket,true,STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT) !== true) {
            throw new RuntimeException('TLS-Verschlüsselung konnte nicht aufgebaut werden.');
        }
        $hello=$command('EHLO zgs-app.local',[250]);
        if (!preg_match('/^250[ -]AUTH\b[^\r\n]*\bPLAIN\b/mi',$hello)) {
            throw new RuntimeException('SMTP-Server unterstützt die erforderliche Authentifizierung nicht.');
        }
        $command('AUTH PLAIN '.base64_encode("\0".$user."\0".$pass),[235]);
        $command('MAIL FROM:<'.$sender.'>',[250]);
        $command('RCPT TO:<'.$recipient.'>',[250,251]);
        $command('DATA',[354]);
        $cleanSubject=str_replace(["\r","\n"],' ',trim($subject));
        $encodedSubject='=?UTF-8?B?'.base64_encode($cleanSubject).'?=';
        $headers=[
            'Date: '.date('r'),
            'From: <'.$sender.'>',
            'To: <'.$recipient.'>',
            'Subject: '.$encodedSubject,
            'Message-ID: <'.bin2hex(random_bytes(12)).'@'.$host.'>',
            'MIME-Version: 1.0',

        ];
        if ($replyTo !== null) $headers[]='Reply-To: <'.$replyTo.'>';
        $encodedBody=rtrim(chunk_split(base64_encode(str_replace(["\r\n","\r"],"\n",$body)),76,"\r\n"));
        if ($attachment !== null) {
            $file=(string)($attachment['data']??'');
            $name=(string)($attachment['name']??'invoice.pdf');
            if(strlen($file)>3000000 || !preg_match('/^[a-zA-Z0-9._-]{1,100}$/D',$name)) throw new RuntimeException('Ungültiger Anhang.');
            $boundary='zgs_'.bin2hex(random_bytes(16));
            $headers[]='Content-Type: multipart/mixed; boundary="'.$boundary.'"';
            $payload=implode("\r\n",$headers)."\r\n\r\n";
            $payload.='--'.$boundary."\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n".$encodedBody."\r\n";
            $payload.='--'.$boundary."\r\nContent-Type: application/pdf; name=\"".$name."\"\r\nContent-Disposition: attachment; filename=\"".$name."\"\r\nContent-Transfer-Encoding: base64\r\n\r\n";
            $payload.=rtrim(chunk_split(base64_encode($file),76,"\r\n"))."\r\n--".$boundary."--\r\n.\r\n";
        } else {
            $headers[]='Content-Type: text/plain; charset=UTF-8';
            $headers[]='Content-Transfer-Encoding: base64';
            $payload=implode("\r\n",$headers)."\r\n\r\n".$encodedBody."\r\n.\r\n";
        }
        if (fwrite($socket,$payload) !== strlen($payload)) throw new RuntimeException('SMTP-Nachricht konnte nicht vollständig übertragen werden.');
        $expect([250]);
        $command('QUIT',[221]);
    } finally {
        fclose($socket);
    }
}
