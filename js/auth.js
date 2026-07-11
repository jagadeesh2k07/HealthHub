'use strict';

const $ = id => document.getElementById(id);

function setErr(grp, errId, msg) {
  const g=$(grp),e=$(errId);
  if(!g||!e)return;
  g.classList.add('has-error');g.classList.remove('has-ok');e.textContent=msg;
}
function setOk(grp,errId){
  const g=$(grp),e=$(errId);
  if(!g||!e)return;
  g.classList.remove('has-error');g.classList.add('has-ok');e.textContent='';
}
function clr(grp,errId){
  const g=$(grp),e=$(errId);
  if(!g||!e)return;
  g.classList.remove('has-error','has-ok');e.textContent='';
}
function showToast(id,msg,type='ok'){
  const t=$(id);if(!t)return;
  t.textContent=msg;t.className=`toast ${type}`;t.classList.remove('hidden');
  setTimeout(()=>t.classList.add('hidden'),4000);
}
function setLoad(b,t,l,on){
  const B=$(b),T=$(t),L=$(l);if(!B||!T||!L)return;
  B.disabled=on;T.classList.toggle('hidden',on);L.classList.toggle('hidden',!on);
}
const validEmail=v=>/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v.trim());
const validPhone=v=>/^[+]?[\d\s\-()]{7,15}$/.test(v.trim());

function toggleEye(inputId,iconId){
  const inp=$(inputId),icon=$(iconId);if(!inp||!icon)return;
  const show=inp.type==='password';
  inp.type=show?'text':'password';
  icon.className=show?'fas fa-eye-slash':'fas fa-eye';
}

function getRules(pw){
  return{len:pw.length>=8,upper:/[A-Z]/.test(pw),num:/[0-9]/.test(pw),sym:/[^A-Za-z0-9]/.test(pw)};
}
function isStrong(pw){const r=getRules(pw);return r.len&&r.upper&&r.num&&r.sym;}

function updateBar(pw){
  const fill=$('str-fill'),txt=$('str-txt');
  const rules=getRules(pw);
  const score=[rules.len,rules.upper,rules.num,rules.sym].filter(Boolean).length;
  const map=[
    {w:'0%',c:'transparent',t:''},
    {w:'25%',c:'#ef4444',t:'Weak'},
    {w:'50%',c:'#f97316',t:'Fair'},
    {w:'75%',c:'#eab308',t:'Good'},
    {w:'100%',c:'var(--teal)',t:'Strong ✓'},
  ];
  const s=pw.length===0?0:score;
  if(fill){fill.style.width=map[s].w;fill.style.background=map[s].c;}
  if(txt){txt.textContent=pw?map[s].t:'';txt.style.color=map[s].c;}
  const rMap={'rule-len':rules.len,'rule-upper':rules.upper,'rule-num':rules.num,'rule-symbol':rules.sym};
  Object.entries(rMap).forEach(([id,passed])=>{
    const el=$(id);if(!el)return;
    el.classList.toggle('met',passed);
    const icon=el.querySelector('i');
    if(icon)icon.className=passed?'fas fa-check-circle':'fas fa-circle';
  });
}

function matchCheck(pw,cpw,grp,errId,matchId){
  const ok=$(matchId);
  if(!cpw){clr(grp,errId);if(ok)ok.classList.add('hidden');return;}
  if(pw===cpw){setOk(grp,errId);if(ok)ok.classList.remove('hidden');}
  else{setErr(grp,errId,'Passwords do not match.');if(ok)ok.classList.add('hidden');}
}

