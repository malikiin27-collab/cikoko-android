/* Secure origin-bound Android WebMessage bridge. In browsers this file does nothing. */
(function(){
 'use strict';
 const bridge=window.CikokoNative;
 if(!bridge||typeof bridge.postMessage!=='function'||!window.CikokoTracking) return;
 let handshake;
 function report(message){document.dispatchEvent(new CustomEvent('cikoko:tracking-error',{detail:{message:message}}));}
 const ready=new Promise(resolve=>{handshake=resolve;});
 bridge.onmessage=event=>{
   try{const msg=JSON.parse(event.data);if(msg.type==='init')handshake(msg);}catch(e){}
 };
 bridge.postMessage(JSON.stringify({type:'init'}));
 let authPromise=null,authenticated=false;
 async function nativeAuth(){
   if(authenticated)return true;
   if(authPromise)return authPromise;
   authPromise=(async()=>{
     const init=await Promise.race([ready,new Promise((_,reject)=>setTimeout(()=>reject(new Error('Layanan Android belum merespons. Buka ulang aplikasi dan periksa izin lokasi.')),10000))]);
     if(init.has_token){authenticated=true;return true;}
     const meta=document.querySelector('meta[name="csrf-token"]');
     const base=document.querySelector('meta[name="app-base-url"]');
     if(!meta||!base)throw Error('Kredensial halaman tidak tersedia.');
     const request=new URLSearchParams({csrf_token:meta.content,device_id:init.device_id});
     const r=await fetch(base.content+'/api/mobile/bridge.php',{
       method:'POST',credentials:'same-origin',cache:'no-store',
       headers:{'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'CikokoNative'},body:request
     });
     const data=await r.json();
     if(!r.ok||!data.ok||!data.token)throw Error(data.message||'Autentikasi aplikasi gagal.');
     bridge.postMessage(JSON.stringify({type:'token',token:data.token}));
     authenticated=true;
     return true;
   })().finally(()=>{authPromise=null;});
   return authPromise;
 }
 async function sync(active){
   if(active){try{await nativeAuth();bridge.postMessage(JSON.stringify({type:'tracking',active:true}));}
     catch(e){report(e.message);}}
   else bridge.postMessage(JSON.stringify({type:'tracking',active:false}));
 }
 document.addEventListener('cikoko:tracking-status',event=>sync(!!(event.detail&&event.detail.active)));
 nativeAuth().then(()=>window.CikokoTracking.status()).then(r=>sync(r.active)).catch(e=>report(e.message));
})();
