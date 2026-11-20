"""Local synthetic full DB/private-uploads restore; refuses non-test schemas or remote DBs."""
import hashlib
import json
import os
from pathlib import Path
import re
import shutil
import subprocess
import tempfile

socket=os.environ.get('GSZ_TEST_DB_SOCKET','')
source=os.environ.get('GSZ_TEST_DB_SOURCE','')
target=os.environ.get('GSZ_TEST_DB_TARGET','')
if not socket.startswith('/workspace/') or source==target or not all(re.fullmatch(r'gsz_test_golive_[a-z0-9_]{1,44}',x) for x in [source,target]):
    raise RuntimeError('Separate local synthetic go-live schemas required.')
mysql=os.environ.get('GSZ_TEST_MYSQL','mariadb')
dumper=os.environ.get('GSZ_TEST_MYSQL_DUMP','mariadb-dump')
connection=['--socket='+socket,'-u'+os.environ.get('GSZ_TEST_DB_USER','root')]
root=Path(__file__).resolve().parents[1]
originals=root/'private/uploads'
def hashes(directory):
    result={}
    for p in directory.rglob('*'):
        if p.is_symlink():raise RuntimeError('Symlink in private originals.')
        if p.is_file():result[p.relative_to(directory).as_posix()]=hashlib.sha256(p.read_bytes()).hexdigest()
    return result
def dump(database):
    return subprocess.check_output([dumper,*connection,'--single-transaction','--skip-comments','--skip-dump-date','--order-by-primary','--hex-blob',database])
def query(sql):
    return subprocess.check_output([mysql,*connection,'--batch','--skip-column-names',target,'-e',sql]).decode().strip()
with tempfile.TemporaryDirectory(prefix='gsz-golive-restore-') as temp:
    copied=Path(temp)/'uploads'
    before_files=hashes(originals)
    shutil.copytree(originals,copied)
    before=dump(source)
    subprocess.run([mysql,*connection,'-e','CREATE DATABASE '+target+' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'],check=True)
    subprocess.run([mysql,*connection,target],input=before,check=True)
    if before!=dump(target):raise AssertionError('Restored logical DB differs.')
    if before_files!=hashes(copied):raise AssertionError('Restored private files differ.')
    documents=0
    for row in query('SELECT stored_name,sha256,size_bytes FROM documents').splitlines():
        stored,digest,size=row.split('\t')
        if not re.fullmatch('[a-f0-9]{32}',stored) or not re.fullmatch('[a-f0-9]{64}',digest):raise AssertionError('Invalid document identity.')
        content=(copied/'documents'/stored).read_bytes()
        if len(content)!=int(size) or hashlib.sha256(content).hexdigest()!=digest:raise AssertionError('Restored document linkage differs.')
        documents+=1
    if not documents:raise AssertionError('Fixture needs actual archived document.')
    if query("SELECT COUNT(*) FROM tasks WHERE title='Praxistest Reinigung' AND done=1")!='1':raise AssertionError('Completed daily task not preserved.')
    if query("SELECT COUNT(*) FROM tasks WHERE title='Praxistest verschieben' AND plan_locked=1 AND notes='Nach Browserneustart erhalten'")!='1':raise AssertionError('Manual move not preserved.')
    print(json.dumps({'restore':'passed','tables':len(query('SHOW TABLES').splitlines()),'logical_dump_bytes':len(before),'logical_dump':'identical','private_files':len(before_files),'verified_document_links':documents,'daily_task_state':'preserved'}))