(function initLogin(){
  const form=$('loginForm');if(!form)return;

  const flink=$('forgotLink');
  if(flink)flink.addEventListener('click',e=>{
    e.preventDefault();
    $('fpOverlay').classList.remove('hidden');
  });
  const fpClose=$('fpClose');
  if(fpClose)fpClose.addEventListener('click',closeModal);
  $('fpOverlay')&&$('fpOverlay').addEventListener('click',e=>{
    if(e.target===$('fpOverlay'))closeModal();
  });

  $('email').addEventListener('blur',function(){
    const v=this.value.trim();
    if(!v)return setErr('grp-email','err-email','Email is required.');
    if(!validEmail(v))return setErr('grp-email','err-email','Enter a valid email.');
    setOk('grp-email','err-email');
  });
  $('email').addEventListener('input',function(){if(this.value.trim())clr('grp-email','err-email');});
  $('password').addEventListener('blur',function(){
    const v=this.value;
    if(!v)return setErr('grp-pass','err-pass','Password is required.');
    if(v.length<6)return setErr('grp-pass','err-pass','At least 6 characters.');
    setOk('grp-pass','err-pass');
  });
  $('password').addEventListener('input',function(){if(this.value)clr('grp-pass','err-pass');});

  form.addEventListener('submit',function(e){
    e.preventDefault();
    let ok=true;
    const email=$('email').value.trim();
    const pw=$('password').value;
    if(!email){setErr('grp-email','err-email','Email is required.');ok=false;}
    else if(!validEmail(email)){setErr('grp-email','err-email','Enter a valid email.');ok=false;}
    else setOk('grp-email','err-email');
    if(!pw){setErr('grp-pass','err-pass','Password is required.');ok=false;}
    else if(pw.length<6){setErr('grp-pass','err-pass','At least 6 characters.');ok=false;}
    else setOk('grp-pass','err-pass');
    if(!ok)return;

    setLoad('loginBtn','loginTxt','loginLoad',true);
    const fd=new FormData();fd.append('email',email);fd.append('password',pw);
    fetch('php/login.php',{method:'POST',body:fd})
      .then(r=>r.json())
      .then(data=>{
        setLoad('loginBtn','loginTxt','loginLoad',false);
        if(data.status==='success'){
          showToast('toast','✓ Signed in! Redirecting…','ok');
          setTimeout(()=>{window.location.href=data.redirect||'php/dashboard.php';},1200);
        }else{showToast('toast','✗ '+data.message,'bad');}
      })
      .catch(()=>{setLoad('loginBtn','loginTxt','loginLoad',false);showToast('toast','✗ Something went wrong.','bad');});
  });
})();

