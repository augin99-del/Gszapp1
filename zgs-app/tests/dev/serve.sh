#!/usr/bin/env bash
# Nur lokale QA: startet PHP-Testserver gegen eine lokale gsz_test_*-Datenbank.
# Aufruf: tests/dev/serve.sh <datenbank> <port>
set -eu
cd "$(dirname "$0")/../.."
DB="$1"; PORT="${2:-8460}"; SOCK="${GSZ_TEST_SOCKET:-/run/mysqld/mysqld.sock}"
case "$DB" in gsz_test_*) ;; *) echo "Nur gsz_test_*-Datenbanken"; exit 1;; esac
SESS=$(mktemp -d)
exec env GSZ_DB_SOCKET="$SOCK" GSZ_DB_NAME="$DB" GSZ_DB_USER=root GSZ_DB_PASS= GSZ_DB_HOST=localhost \
  php -d session.save_path="$SESS" -d display_errors=stderr -S 127.0.0.1:"$PORT" -t public tests/dev/router.php
