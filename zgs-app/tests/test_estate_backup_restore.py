"""Local synthetic DB + private estate-original restore verification; no production DSNs."""
import hashlib
import json
import os
from pathlib import Path
import re
import shutil
import subprocess
import tempfile

socket = os.environ.get('GSZ_TEST_DB_SOCKET', '')
source = os.environ.get('GSZ_TEST_DB_SOURCE', '')
target = os.environ.get('GSZ_TEST_DB_TARGET', '')
if not socket.startswith('/workspace/') or not all(re.fullmatch(r'gsz_test_estate_[a-z0-9_]{1,46}', n) for n in (source, target)) or source == target:
    raise RuntimeError('Only separate local synthetic estate databases are permitted.')
mysql = os.environ.get('GSZ_TEST_MYSQL', 'mariadb')
dumper = os.environ.get('GSZ_TEST_MYSQL_DUMP', 'mariadb-dump')
connection = ['--socket=' + socket, '-u' + os.environ.get('GSZ_TEST_DB_USER', 'root')]

def query(db, sql):
    return subprocess.check_output([mysql, *connection, '--batch', '--skip-column-names', db, '-e', sql]).decode().strip()

def dump(db):
    return subprocess.check_output([dumper, *connection, '--single-transaction', '--skip-comments', '--skip-dump-date', '--order-by-primary', '--hex-blob', db])

originals = Path(__file__).resolve().parents[1] / 'private/uploads/estate'
with tempfile.TemporaryDirectory(prefix='gsz-estate-restore-') as temp:
    copied = Path(temp) / 'estate'
    shutil.copytree(originals, copied)
    before = dump(source)
    subprocess.run([mysql, *connection, '-e', 'CREATE DATABASE ' + target + ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'], check=True)
    subprocess.run([mysql, *connection, target], input=before, check=True)
    after = dump(target)
    if before != after:
        raise AssertionError('Logical dump differs after restoration.')
    rows = query(target, 'SELECT sha256,size_bytes FROM estate_documents UNION ALL SELECT pdf_sha256,pdf_size FROM estate_settlement_documents')
    checked = 0
    for row in rows.splitlines():
        digest, length = row.split('\t')
        if not re.fullmatch('[a-f0-9]{64}', digest):
            raise AssertionError('Unsafe hash in restored metadata.')
        content = (copied / digest).read_bytes()
        if len(content) != int(length) or hashlib.sha256(content).hexdigest() != digest:
            raise AssertionError('Restored original integrity differs.')
        checked += 1
    tables = len(query(target, 'SHOW TABLES').splitlines())
    if not checked:
        raise AssertionError('Populated estate-original fixture required.')
    print(json.dumps({'restore': 'passed', 'tables': tables, 'logical_dump_bytes': len(before), 'logical_dump': 'identical', 'restored_originals_and_pdfs': checked}))
