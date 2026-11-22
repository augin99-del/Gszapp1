# Gebündelter nativer-IMAP-freier Adapter

Geprüft13.11.2026. Hauptbibliothek DirectoryTree/ImapEngine v1.25.9, MIT, veröffentlichter Stand04.11.2026. Webklex/php-imap6.2.0 (MIT) ebenfalls geprüft, wegen zusätzlicher ext-zip-Anforderung nicht gewählt. Vollständiger Vendor und Autoloader liegen bei; kein Composer/SSH auf STRATO erforderlich. Composer-PlattformPHP8.2.0, alle Abhängigkeiten exakt in composer.lock gepinnt. Erforderlich: PHP>=8.2, OpenSSL, mbstring/iconv/ctype/json; natives IMAP und ZIP nicht erforderlich. Keine Frameworkanwendung integriert, ausschließlich Protokolladapter.

Composer audit --locked --no-dev --format=json am13.11.2026: keine gemeldeten Advisories/aufgegebenen Pakete. Das ist ein zeitpunktbezogener Metadatentest, keine Sicherheitsgarantie. Debuglogger abgeschaltet, TLS/Peerprüfung verpflichtend, Grenzen um Socket-/Nachrichten-/Ordner-/UID-Antworten. Bestehender GSZ-MIMEparser und bestehende Mailtabellen wiederverwendet. Keine zusätzlichen laufenden Softwarekosten.

| Paket | Version | Lizenz |
|---|---|---|
| carbonphp/carbon-doctrine-types | 3.2.1 | MIT |
| directorytree/imapengine | v1.25.9 | MIT |
| doctrine/lexer | 3.0.3 | MIT |
| egulias/email-validator | 4.0.4 | MIT |
| guzzlehttp/psr7 | 3.4.0 | MIT |
| illuminate/collections | v12.72.0 | MIT |
| illuminate/conditionable | v12.72.0 | MIT |
| illuminate/contracts | v12.72.0 | MIT |
| illuminate/macroable | v12.72.0 | MIT |
| laravel/serializable-closure | v2.2.0 | MIT |
| nesbot/carbon | 3.15.1 | MIT |
| php-di/invoker | 2.3.7 | MIT |
| php-di/php-di | 7.1.1 | MIT |
| psr/clock | 1.0.0 | MIT |
| psr/container | 2.0.2 | MIT |
| psr/http-factory | 1.1.0 | MIT |
| psr/http-message | 2.0 | MIT |
| psr/log | 3.0.2 | MIT |
| psr/simple-cache | 3.0.0 | MIT |
| symfony/clock | v7.4.21 | MIT |
| symfony/deprecation-contracts | v3.8.1 | MIT |
| symfony/mime | v7.4.22 | MIT |
| symfony/polyfill-iconv | v1.43.0 | MIT |
| symfony/polyfill-intl-idn | v1.43.0 | MIT |
| symfony/polyfill-intl-normalizer | v1.43.0 | MIT |
| symfony/polyfill-mbstring | v1.43.3 | MIT |
| symfony/polyfill-php80 | v1.43.0 | MIT |
| symfony/polyfill-php82 | v1.43.0 | MIT |
| symfony/polyfill-php83 | v1.43.0 | MIT |
| symfony/polyfill-php84 | v1.43.0 | MIT |
| symfony/polyfill-php85 | v1.43.0 | MIT |
| symfony/polyfill-time | v1.42.0 | MIT |
| symfony/translation | v7.4.23 | MIT |
| symfony/translation-contracts | v3.8.1 | MIT |
| zbateson/mail-mime-parser | 4.0.9 | BSD-2-Clause |
| zbateson/mb-wrapper | 3.0.1 | BSD-2-Clause |
| zbateson/stream-decorators | 3.0.2 | BSD-2-Clause |
