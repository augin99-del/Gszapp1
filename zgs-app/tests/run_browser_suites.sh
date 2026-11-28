#!/usr/bin/env bash
# Lokaler QA-Runner für Playwright-Suiten: je Suite neue gsz_test_*-Datenbank + Fixture, PHP-Testserver,
# HTTPS-Loopback-Proxy (selbstsigniert). Nur synthetische Daten. Aufruf: tests/run_browser_suites.sh [suite …]
set -u
cd "$(dirname "$0")/.."
SOCK="${GSZ_TEST_SOCKET:-/run/mysqld/mysqld.sock}"; CERT="${GSZ_TEST_CERT:-/tmp/claude-0/tls/cert.pem}"; KEY="${GSZ_TEST_KEY:-/tmp/claude-0/tls/key.pem}"
declare -A FIX=(
 [test_zentrale_ui_browser]=tests/dev/screens_fixture.php [test_zentrale_flows_browser]=tests/dev/screens_fixture.php [test_mail_ui_browser]=tests/dev/screens_fixture.php [test_waste_ui_browser]=tests/dev/screens_fixture.php [test_object_rules_browser]=tests/dev/screens_fixture.php [test_gsc_browser]=tests/dev/screens_fixture.php [test_notes_browser]=tests/dev/screens_fixture.php [test_stays_browser]=tests/dev/screens_fixture.php [test_projects_browser]=tests/dev/screens_fixture.php [test_leads_browser]=tests/dev/screens_fixture.php [test_service_browser]=tests/dev/screens_fixture.php [test_push_browser]=tests/dev/screens_fixture.php [test_estate_portfolio_browser]=tests/dev/screens_fixture.php [test_contracts_browser]=tests/dev/screens_fixture.php [test_offers_keys_browser]=tests/dev/screens_fixture.php
 [test_browser]=tests/prepare_browser.php [test_estate_browser]=tests/prepare_estate_browser.php [test_estate_settlement_browser]=tests/prepare_estate_settlement_browser.php
 [test_estate_export_browser]=tests/prepare_estate_settlement_browser.php [test_waste_connections_browser]=tests/prepare_waste_connections_browser.php
)
suites=("$@"); [ ${#suites[@]} -eq 0 ] && suites=($(ls tests/*_browser.cjs | xargs -n1 basename | sed 's/\.cjs$//'))
pass=0; fail=0; failed=(); port=8700
for s in "${suites[@]}"; do
  if [ "$s" = test_setup_browser ]; then for src in "" 320 370; do out=$(tests/dev/run_setup_browser.sh $src 2>&1); if [ $? -eq 0 ]; then pass=$((pass+1)); echo "PASS test_setup_browser[${src:-leer}] :: $(echo "$out" | tail -1 | cut -c1-160)"; else fail=$((fail+1)); failed+=("test_setup_browser[$src]"); echo "FAIL test_setup_browser[${src:-leer}] :: $(echo "$out" | grep -v '^ *at ' | tail -2 | tr '\n' ' ' | cut -c1-300)"; fi; done; continue; fi
  port=$((port+2)); fixture=${FIX[$s]:-tests/prepare_gesamt_browser.php}
  pre=gsz_test_; [ "$fixture" = tests/prepare_waste_connections_browser.php ] && pre=gsz_test_wastebrowser_
  db="${pre}b$(date +%s%N | tail -c 8)"; mysql -uroot -S "$SOCK" -e "CREATE DATABASE \`$db\` CHARACTER SET utf8mb4" || exit 2
  dsn="mysql:unix_socket=$SOCK;dbname=$db"
  if [ "$fixture" = tests/dev/screens_fixture.php ]; then fout=$(GSZ_TEST_DSN="$dsn" php "$fixture" "$PWD" 2>&1); else fout=$(GSZ_TEST_DSN="$dsn" php "$fixture" 2>&1); fi
  sess=$(mktemp -d); qpass="qa$(head -c 12 /dev/urandom | od -An -tx1 | tr -d ' \n')"
  mysql -uroot -S "$SOCK" -e "CREATE OR REPLACE USER 'gszqa'@'localhost' IDENTIFIED BY '$qpass'; GRANT ALL ON \`$db\`.* TO 'gszqa'@'localhost';"
  env GSZ_MAIL_FROM=owner@example.invalid GSZ_DEV_STRICT_HTTPS=1 GSZ_DB_SOCKET="$SOCK" GSZ_DB_NAME="$db" GSZ_DB_USER=gszqa GSZ_DB_PASS="$qpass" GSZ_DB_HOST=localhost php -d session.save_path="$sess" -d display_errors=stderr -S 127.0.0.1:$port -t public tests/dev/router.php >/tmp/claude-0/bsrv-$s.log 2>&1 &
  srv=$!; node tests/dev/tls_proxy.cjs $((port+1)) $port "$CERT" "$KEY" & prx=$!; sleep 0.8
  if [ "$s" = test_v1_calendar_browser ]; then GSZ_TEST_URL="https://127.0.0.1:$((port+1))" GSZ_TEST_PHP=php timeout 300 node tests/test_golive_browser.cjs >/dev/null 2>&1; fi
  if [ "$s" = test_estate_export_browser ]; then GSZ_TEST_URL="https://127.0.0.1:$((port+1))" timeout 300 node tests/test_estate_settlement_browser.cjs >/dev/null 2>&1; fi
  out=$(GSZ_TEST_HTTP_URL="http://127.0.0.1:$port" GSZ_TEST_URL="https://127.0.0.1:$((port+1))" GSZ_TEST_DSN="$dsn" GSZ_TEST_PHP=php timeout 300 node "tests/$s.cjs" 2>&1); rc=$?
  rm -f private/backups/gsz-sicherung-* 2>/dev/null; rm -rf private/secrets private/server-config 2>/dev/null; kill $srv $prx 2>/dev/null; wait $srv $prx 2>/dev/null; mysql -uroot -S "$SOCK" -e "DROP DATABASE IF EXISTS \`$db\`"
  if [ $rc -eq 0 ]; then pass=$((pass+1)); echo "PASS $s :: $(echo "$out" | tail -1 | cut -c1-160)"; else fail=$((fail+1)); failed+=("$s"); echo "FAIL $s :: $(echo "$out" | grep -E "Error|locator|waiting for|Call log" | head -12 | tr "\n" " " | cut -c1-1500)"; fi
done
echo "SUMMARY pass=$pass fail=$fail ${failed[*]:-}"; [ $fail -eq 0 ]
