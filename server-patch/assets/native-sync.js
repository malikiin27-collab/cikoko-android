/* Cikoko Android: authenticate once per page; keep startup failures visible. */
(function(){
 'use strict';
 if(window.CikokoNativeSyncLoaded)return;
 window.CikokoNativeSyncLoaded=true;
 const bridge=window.CikokoNative;
 const isAndroid=/CikokoAndroid\//.test(navigator.userAgent);
 if(!bridge&&!isAndroid)return;
 const anchor=document.getElementById('liveStrip');
 const panel=document.createElement('div');
 panel.id='nativeTrackingDiagnostic';panel.className='card';
 panel.setAttribute('role','status');panel.style.cssText='padding:16px;margin:12px 0;overflow-wrap:anywhere;border:1px solid #cbd5e1';
 const heading=document.createElement('strong');heading.textContent='Status tracking Android · D15';
 const message=document.createElement('p');
 panel.append(heading,message);
 if(anchor)anchor.insertAdjacentElement('afterend',panel);
 function report(text,error){
   message.textContent=text;
   panel.style.borderColor=error?'#b91c1c':'#cbd5e1';
   if(error)document.dispatchEvent(new CustomEvent('cikoko:tracking-error',{detail:{message:text}}));
 }
 if(!bridge||typeof bridge.postMessage!=='function'){
   report('ANDROID_BRIDGE: Penghubung Android tidak tersedia. Periksa versi APK, Android System WebView, dan domain aplikasi.',true);return;
 }
 if(!window.CikokoTracking){report('WEB_CLIENT: Berkas tracking-client.js belum berhasil dimuat.',true);return;}
 let handshake,tokenSaved,latestHealth=null;
 const ready=new Promise(resolve=>{handshake=resolve;});
 bridge.onmessage=event=>{try{const msg=JSON.parse(event.data);if(msg.type==='init')handshake(msg);else if(msg.type==='token_saved'&&tokenSaved)tokenSaved();else if(msg.type==='health'){latestHealth=msg;if(authenticated)renderHealth(msg);}}catch(e){}};
 bridge.postMessage(JSON.stringify({type:'init'}));
 let authenticated=false,authPromise=null,lastAttempt=0,desiredActive=false;
 async function jsonRequest(url,options,stage){
   const controller=new AbortController();const timer=setTimeout(()=>controller.abort(),10000);
   try{
     const response=await fetch(url,Object.assign({cache:'no-store',credentials:'same-origin'},options,{signal:controller.signal}));
     let data;try{data=await response.json();}catch(e){throw Error(stage+': HTTP '+response.status+' — respons bukan JSON. Periksa alamat API dan error_log hosting.');}
     if(!response.ok||!data.ok)throw Error(stage+': HTTP '+response.status+' — '+(data.message||'Permintaan ditolak server.'));
     return data;
   }catch(e){if(e.name==='AbortError')throw Error(stage+': Server tidak merespons dalam 10 detik.');throw e;}
   finally{clearTimeout(timer);}
 }
 async function nativeAuth(){
   if(authenticated)return;
   if(authPromise)return authPromise;
   authPromise=(async()=>{
     report('Memeriksa penghubung Android dan autentikasi server.');
     let timeout;
     const init=await Promise.race([ready,new Promise((_,reject)=>{timeout=setTimeout(()=>reject(Error('ANDROID_INIT: APK tidak merespons penghubung halaman.')),10000);})]).finally(()=>clearTimeout(timeout));
     const meta=document.querySelector('meta[name="csrf-token"]');
     const base=document.querySelector('meta[name="app-base-url"]');
     if(!meta||!base)throw Error('WEB_META: Kredensial halaman tidak tersedia.');
     const root=base.content.replace(/\/$/,'');
     const data=await jsonRequest(root+'/api/mobile/bridge.php',{
       method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'CikokoNative'},
       body:new URLSearchParams({csrf_token:meta.content,device_id:init.device_id})
     },'TOKEN');
     if(!data.token)throw Error('TOKEN: Server tidak memberikan token aplikasi.');
     // Verify the same Bearer API used by the native service before starting it.
     await jsonRequest(root+'/api/mobile/status.php',{credentials:'omit',headers:{Authorization:'Bearer '+data.token}},'API_STATUS');
     let savedTimer;const saved=new Promise((resolve,reject)=>{tokenSaved=resolve;if(init.protocol>=2)savedTimer=setTimeout(()=>reject(Error('TOKEN_LOCAL: APK belum mengonfirmasi penyimpanan token.')),8000);});
     bridge.postMessage(JSON.stringify({type:'token',token:data.token}));
     if(init.protocol>=2)try{await saved;}finally{clearTimeout(savedTimer);tokenSaved=null;}
     authenticated=true;
   })().finally(()=>{authPromise=null;});
   return authPromise;
 }
 async function sync(active){
   desiredActive=active;
   if(!active){bridge.postMessage(JSON.stringify({type:'tracking',active:false}));report('Sesi kerja belum aktif.');return;}
   if(authPromise||Date.now()-lastAttempt<10000)return;
   lastAttempt=Date.now();
   try{
     await nativeAuth();if(!desiredActive)return;
     bridge.postMessage(JSON.stringify({type:'tracking',active:true}));
     if(latestHealth)renderHealth(latestHealth);else report('Autentikasi API berhasil. Menunggu status layanan APK. Jika status tidak muncul, gunakan APK versi 2.3.0.');
     bridge.postMessage(JSON.stringify({type:'health'}));
   }catch(e){report(e.message||'Koneksi Android gagal.',true);}
 }
 function renderHealth(h){
   function at(value){return value?new Date(value).toLocaleTimeString('id-ID'):'belum ada';}
   const warnings=[];
   if(!h.precise)warnings.push('Izinkan lokasi akurat.');
   if(!h.location_enabled)warnings.push('Aktifkan GPS HP.');
   if(!h.notifications)warnings.push('Notifikasi tracking dinonaktifkan.');
   if(!h.battery_unrestricted)warnings.push('Optimasi baterai masih aktif; periksa Pengaturan tracking.');
   const confirmedFix=h.server_fix_at===undefined?h.fix_at:h.server_fix_at;
   const stale=desiredActive&&(!h.sent_at||Date.now()-h.sent_at>20000||!confirmedFix||Date.now()-confirmedFix>20000);
   report((h.running?'Layanan GPS berjalan. ':'Layanan GPS berhenti. ')+h.message+
     (h.accuracy?' Akurasi sampel ±'+Math.round(h.accuracy)+' m. ':'')+' Lokasi terakhir: '+at(h.fix_at)+'. Waktu GPS yang diakui server: '+at(confirmedFix)+'. Konfirmasi diterima: '+at(h.sent_at)+'. '+
     (stale?'Belum ada lokasi baru yang terkonfirmasi dalam 20 detik. ':'')+warnings.join(' ')+
     ' Perangkat: '+h.device+' · Android '+h.android+' · APK '+h.version+'. '+h.provider+'.',desiredActive&&(!h.running||stale));
 }
 const controls=document.createElement('div');controls.style.cssText='display:flex;gap:8px;flex-wrap:wrap';
 [['Pengaturan tracking','tracking_settings'],['Izin lokasi latar belakang','background_permission']].forEach(([label,type])=>{
   const button=document.createElement('button');button.type='button';button.className='btn outline';button.textContent=label;
   button.addEventListener('click',()=>bridge.postMessage(JSON.stringify({type})));controls.append(button);
 });panel.append(controls);
 setInterval(()=>{if(!document.hidden)bridge.postMessage(JSON.stringify({type:'health'}));},5000);
 document.addEventListener('visibilitychange',()=>{if(!document.hidden)bridge.postMessage(JSON.stringify({type:'health'}));});
 document.addEventListener('cikoko:tracking-status' ,event=>sync(!!(event.detail&&event.detail.active)));
 window.CikokoTracking.status().then(r=>sync(r.active)).catch(e=>report(e.message,true));
})();