(function initRegister(){
  const form=$('registerForm');if(!form)return;

  $('rpw').addEventListener('input',function(){
    updateBar(this.value);clr('grp-rpw','err-rpw');
    const cv=$('cpw').value;if(cv)matchCheck(this.value,cv,'grp-cpw','err-cpw','match-ok');
  });
  $('cpw').addEventListener('input',function(){matchCheck($('rpw').value,this.value,'grp-cpw','err-cpw','match-ok');});

  let emailTimer;
  $('remail').addEventListener('input',function(){
    clr('grp-remail','err-remail');
    const h=$('hint-email');if(h)h.textContent='';
    clearTimeout(emailTimer);
    const v=this.value.trim();
    if(validEmail(v))emailTimer=setTimeout(()=>checkEmailAjax(v,'hint-email'),600);
  });

  $('fname').addEventListener('blur',function(){
    const v=this.value.trim();
    if(!v||v.length<2)setErr('grp-fname','err-fname','Enter your first name.');
    else setOk('grp-fname','err-fname');
  });
  $('lname').addEventListener('blur',function(){
    const v=this.value.trim();
    if(!v)setErr('grp-lname','err-lname','Enter your last name.');
    else setOk('grp-lname','err-lname');
  });
  $('remail').addEventListener('blur',function(){
    const v=this.value.trim();
    if(!v)setErr('grp-remail','err-remail','Email is required.');
    else if(!validEmail(v))setErr('grp-remail','err-remail','Enter a valid email.');
  });
  $('phone').addEventListener('blur',function(){
    const v=this.value.trim();
    if(v&&!validPhone(v))setErr('grp-phone','err-phone','Enter a valid phone number.');
    else clr('grp-phone','err-phone');
  });
  $('rpw').addEventListener('blur',function(){
    const v=this.value;
    if(!v)return setErr('grp-rpw','err-rpw','Password is required.');
    if(v.length<8)return setErr('grp-rpw','err-rpw','At least 8 characters.');
    if(!isStrong(v))return setErr('grp-rpw','err-rpw','Must include uppercase, number & symbol.');
    setOk('grp-rpw','err-rpw');
  });
  $('cpw').addEventListener('blur',function(){matchCheck($('rpw').value,this.value,'grp-cpw','err-cpw','match-ok');});

  form.addEventListener('submit',function(e){
    e.preventDefault();
    let ok=true;
    const fn=$('fname').value.trim(),ln=$('lname').value.trim();
    const email=$('remail').value.trim(),phone=$('phone').value.trim();
    const pw=$('rpw').value,cpw=$('cpw').value;
    const terms=$('terms').checked;

    if(!fn||fn.length<2){setErr('grp-fname','err-fname','First name required.');ok=false;}else setOk('grp-fname','err-fname');
    if(!ln){setErr('grp-lname','err-lname','Last name required.');ok=false;}else setOk('grp-lname','err-lname');
    if(!email){setErr('grp-remail','err-remail','Email is required.');ok=false;}
    else if(!validEmail(email)){setErr('grp-remail','err-remail','Enter a valid email.');ok=false;}
    else setOk('grp-remail','err-remail');
    if(phone&&!validPhone(phone)){setErr('grp-phone','err-phone','Enter a valid phone.');ok=false;}
    if(!pw){setErr('grp-rpw','err-rpw','Password required.');ok=false;}
    else if(pw.length<8){setErr('grp-rpw','err-rpw','At least 8 characters.');ok=false;}
    else if(!isStrong(pw)){setErr('grp-rpw','err-rpw','Must include uppercase, number & symbol.');ok=false;}
    else setOk('grp-rpw','err-rpw');
    if(!cpw){setErr('grp-cpw','err-cpw','Confirm your password.');ok=false;}
    else if(pw!==cpw){setErr('grp-cpw','err-cpw','Passwords do not match.');ok=false;}
    else setOk('grp-cpw','err-cpw');
    if(!terms){setErr('grp-terms','err-terms','You must agree to continue.');ok=false;}
    else clr('grp-terms','err-terms');
    if(!ok)return;

    setLoad('regBtn','regTxt','regLoad',true);
    const fd=new FormData();
    fd.append('firstName',fn);fd.append('lastName',ln);
    fd.append('email',email);fd.append('phone',phone);fd.append('password',pw);
    fetch('php/register.php',{method:'POST',body:fd})
      .then(r=>r.json())
      .then(data=>{
        setLoad('regBtn','regTxt','regLoad',false);
        if(data.status==='success'){
          showToast('reg-toast',`✓ Account created! Welcome, ${fn}. Redirecting…`,'ok');
          setTimeout(()=>{window.location.href='login.html';},2000);
        }else{showToast('reg-toast','✗ '+data.message,'bad');}
      })
      .catch(()=>{setLoad('regBtn','regTxt','regLoad',false);showToast('reg-toast','✗ Something went wrong.','bad');});
  });
})();

function checkEmailAjax(val,hintId){
  const hint=$(hintId);if(!hint)return;
  hint.style.color='var(--text3)';hint.textContent='Checking…';
  const fd=new FormData();fd.append('email',val);
  fetch('php/check_email.php',{method:'POST',body:fd})
    .then(r=>r.json())
    .then(data=>{
      if(data.taken){hint.style.color='var(--err)';hint.textContent='✗ Email already registered.';}
      else{hint.style.color='var(--ok)';hint.textContent='✓ Email is available.';}
    })
    .catch(()=>{hint.textContent='';});
}

function closeModal(){
  const ov=$('fpOverlay');if(ov)ov.classList.add('hidden');
  ['fpStep1','fpStep2','fpStep3'].forEach((id,i)=>{
    const el=$(id);if(!el)return;
    el.classList.toggle('hidden',i!==0);
  });
  ['fpEmail','fpCurr','fpNew','fpConf'].forEach(id=>{const el=$(id);if(el)el.value='';});
}

