/* GSZ-Zentrale: Abhaken ohne Neuladen, „Rechnung merken?“ nach erledigter Einzelarbeit und „+ Schnell notieren“.
   Ohne JavaScript funktioniert alles wie bisher (normale Formulare, „+“ führt zur Merkliste).
   Der Server prüft wie immer (Anmeldung, CSRF, Bearbeitungsstand); bei jeder Unklarheit wird das Formular normal gesendet. */
(function(){
  'use strict';
  var TOGGLES={toggle:1,note_done:1,material_done:1};
  var toastTimer=null;
  function toast(text,actions){
    var t=document.getElementById('gsz-toast');
    if(!t){t=document.createElement('div');t.id='gsz-toast';t.className='toast';t.setAttribute('role','status');t.setAttribute('aria-live','polite');document.body.appendChild(t);}
    t.textContent='';var span=document.createElement('span');span.textContent=text;t.appendChild(span);
    (actions||[]).forEach(function(a){var b=document.createElement('button');b.type='button';b.textContent=a.label;b.addEventListener('click',function(){hide();a.run();});t.appendChild(b);});
    t.classList.toggle('two',!!(actions&&actions.length>1));t.classList.add('show');clearTimeout(toastTimer);toastTimer=setTimeout(hide,actions&&actions.length?(actions.length>1?9000:6000):4000);
    function hide(){t.classList.remove('show');}
  }
  function post(url,data){
    return fetch(url,{method:'POST',body:data,credentials:'same-origin',headers:{'X-GSZ-Fetch':'1','Accept':'application/json'}})
      .then(function(r){var ct=r.headers.get('Content-Type')||'';if(!r.ok||ct.indexOf('application/json')<0)throw new Error('fallback');return r.json();});
  }
  function itemOf(form){return form.closest('.take-item')||form.closest('.block');}
  function itemName(form){var item=itemOf(form);var o=item&&(item.querySelector('.take-title')||item.querySelector('.obj'));return o?o.textContent.trim():'';}
  function setState(form,done){
    var block=itemOf(form);if(block)block.classList.toggle('is-done',done);
    var input=form.querySelector('input[name=done]');if(input)input.value=done?'0':'1';
    var btn=form.querySelector('button.check');var kind=form.querySelector('input[name=action]').value;
    if(btn){var invoice=block&&block.classList.contains('is-invoice');
      var label=kind==='material_done'?(done?'Doch noch nicht besorgt':'Besorgt'):invoice?(done?'Wieder offen':'Rechnung geschrieben'):(done?'Wieder öffnen':'Erledigt');
      btn.setAttribute('aria-label',label+': '+itemName(form));btn.title=label;}
    var move=block&&block.querySelector('details.move');if(move){move.hidden=done;move.open=false;}
  }
  function doneText(kind,block,done){
    if(kind==='material_done')return done?' besorgt':' wieder auf der Einkaufsliste';
    if(block&&block.classList.contains('is-invoice'))return done?': Rechnung geschrieben':' wieder offen';
    return done?' erledigt':' wieder offen';
  }
  function rememberInvoice(form,offer){
    var data=new FormData();data.set('csrf',form.querySelector('input[name=csrf]').value);data.set('action','invoice_from');data.set('source',offer.source);data.set('id',String(offer.id));
    post('notes.php',data).then(function(j){toast(j.ok?j.message:(j.error||'Das hat nicht geklappt.'));}).catch(function(){toast('Das hat nicht geklappt. Bitte unter Merkliste eintragen.');});
  }
  function send(form,done,fromUndo){
    var btn=form.querySelector('button.check');if(btn)btn.disabled=true;
    var kind=form.querySelector('input[name=action]').value;
    setState(form,done);
    var data=new FormData(form);data.set('done',done?'1':'0');
    return post(form.getAttribute('action')||location.href,data)
      .then(function(j){
        if(btn)btn.disabled=false;
        if(!j.ok){setState(form,!done);toast(j.error||'Das hat nicht geklappt.');return;}
        var rv=form.querySelector('input[name=row_version]');if(rv)rv.value=String(j.row_version);
        setState(form,j.done);
        if(fromUndo)return;
        var block=itemOf(form);var actions=[{label:'Rückgängig',run:function(){send(form,!j.done,true);}}];
        if(j.done&&j.invoice)actions.unshift({label:'Rechnung merken',run:function(){rememberInvoice(form,j.invoice);}});
        toast((itemName(form)||'Aufgabe')+doneText(kind,block,j.done)+(j.done&&j.invoice?'. Rechnung nötig?':''),actions);
      })
      .catch(function(){if(btn)btn.disabled=false;setState(form,!done);form.submit();});
  }
  document.addEventListener('submit',function(ev){
    var form=ev.target;
    if(!(form instanceof HTMLFormElement)||!window.fetch||!window.FormData)return;
    var action=form.querySelector('input[name=action]');
    if(!action||!TOGGLES[action.value]||!form.querySelector('button.check'))return;
    ev.preventDefault();
    var input=form.querySelector('input[name=done]');
    send(form,input?input.value==='1':true,false);
  });
  // „+ Schnell notieren“ als Dialog
  document.addEventListener('click',function(ev){
    var open=ev.target.closest&&ev.target.closest('[data-quick-note]');var dlg=document.getElementById('quick-note');
    if(open&&dlg&&typeof dlg.showModal==='function'){ev.preventDefault();var body=dlg.querySelector('.quick-body'),tpl=document.getElementById('quick-note-form');if(body&&tpl&&!body.firstElementChild)body.appendChild(tpl.content.cloneNode(true));dlg.showModal();var f=dlg.querySelector('input[name=title]');if(f)f.focus();return;}
    if(ev.target.closest&&ev.target.closest('[data-close]')&&dlg){dlg.close();return;}
    if(dlg&&ev.target===dlg)dlg.close();
  });
  // Datum gewählt → „Wann“-Knöpfe abwählen; Knopf gewählt → Datum leeren
  document.addEventListener('change',function(ev){
    var el=ev.target;var fs=el.closest&&el.closest('fieldset.when');if(!fs)return;
    if(el.name==='due_date'&&el.value){fs.querySelectorAll('input[name=due]').forEach(function(r){r.checked=false;});}
    if(el.name==='due'){var d=fs.querySelector('input[name=due_date]');if(d)d.value='';}
  });
})();
