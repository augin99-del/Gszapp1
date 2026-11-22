/* GSZ-Zentrale: Abhaken ohne Neuladen. Ohne JavaScript funktioniert alles wie bisher (normales Formular).
   Der Server prüft wie immer (Anmeldung, CSRF, Bearbeitungsstand); bei jeder Unklarheit wird das Formular normal gesendet. */
(function(){
  'use strict';
  var toastTimer=null;
  function toast(text,undo){
    var t=document.getElementById('gsz-toast');
    if(!t){t=document.createElement('div');t.id='gsz-toast';t.className='toast';t.setAttribute('role','status');t.setAttribute('aria-live','polite');document.body.appendChild(t);}
    t.textContent='';var span=document.createElement('span');span.textContent=text;t.appendChild(span);
    if(undo){var b=document.createElement('button');b.type='button';b.textContent='Rückgängig';b.addEventListener('click',function(){hide();undo();});t.appendChild(b);}
    t.classList.add('show');clearTimeout(toastTimer);toastTimer=setTimeout(hide,undo?6000:4000);
    function hide(){t.classList.remove('show');}
  }
  function setState(form,done){
    var block=form.closest('.block');if(block)block.classList.toggle('is-done',done);
    var input=form.querySelector('input[name=done]');if(input)input.value=done?'0':'1';
    var btn=form.querySelector('button.check');
    if(btn){var name=(block&&block.querySelector('.obj'))?block.querySelector('.obj').textContent.trim():'';btn.setAttribute('aria-label',(done?'Wieder öffnen: ':'Erledigt: ')+name);btn.title=done?'Wieder öffnen':'Erledigt';}
    var move=block&&block.querySelector('details.move');if(move){move.hidden=done;move.open=false;}
  }
  function send(form,done,fromUndo){
    var btn=form.querySelector('button.check');if(btn)btn.disabled=true;
    setState(form,done);
    var data=new FormData(form);data.set('done',done?'1':'0');
    return fetch(form.getAttribute('action')||location.href,{method:'POST',body:data,credentials:'same-origin',headers:{'X-GSZ-Fetch':'1','Accept':'application/json'}})
      .then(function(r){var ct=r.headers.get('Content-Type')||'';if(!r.ok||ct.indexOf('application/json')<0)throw new Error('fallback');return r.json();})
      .then(function(j){
        if(btn)btn.disabled=false;
        if(!j.ok){setState(form,!done);toast(j.error||'Das hat nicht geklappt.');return;}
        var rv=form.querySelector('input[name=row_version]');if(rv)rv.value=String(j.row_version);
        setState(form,j.done);
        var block=form.closest('.block');var name=block&&block.querySelector('.obj')?block.querySelector('.obj').textContent.trim():'Aufgabe';
        if(!fromUndo)toast(name+(j.done?' erledigt':' wieder offen'),function(){send(form,!j.done,true);});
      })
      .catch(function(){if(btn)btn.disabled=false;setState(form,!done);form.submit();});
  }
  document.addEventListener('submit',function(ev){
    var form=ev.target;
    if(!(form instanceof HTMLFormElement)||!window.fetch||!window.FormData)return;
    var action=form.querySelector('input[name=action]');
    if(!action||action.value!=='toggle'||!form.querySelector('button.check'))return;
    ev.preventDefault();
    var input=form.querySelector('input[name=done]');
    send(form,input?input.value==='1':true,false);
  });
})();
