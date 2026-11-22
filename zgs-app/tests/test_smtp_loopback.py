"""Native PHP SMTP/TLS test on loopback only. Python/OpenSSL are LOCAL QA tools.
No app credentials loaded, no forwarding/delivery, no persisted message or key.
Requires free loopback port 587 because the production adapter deliberately fixes it.
"""
import base64, json, os, pathlib, socket, ssl, subprocess, tempfile, threading
ROOT = pathlib.Path(__file__).resolve().parent
PHP = os.environ.get('GSZ_TEST_PHP', 'php')

def run(mode, cert, key, transport, implicit=False):
    state = {'auth': False, 'tls': False, 'data': False, 'dot': False}
    listener = socket.socket(); listener.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
    listener.bind(('127.0.0.1', 465 if implicit else 587)); listener.listen(1); listener.settimeout(15)
    def server():
        conn = None
        try:
            conn, _ = listener.accept(); conn.settimeout(15)
            def write(b): conn.sendall(b)
            def line():
                b = b''
                while not b.endswith(b'\n'):
                    v = conn.recv(1)
                    if not v: raise EOFError()
                    b += v
                    if len(b) > 8192: raise RuntimeError('Synthetic line bound')
                return b
            context = ssl.SSLContext(ssl.PROTOCOL_TLS_SERVER); context.load_cert_chain(cert, key)
            if implicit:
                conn = context.wrap_socket(conn, server_side=True); state['tls'] = True
                write(b'220 loopback synthetic SMTP\r\n'); assert line().startswith(b'EHLO ')
            else:
                write(b'220 loopback synthetic SMTP\r\n'); assert line().startswith(b'EHLO ')
                if mode == 'no_starttls':
                    write(b'250 AUTH PLAIN\r\n'); return
                write(b'250-loopback\r\n250 STARTTLS\r\n'); assert line() == b'STARTTLS\r\n'; write(b'220 TLS now\r\n')
                conn = context.wrap_socket(conn, server_side=True); state['tls'] = True
                assert line().startswith(b'EHLO ')
            write(b'250-loopback\r\n250 AUTH PLAIN\r\n')
            auth = line()
            if auth == b'AUTH PLAIN\r\n':
                write(b'334 \r\n'); encoded = line().strip()
            else:
                assert auth.startswith(b'AUTH PLAIN '); encoded = auth.split()[2]
            state['auth'] = True
            assert base64.b64decode(encoded) == b'\0synthetic-test-user\0synthetic-mock-only'
            if mode == 'auth_reject': write(b'535 refused\r\n'); return
            write(b'235 accepted\r\n'); assert line().startswith(b'MAIL FROM:<owner@example.invalid>'); write(b'250 sender\r\n')
            assert line().startswith(b'RCPT TO:<customer@example.invalid>')
            if mode == 'rcpt_reject': write(b'550 refused\r\n'); return
            write(b'250 recipient\r\n'); assert line() == b'DATA\r\n'
            if mode == 'data_reject': write(b'554 refused\r\n'); return
            write(b'354 content\r\n')
            for _ in range(100):
                b = line()
                if b == b'.\r\n': break
                if b == b'..line\r\n': state['dot'] = True
            else: raise RuntimeError('Synthetic content bound')
            state['data'] = True
            if mode == 'lost_ack': return
            write(b'250 stored in memory only\r\n')
            assert line() == b'QUIT\r\n'
            if mode != 'quit_close': write(b'221 bye\r\n')
        except (ssl.SSLError, EOFError, BrokenPipeError, ConnectionResetError):
            if mode != 'untrusted_tls': state['server_failure'] = True
        except Exception as ex: state['server_failure'] = type(ex).__name__
        finally:
            if conn: conn.close()
            listener.close()
    thread = threading.Thread(target=server, daemon=True); thread.start()
    cmd = [PHP]
    if mode != 'untrusted_tls': cmd += ['-d', 'openssl.cafile='+str(cert)]
    env = dict(os.environ, GSZ_SYNTHETIC_SMTP='1', GSZ_SYNTHETIC_SMTP_TRANSPORT=transport, GSZ_SYNTHETIC_SMTP_PORT='465' if implicit else '587')
    result = subprocess.run(cmd+[str(ROOT/'test_smtp_transport.php')], env=env, capture_output=True, text=True, timeout=25, check=True)
    thread.join(20); assert not thread.is_alive(), 'Loopback server did not finish'
    reply = json.loads(result.stdout)
    expected = {'accepted': True} if mode in ('success', 'quit_close') else {'accepted': False, 'uncertain': mode == 'lost_ack'}
    assert reply == expected, (transport, mode, reply, expected, state)
    assert not state.get('server_failure'), (mode, state)
    if mode in ('no_starttls', 'untrusted_tls'): assert not state['auth'], 'Credentials before validated TLS'
    if mode in ('success', 'quit_close', 'lost_ack'): assert state['tls'] and state['data'] and state['dot']

with tempfile.TemporaryDirectory(prefix='gsz-synthetic-tls-') as directory:
    cert = pathlib.Path(directory)/'cert.pem'; key = pathlib.Path(directory)/'key.pem'
    subprocess.run(['openssl', 'req', '-x509', '-newkey', 'rsa:2048', '-nodes', '-days', '1', '-subj', '/CN=127.0.0.1', '-addext', 'subjectAltName=IP:127.0.0.1', '-keyout', str(key), '-out', str(cert)], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL, check=True)
    os.chmod(key, 0o600)
    modes = ['success', 'no_starttls', 'auth_reject', 'rcpt_reject', 'data_reject', 'lost_ack', 'quit_close', 'untrusted_tls']
    for transport in ['native', 'phpmailer']:
        for mode in modes: run(mode, cert, key, transport)
    for mode in modes:
        if mode != 'no_starttls': run(mode, cert, key, 'phpmailer', True)
    print('SMTP loopback PASS (23 scenarios: native/PHPMailer587 + PHPMailer465; no external delivery).')