window.fpNext1=function(){
  const email=$('fpEmail').value.trim();
  if(!email||!validEmail(email)){setErr('grp-fpemail','err-fpemail','Enter a valid email.');return;}
  setLoad('fpStep1Btn'||null,'fpStep1Txt','fpStep1Load',true);
  const fd=new FormData();fd.append('action','check_email');fd.append('email',email);
  fetch('php/reset_password.php',{method:'POST',body:fd})
    .then(r=>r.json())
    .then(data=>{
      setLoad(null,'fpStep1Txt','fpStep1Load',false);
      const btn=$('fpStep1Btn');if(btn)btn.disabled=false;
      if(data.status==='success'){
        $('fpStep1').classList.add('hidden');$('fpStep2').classList.remove('hidden');
      }else{setErr('grp-fpemail','err-fpemail',data.message||'Email not found.');}
    })
    .catch(()=>{const btn=$('fpStep1Btn');if(btn)btn.disabled=false;});
};

window.fpNext2=function(){
  const curr=$('fpCurr').value;
  if(!curr){setErr('grp-fpcurr','err-fpcurr','Enter your current password.');return;}
  const btn=$('fpStep2Btn');if(btn)btn.disabled=true;
  const fd=new FormData();
  fd.append('action','verify_password');
  fd.append('email',$('fpEmail').value.trim());
  fd.append('currentPassword',curr);
  fetch('php/reset_password.php',{method:'POST',body:fd})
    .then(r=>r.json())
    .then(data=>{
      if(btn)btn.disabled=false;
      if(data.status==='success'){
        $('fpStep2').classList.add('hidden');$('fpStep3').classList.remove('hidden');
        $('fpNew').addEventListener('input',function(){updateFpBar(this.value);});
        $('fpConf').addEventListener('input',function(){matchCheck($('fpNew').value,this.value,'grp-fpconf','err-fpconf','fp-match-ok');});
      }else{setErr('grp-fpcurr','err-fpcurr',data.message||'Incorrect password.');}
    })
    .catch(()=>{if(btn)btn.disabled=false;});
};

function updateFpBar(pw){
  const fill=$('fp-str-fill'),txt=$('fp-str-txt');
  if(!fill)return;
  const rules=getRules(pw);
  const score=[rules.len,rules.upper,rules.num,rules.sym].filter(Boolean).length;
  const map=[{w:'0%',c:'transparent'},{w:'25%',c:'#ef4444'},{w:'50%',c:'#f97316'},{w:'75%',c:'#eab308'},{w:'100%',c:'var(--teal)'}];
  const s=pw.length===0?0:score;
  fill.style.width=map[s].w;fill.style.background=map[s].c;
}

window.fpSubmit=function(){
  const newPw=$('fpNew').value,conf=$('fpConf').value;
  let ok=true;
  if(!newPw||newPw.length<8){setErr('grp-fpnew','err-fpnew','At least 8 characters.');ok=false;}
  else if(!isStrong(newPw)){setErr('grp-fpnew','err-fpnew','Must include uppercase, number & symbol.');ok=false;}
  if(!conf){setErr('grp-fpconf','err-fpconf','Confirm your password.');ok=false;}
  else if(newPw!==conf){setErr('grp-fpconf','err-fpconf','Passwords do not match.');ok=false;}
  if(!ok)return;
  const btn=$('fpStep3Btn');if(btn)btn.disabled=true;
  const fd=new FormData();
  fd.append('action','reset');
  fd.append('email',$('fpEmail').value.trim());
  fd.append('newPassword',newPw);
  fetch('php/reset_password.php',{method:'POST',body:fd})
    .then(r=>r.json())
    .then(data=>{
      if(btn)btn.disabled=false;
      if(data.status==='success'){
        showToast('fp-toast','✓ Password updated! You can now sign in.','ok');
        setTimeout(()=>closeModal(),2500);
      }else{showToast('fp-toast','✗ '+(data.message||'Update failed.'),'bad');}
    })
    .catch(()=>{if(btn)btn.disabled=false;});
};

function getRules(pw){
  return{len:pw.length>=8,upper:/[A-Z]/.test(pw),num:/[0-9]/.test(pw),sym:/[^A-Za-z0-9]/.test(pw)};
}
