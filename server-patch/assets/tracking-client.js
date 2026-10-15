(function(){
'use strict';
if(window.CikokoTracking)return;
const role=(document.body&&document.body.getAttribute('data-employee-role'))||'';
if(role!=='pjlp'&&role!=='pppk')return;
const baseMeta=document.querySelector('meta[name="app-base-url"]');
const base=(baseMeta?baseMeta.content:'').replace(/\/$/,'');
const csrfMeta=document.querySelector('meta[name="csrf-token"]');
const csrf=csrfMeta?csrfMeta.content:'';
let active=false,session=null,watchId=null,lastSent=0,lastPos=null,sending=false,wakeLock=null,statusPromise=null;
function emit(name,detail){document.dispatchEvent(new CustomEvent('cikoko:'+name,{detail:detail||{}}));}
function pointData(pos,at){return {lat:pos.coords.latitude,lng:pos.coords.longitude,acc:pos.coords.accuracy,at:at||null};}
async function api(action,pos,file,stampToken){
 const fd=new FormData();fd.set('action',action);if(csrf)fd.set('csrf_token',csrf);if(pos){fd.set('lat',pos.coords.latitude);fd.set('lng',pos.coords.longitude);fd.set('acc',pos.coords.accuracy);fd.set('fix_epoch',Math.floor(pos.timestamp/1000));}if(file)fd.set('photo',file,file.name||'tracking-photo.jpg');if(stampToken)fd.set('photo_stamp_token',stampToken);
 const r=await fetch(base+'/pegawai/tracking_api.php',{method:'POST',credentials:'same-origin',body:fd});
 let j;try{j=await r.json();}catch(e){throw new Error('Respons server tidak dapat dibaca.');}if(!r.ok||!j.ok)throw new Error(j.message||'Proses jam kerja tidak dapat diselesaikan.');return j;
}
function geoOnce(){return new Promise((resolve,reject)=>{if(!navigator.geolocation){reject(new Error('Perangkat tidak mendukung layanan lokasi.'));return;}navigator.geolocation.getCurrentPosition(resolve,err=>reject(new Error('Lokasi tidak tersedia: '+err.message)),{enableHighAccuracy:true,maximumAge:0,timeout:25000});});}
async function requestWake(){if(window.CikokoNative)return;
 if(!active||document.visibilityState!=='visible'||!('wakeLock' in navigator))return;try{if(!wakeLock)wakeLock=await navigator.wakeLock.request('screen');wakeLock.addEventListener('release',()=>{wakeLock=null;});}catch(e){}}
async function releaseWake(){try{if(wakeLock)await wakeLock.release();}catch(e){}wakeLock=null;}
function clearWatch(){if(watchId!==null&&navigator.geolocation){navigator.geolocation.clearWatch(watchId);watchId=null;}}
function startWatch(){
 if(window.CikokoNative)return;
 if(!active||document.visibilityState!=='visible'||!navigator.geolocation||watchId!==null)return;
 watchId=navigator.geolocation.watchPosition(async pos=>{
   lastPos=pos;emit('tracking-point',pointData(pos));
   if(!active||!navigator.onLine||sending||Date.now()-lastSent<5000)return;
   sending=true;
   try{const j=await api('point',pos);lastSent=Date.now();emit('tracking-point',pointData(pos,j.at||null));}
   catch(e){emit('tracking-error',{message:e.message});}finally{sending=false;}
 },err=>emit('tracking-error',{message:'Lokasi: '+err.message}),{enableHighAccuracy:true,maximumAge:0,timeout:25000});
 requestWake();
}
async function refreshStatus(force){
 if(statusPromise&&!force)return statusPromise;
 statusPromise=fetch(base+'/pegawai/tracking_api.php?action=status',{credentials:'same-origin',cache:'no-store'}).then(async r=>{let j;try{j=await r.json();}catch(e){throw new Error('Status jam kerja tidak dapat dibaca.');}if(!r.ok||!j.ok)throw new Error(j.message||'Status jam kerja tidak tersedia.');active=!!j.active;session=j.session||null;emit('tracking-status',{active:active,session:session});if(window.CikokoNative&&session)emit('tracking-point',{lat:Number(session.last_lat),lng:Number(session.last_lng),acc:Number(session.last_accuracy||0),at:session.last_seen_at,stored:true});if(active)startWatch();else{clearWatch();releaseWake();}return {active:active,session:session};}).catch(e=>{emit('tracking-error',{message:e.message});throw e;}).finally(()=>{statusPromise=null;});
 return statusPromise;
}
async function start(photoFile,stampToken){
 if(!photoFile)throw new Error('Foto mulai bertugas wajib dipilih atau diambil terlebih dahulu.');
 if(!stampToken)throw new Error('Foto mulai bertugas/check-out belum diberi cap. Ambil foto kembali.');
 const pos=await geoOnce();lastPos=pos;
 if(Number(pos.coords.accuracy)>200)throw new Error('Akurasi GPS masih rendah (±'+Math.round(pos.coords.accuracy)+' m). Pindah ke area terbuka dan coba kembali.');
 const j=await api('start',pos,photoFile,stampToken);active=true;session={id:j.session_id,checkin_at:j.at||new Date().toLocaleString('id-ID')};lastSent=Date.now();emit('tracking-status',{active:true,session:session});emit('tracking-point',pointData(pos,j.at||null));startWatch();await requestWake();return {data:j,position:pointData(pos)};
}
async function stop(photoFile,stampToken){
 if(!photoFile)throw new Error('Foto selesai bertugas wajib dipilih atau diambil terlebih dahulu.');
 if(!stampToken)throw new Error('Foto mulai bertugas/check-out belum diberi cap. Ambil foto kembali.');
 let pos=lastPos;try{pos=await geoOnce();}catch(e){}
 const j=await api('stop',pos,photoFile,stampToken);active=false;session=null;clearWatch();await releaseWake();emit('tracking-status',{active:false,session:null});return {data:j,position:pos?pointData(pos):null};
}
document.addEventListener('visibilitychange',()=>{if(document.visibilityState==='hidden'){clearWatch();releaseWake();return;}refreshStatus(true).catch(()=>{});});
window.addEventListener('online',()=>{if(active)refreshStatus(true).catch(()=>{});});
window.addEventListener('pagehide',()=>{releaseWake();});
window.CikokoTracking={start:start,stop:stop,status:()=>refreshStatus(true),isActive:()=>active,lastPosition:()=>lastPos};
setInterval(()=>{if(window.CikokoNative&&!document.hidden)refreshStatus(false).catch(()=>{});},10000);
refreshStatus(false).catch(()=>{});
})();
