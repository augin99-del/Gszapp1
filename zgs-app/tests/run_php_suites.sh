#!/usr/bin/env bash
# Lokaler QA-Runner: führt alle PHP-Suiten aus. DB-Suiten erhalten je eine NEUE leere
# gsz_test_*-Datenbank auf einer lokalen Test-MariaDB (Unix-Socket, root ohne Passwort).
# Niemals gegen eine produktive Datenbank verwenden. Keine Anwendungskonfiguration nötig.
set -u
cd "$(dirname "$0")/.."
SOCK="${GSZ_TEST_SOCKET:-/run/mysqld/mysqld.sock}"
PHP="${GSZ_TEST_PHP:-php}"
pass=0; fail=0; failed=()
for f in tests/test_*.php; do
  name=$(basename "$f" .php)
  case "$name" in test_smtp_transport|test_setup_mail_loopback|test_final_optional_dom|test_setup_schema|test_final_upgrade) continue;; esac
  prefix=$(grep -oE "dbname=\(?gsz_test_[a-z0-9]*_?\[" "$f" | head -1 | sed -E 's/dbname=\(?//; s/\[$//')
  env=()
  if [ -n "$prefix" ]; then
    db="${prefix}r$(date +%s%N | tail -c 9)"
    [ "${prefix: -1}" = "_" ] || db="${prefix}_r$(date +%s%N | tail -c 9)"
    mysql -uroot -S "$SOCK" -e "CREATE DATABASE \`$db\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci" || exit 2
    env=(GSZ_TEST_DSN="mysql:unix_socket=$SOCK;dbname=$db")
  fi
  out=$(env "${env[@]}" GSZ_TEST_PHP="$PHP" ${GSZ_TEST_SOURCE_SCHEMA:+GSZ_TEST_SOURCE_SCHEMA=$GSZ_TEST_SOURCE_SCHEMA} timeout 300 "$PHP" "$f" 2>&1); rc=$?
  [ -n "$prefix" ] && mysql -uroot -S "$SOCK" -e "DROP DATABASE IF EXISTS \`$db\`"
  if [ $rc -eq 0 ]; then pass=$((pass+1)); echo "PASS $name :: $(echo "$out" | tail -1 | cut -c1-160)";
  else fail=$((fail+1)); failed+=("$name"); echo "FAIL $name :: $(echo "$out" | tail -3 | tr '\n' ' ' | cut -c1-400)"; fi
done
# Quellstand-Matrix für Schemaerkennung und geschütztes Update
for src in 0 192 220 320 340 360 370; do
  for suite in test_setup_schema:gsz_test_setup_ test_final_upgrade:gsz_test_final_; do
    name=${suite%%:*}; pre=${suite##*:}
    [ "$name" = test_final_upgrade ] && case $src in 340|360|370) ;; *) continue;; esac
    db="${pre}m${src}_$(date +%s%N | tail -c 7)"; mysql -uroot -S "$SOCK" -e "CREATE DATABASE \`$db\` CHARACTER SET utf8mb4" || exit 2
    out=$(GSZ_TEST_SOURCE_SCHEMA=$src GSZ_TEST_DSN="mysql:unix_socket=$SOCK;dbname=$db" timeout 300 "$PHP" "tests/$name.php" 2>&1); rc=$?
    mysql -uroot -S "$SOCK" -e "DROP DATABASE IF EXISTS \`$db\`"
    if [ $rc -eq 0 ]; then pass=$((pass+1)); echo "PASS $name[$src] :: $(echo "$out" | tail -1 | cut -c1-160)"; else fail=$((fail+1)); failed+=("$name[$src]"); echo "FAIL $name[$src] :: $(echo "$out" | tail -3 | tr '\n' ' ' | cut -c1-400)"; fi
  done
done
echo "SUMMARY pass=$pass fail=$fail ${failed[*]:-}"
[ $fail -eq 0 ]
