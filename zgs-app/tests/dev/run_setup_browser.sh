#!/usr/bin/env bash
# Lokale QA für tests/test_setup_browser.cjs: isolierte App-Kopie unter /workspace/gsz-tools, absichtlich defekte
# config.php, TCP-MariaDB 127.0.0.1:33307 mit eigenem Benutzer, HTTPS-Loopback. Nur synthetische Daten.
# Aufruf: tests/dev/run_setup_browser.sh [quellschema]   (leer = leere Datenbank, z. B. 320 = Altbestand)
set -eu
APP="$(cd "$(dirname "$0")/../.." && pwd)"; SRC="${1:-}"; ROOT=/workspace/gsz-tools/setup-e2e-$$; CERT=/tmp/claude-0/tls/cert.pem; KEY=/tmp/claude-0/tls/key.pem
mkdir -p "$ROOT"; cp -a "$APP/public" "$APP/private" "$ROOT/"; rm -rf "$ROOT/private/uploads" "$ROOT/private/server-config" "$ROOT/private/secrets" "$ROOT/private/backups" "$ROOT/private/runtime-setup"
printf '<?php\nreturn [ "db_host" => \n' > "$ROOT/private/config.php"; chmod 600 "$ROOT/private/config.php"
DB=gsz_test_setup_e2e$$; PASS="Synthetic-$(head -c 9 /dev/urandom | od -An -tx1 | tr -d ' \n')"
mysql -uroot -e "CREATE DATABASE $DB CHARACTER SET utf8mb4; CREATE OR REPLACE USER 'gszsetup'@'127.0.0.1' IDENTIFIED BY '$PASS'; GRANT ALL ON $DB.* TO 'gszsetup'@'127.0.0.1';"
SHA=""
if [ -n "$SRC" ]; then
  GSZ_TEST_DSN="mysql:unix_socket=/run/mysqld/mysqld.sock;dbname=$DB" php -r '
   $p=new PDO(getenv("GSZ_TEST_DSN"),"root","",[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);$r=$argv[1];$src=(int)$argv[2];
   $p->exec(file_get_contents($r."/private/fresh_install_v192.sql"));foreach([200,210,220,230,240,250,260,270,280,290,300,310,320,330,340,350,360,370] as $v)if($v<=$src)$p->exec(file_get_contents($r."/private/migration_v".$v.".sql"));
   $p->prepare("INSERT INTO admins(email,password_hash) VALUES(?,?)")->execute(["owner@example.invalid",password_hash("Synthetic-Setup-E2E-Unique-2026!",PASSWORD_DEFAULT)]);' "$APP" "$SRC"
  SHA=$(mysqldump -uroot "$DB" | sha256sum | cut -c1-64)
fi
FIX=$ROOT/fixture.json; printf '{"db":"%s","user":"gszsetup","password":"%s","source_schema":%s,"backup_sha256":"%s"}' "$DB" "$PASS" "${SRC:-null}" "${SHA:-$(printf x | sha256sum | cut -c1-64)}" > "$FIX"; chmod 600 "$FIX"
[ -z "$SRC" ] && sed -i 's/"source_schema":null/"source_schema":0/' "$FIX"
PORT=$((9000+($$%400)*2)); while ss -ltn 2>/dev/null | grep -q ":$PORT \|:$((PORT+1)) "; do PORT=$((PORT+2)); done; sess=$(mktemp -d)
(cd "$ROOT" && env GSZ_DEV_STRICT_HTTPS=1 php -d session.save_path="$sess" -S 127.0.0.1:$PORT -t public "$APP/tests/dev/router.php" >/tmp/claude-0/setup-srv.log 2>&1) & srv=$!
node "$APP/tests/dev/tls_proxy.cjs" $((PORT+1)) $PORT "$CERT" "$KEY" & prx=$!; sleep 1
set +e; GSZ_TEST_URL="https://127.0.0.1:$((PORT+1))" GSZ_TEST_SETUP_ROOT="$ROOT" GSZ_TEST_SETUP_FIXTURE="$FIX" node "$APP/tests/test_setup_browser.cjs"; rc=$?
kill $srv $prx 2>/dev/null; mysql -uroot -e "DROP DATABASE IF EXISTS $DB; DROP USER IF EXISTS 'gszsetup'@'127.0.0.1';"; rm -rf "$ROOT"; exit $rc
