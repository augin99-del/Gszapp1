"""Local synthetic implicit-TLS IMAP993 server, no accounts, delivery or persisted messages."""
import json,os,pathlib,re,socket,ssl,subprocess,tempfile,threading
ROOT=pathlib.Path(__file__).resolve().parent
PHP=os.environ.get('GSZ_TEST_PHP','php')
def run(mode,cert,key,trust=True):
 state={'auth':False,'body':False};listener=socket.socket();listener.setsockopt(socket.SOL_SOCKET,socket.SO_REUSEADDR,1);listener.bind(('127.0.0.1',993));listener.listen(1);listener.settimeout(15)
 def server():
  conn=None
  try:
   conn,_=listener.accept();conn.settimeout(10);ctx=ssl.SSLContext(ssl.PROTOCOL_TLS_SERVER);ctx.load_cert_chain(cert,key);conn=ctx.wrap_socket(conn,server_side=True);conn.sendall(b'* OK synthetic IMAP server\r\n');f=conn.makefile('rb');status_calls=0
   for _ in range(30):
    raw=f.readline(8192)
    if not raw:break
    line=raw.decode();tag,cmd=line.rstrip('\r\n').split(' ',1)
    def write(s):conn.sendall(s.encode())
    if cmd.startswith('LOGIN '):
     assert cmd=='LOGIN "synthetic@example.invalid" "synthetic-mock-only"';state['auth']=True
     if mode=='auth_reject':write(tag+' NO [AUTHENTICATIONFAILED] rejected\r\n');break
     write(tag+' OK authenticated\r\n')
    elif cmd.startswith('LIST '):write('* LIST (\\HasNoChildren) "/" "INBOX"\r\n* LIST (\\HasNoChildren \\Sent) "/" "Sent"\r\n'+tag+' OK list\r\n')
    elif cmd.startswith('STATUS '):
     status_calls+=1;epoch=901 if mode=='epoch_change' and status_calls>1 else 900;write('* STATUS "INBOX" (UIDVALIDITY '+str(epoch)+')\r\n'+tag+' OK status\r\n')
    elif cmd.startswith('SELECT '):write('* 1 EXISTS\r\n'+tag+' OK select\r\n')
    elif cmd.startswith('UID SEARCH '):write('* SEARCH 3\r\n'+tag+' OK search\r\n')
    elif cmd=='UID FETCH 3 (RFC822.SIZE FLAGS)':write('* 1 FETCH (UID 3 RFC822.SIZE '+str(15728641 if mode=='oversized' else 200)+' FLAGS (\\Seen))\r\n'+tag+' OK size\r\n')
    elif cmd=='UID FETCH 3 (BODY.PEEK[HEADER] BODY.PEEK[TEXT])':
     state['body']=True;head='From: synthetic@example.invalid\r\nSubject: Synthetic local test\r\n';body='Synthetic local body';answer='* 1 FETCH (UID 3 BODY[HEADER] {'+str(len(head))+'}\r\n'+head
     if mode!='incomplete_body':answer+=' BODY[TEXT] {'+str(len(body))+'}\r\n'+body
     write(answer+')\r\n'+tag+' OK body\r\n')
    elif cmd=='LOGOUT':write('* BYE done\r\n'+tag+' OK logout\r\n');break
    else:raise RuntimeError('Unexpected command type')
   if conn:conn.close()
  except (ssl.SSLError,EOFError,BrokenPipeError,ConnectionResetError):
   if mode not in ('untrusted_tls','wrong_hostname'):state['failure']='TLS/EOF'
  except Exception as ex:state['failure']=type(ex).__name__
  finally:
   if conn:conn.close()
   listener.close()
 thread=threading.Thread(target=server,daemon=True);thread.start();cmd=[PHP]
 if trust:cmd+=['-d','openssl.cafile='+str(cert)]
 result=subprocess.run(cmd+[str(ROOT/'test_setup_mail_loopback.php')],env=os.environ|{'GSZ_SYNTHETIC_IMAP':'1'},capture_output=True,text=True,timeout=25);thread.join(15);assert not thread.is_alive(),'server stuck';assert result.returncode==0,result.stderr
 reply=json.loads(result.stdout);expected={'accepted':True,'folders':2,'validity':900,'messages':1} if mode=='success' else {'accepted':False,'safe':True};assert reply==expected,(mode,reply,state);assert not state.get('failure'),(mode,state)
 if mode in ('untrusted_tls','wrong_hostname'):assert not state['auth'],'credentials sent before verified TLS'
 if mode=='oversized':assert not state['body'],'oversized message body downloaded'
with tempfile.TemporaryDirectory(prefix='gsz-mail-setup-tls-') as tmp:
 root=pathlib.Path(tmp)
 for name,host in [('valid','localhost'),('wrong','wrong.example.invalid')]:
  cert=root/(name+'.pem');key=root/(name+'.key');subprocess.run(['openssl','req','-x509','-newkey','rsa:2048','-nodes','-days','1','-subj','/CN='+host,'-addext','subjectAltName=DNS:'+host,'-keyout',str(key),'-out',str(cert)],stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL,check=True);key.chmod(0o600)
 cert=root/'valid.pem';key=root/'valid.key'
 for mode in ['success','auth_reject','oversized','incomplete_body','epoch_change']:run(mode,cert,key)
 run('untrusted_tls',cert,key,False);run('wrong_hostname',root/'wrong.pem',root/'wrong.key')
 print('IMAP fallback TLS loopback PASS (7 scenarios; certificate/hostname/auth/size/identity; no external mailbox).')
