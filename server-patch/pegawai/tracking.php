<?php
require dirname(__DIR__).'/bootstrap.php';
require dirname(__DIR__).'/ui.php';
$me=requireLogin();
$u=currentEmployee((int)$me['id']);
if(!in_array(($u['role']??''),array('pjlp','pppk'),true)){http_response_code(403);exit('Menu ini hanya tersedia untuk pegawai PJLP / PPSU dan PPPK Paruh Waktu.');}
ensureCikokoAdvancedSchema();
$active=activeTrackingSession((int)$u['id']);
$hist=db()->prepare("SELECT s.*,(SELECT COUNT(*) FROM tracking_points p WHERE p.session_id=s.id) point_count FROM tracking_sessions s WHERE s.user_id=? ORDER BY s.id DESC LIMIT 20");
$hist->execute(array((int)$u['id']));$rows=$hist->fetchAll();
ui_begin('Jam Kerja & Lokasi','tracking',$u,false);
ui_page_title('Jam Kerja & Lokasi','Mulai bertugas mengaktifkan lokasi selama jam kerja. Aplikasi Android resmi menggunakan layanan lokasi latar depan dengan notifikasi, sementara situs web hanya dapat memantau saat halaman aktif.','Dasbor Pegawai › Jam Kerja & Lokasi');
?>
<style media="screen">
#deviceMap.map-live{height:auto!important;min-height:96px!important;padding:14px!important;border-radius:10px!important;display:flex;align-items:center;background:#f3f6fb;border:1px solid #e0e7f0}
#deviceMap .tracking-map-placeholder{padding:0!important;font-size:12px;line-height:1.6}
.location-summary{display:grid;gap:5px;width:100%}.location-summary strong{font-size:14px;color:#183f6a}.location-summary span{font-size:13px;font-variant-numeric:tabular-nums}.location-summary small{font-size:12px;color:#65758a}
</style>
<div id="liveStrip" class="live-strip <?=$active?'':'off'?>"><span class="live-dot"></span><div><b id="liveTitle"><?=$active?'Sesi jam kerja aktif':'Sesi jam kerja belum aktif'?></b><div id="liveSub" class="muted"><?=$active?'Sesi absensi aktif. Pastikan waktu pembaruan dan jumlah titik GPS terus bertambah untuk memeriksa pengiriman lokasi.':'Lakukan mulai bertugas untuk memulai pencatatan jam kerja dan lokasi.'?></div></div></div>
<?php if($active): ?>
<div class="cikoko-shift-timer" aria-label="Durasi jam kerja" data-shift-start="<?=h((string)$active['checkin_at'])?>" data-server-epoch="<?=time()?>" data-start-epoch="<?=strtotime((string)$active['checkin_at'])?>">
  <?=ui_icon('clock')?><div><small>Durasi bertugas</small><strong id="cikokoShiftDuration">Memuat durasi…</strong></div><span class="pill ok">Sesi Aktif</span>
</div>
<script>(function(){var box=document.querySelector('[data-shift-start]');if(!box)return;var elapsed=Math.max(0,(+box.dataset.serverEpoch)-(+box.dataset.startEpoch));var begun=performance.now();function tick(){var secs=Math.max(0,elapsed+Math.floor((performance.now()-begun)/1000));var pad=n=>String(n).padStart(2,'0');document.getElementById('cikokoShiftDuration').textContent=pad(Math.floor(secs/3600))+':'+pad(Math.floor(secs%3600/60))+':'+pad(secs%60);}tick();setInterval(tick,1000);})();</script>
<?php endif; ?>
<div class="tracking-grid">
  <div class="card">
    <div class="card-header"><div class="card-title"><?=ui_icon('track')?> Status Jam Kerja</div><span id="liveBadge" class="pill <?=$active?'ok':'neutral'?>"><?=$active?'Sedang Bertugas':'Belum Mulai Bertugas'?></span></div>
    <div class="tracking-status">
      <div class="box"><span>Nama</span><b><?=h($u['nama_lengkap'])?></b></div>
      <div class="box"><span>Jenis Pegawai</span><b><?=h(roleLabel((string)$u['role']))?></b></div>
      <div class="box"><span>Nomor Pegawai</span><b><?=h($u['nomor_pegawai']?:'-')?></b></div>
      <div class="box"><span>Koordinat Terakhir</span><b id="coord"><?=$active?h($active['last_lat'].', '.$active['last_lng']):'-'?></b></div>
      <div class="box"><span>Akurasi GPS</span><b id="accuracy"><?=$active?'±'.round((float)$active['last_accuracy']).' meter':'-'?></b></div>
      <div class="box"><span>Pembaruan Terakhir</span><b id="lastUpdate"><?=$active?h($active['last_seen_at']):'-'?></b></div>
      <div class="box"><span>Waktu Mulai Bertugas</span><b id="checkinAt"><?=$active?h($active['checkin_at']):'-'?></b></div>
    </div>
    <div class="notice info" style="margin-top:16px"><b>Aplikasi Android:</b> setelah mulai bertugas dan persetujuan izin lokasi, layanan sistem melanjutkan pengiriman GPS saat aplikasi diminimalkan, ditandai notifikasi aktif. Android dapat menghentikan layanan karena pembatasan baterai atau perangkat mati. Pada browser, lokasi hanya berjalan saat halaman aktif.</div>
    <div class="tracking-photo-capture" id="checkinPhotoBox" style="<?=$active?'display:none':''?>">
      <div><b>Foto Mulai Bertugas</b><small>Ambil foto kondisi/posisi saat mulai bertugas. Cap waktu WIB, nama dan lokasi langsung dicetak pada foto setelah kamera digunakan.</small></div>
      <label class="btn light" for="checkinPhoto">Ambil Foto</label><input type="file" id="checkinPhoto" accept="image/jpeg,image/png,image/webp" capture="environment" hidden>
      <img id="checkinPreview" class="tracking-photo-preview" alt="Pratinjau foto mulai bertugas" style="display:none">
    </div>
    <div class="tracking-photo-capture" id="checkoutPhotoBox" style="<?=$active?'':'display:none'?>">
      <div><b>Foto Selesai Bertugas</b><small>Ambil foto akhir kerja. Cap pengambilan foto dicetak langsung. Waktu selesai bertugas resmi tetap dicatat server saat dikirim.</small></div>
      <label class="btn light" for="checkoutPhoto">Ambil Foto</label><input type="file" id="checkoutPhoto" accept="image/jpeg,image/png,image/webp" capture="environment" hidden>
      <img id="checkoutPreview" class="tracking-photo-preview" alt="Pratinjau foto selesai bertugas" style="display:none">
    </div>
    <div class="cikoko-photo-cap-guide"><strong>Cap foto resmi:</strong> Cap waktu pengambilan langsung terlihat di pratinjau foto (waktu diterbitkan server), lengkap dengan nama dan koordinat GPS. Waktu mulai bertugas/selesai bertugas sah dicatat terpisah ketika server menerima proses.</div>
    <div class="form-actions"><button type="button" class="btn" id="startBtn" <?=$active?'disabled':''?>>Mulai Bertugas</button><button type="button" class="btn red" id="stopBtn" <?=$active?'':'disabled'?>>Selesai Bertugas</button><a class="btn light" id="mapsBtn" target="_blank" rel="noopener" href="<?=$active?h(mapsUrl($active['last_lat'],$active['last_lng'])):'#'?>">Buka Google Maps</a></div>
  </div>
  <div class="card"><div class="card-header"><div class="card-title"><?=ui_icon('pin')?> Posisi Perangkat</div><span class="pill neutral" id="pwaTrackingState">Memeriksa sesi</span></div><div class="map-live is-waiting" id="deviceMap"><div class="tracking-map-placeholder">Tekan <b>Uji Lokasi</b> untuk melihat koordinat dan akurasi GPS. Peta dibuka hanya saat diperlukan.</div></div><div id="gpsMessage" class="muted tracking-gps-message" role="status" aria-live="polite">Memeriksa status sesi jam kerja.</div><div class="tracking-gps-actions"><button class="btn light" type="button" id="testGpsBtn">Uji Lokasi</button><small>Hanya menampilkan posisi perangkat. Tidak membuat sesi kerja atau mengirim titik lokasi ke server.</small></div></div>
</div>
<section class="card mw-session-history"><div class="card-header"><div class="card-title">Riwayat Jam Kerja</div></div>
<?php if(!$rows):?><p class="muted">Belum ada riwayat.</p><?php endif;?>
<?php $days=array('Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu');foreach($rows as $r):$at=strtotime($r['checkin_at']);?>
<div class="mw-session-row"><div><strong><?=h($days[(int)date('w',$at)])?>, <?=h(date('d/m/Y · H:i',$at))?></strong><small><?=h(statusLabel($r['status']))?> · <?=(int)$r['point_count']?> titik GPS</small></div><button type="button" class="btn light" data-session-detail="<?=(int)$r['id']?>">Lihat Detail</button></div>
<?php endforeach;?></section>
<script src="../assets/session-detail-v7.js?v=20261015-23"></script>
<script src="../assets/tracking-client.js?v=20261015-23"></script>
<script>
(function(){
  const startBtn=document.getElementById('startBtn'),stopBtn=document.getElementById('stopBtn'),gpsMessage=document.getElementById('gpsMessage'),testGpsBtn=document.getElementById('testGpsBtn'),deviceMap=document.getElementById('deviceMap'),stateBadge=document.getElementById('pwaTrackingState'),checkinInput=document.getElementById('checkinPhoto'),checkoutInput=document.getElementById('checkoutPhoto'),checkinBox=document.getElementById('checkinPhotoBox'),checkoutBox=document.getElementById('checkoutPhotoBox');
  async function captured(input,imgId,label){const img=document.getElementById(imgId);if(!input.files||!input.files[0])return;
    const flow=window.CikokoPhotoProgress;
    try{
      if(flow){flow.open('Mempersiapkan Foto','Meminta waktu resmi dan GPS perangkat.');flow.set(15,'Menyematkan Keterangan','Menambahkan nama pegawai, waktu WIB, dan koordinat GPS.',1);}
      else CikokoUI.progress('Mencetak Cap Foto','Menambahkan waktu resmi dan lokasi GPS ke foto.','Mohon tunggu hingga pratinjau tampil.');
      const result=await CikokoCapture.stampInput(input,label,img);
      if(flow){flow.open('Meninjau Foto','Cap waktu dan lokasi telah disematkan.');flow.ready('Foto siap digunakan. Tekan '+(label==='CHECK-IN'?'Mulai Bertugas':'Selesai Bertugas')+' untuk melanjutkan.');}
      else CikokoUI.success('Foto Siap','Cap waktu dan GPS sudah tercantum.','Foto baru akan tersimpan secara resmi setelah konfirmasi mulai atau selesai bertugas.');
    }catch(e){if(flow)flow.error(e.message||'Ambil foto kembali setelah mengizinkan GPS.');else CikokoUI.error('Cap Foto Gagal',e.message||'Ambil foto kembali setelah mengizinkan GPS.');}
  }
  checkinInput.addEventListener('change',()=>captured(checkinInput,'checkinPreview','CHECK-IN'));
  checkoutInput.addEventListener('change',()=>captured(checkoutInput,'checkoutPreview','CHECK-OUT'));
  function showPos(d){if(!d)return;const lat=Number(d.lat),lng=Number(d.lng),acc=Number(d.acc||0);if(!Number.isFinite(lat)||!Number.isFinite(lng))return;document.getElementById('coord').textContent=lat.toFixed(6)+', '+lng.toFixed(6);document.getElementById('accuracy').textContent='±'+Math.round(acc)+' meter';document.getElementById('mapsBtn').href='https://www.google.com/maps?q='+lat+','+lng;deviceMap.classList.remove('is-waiting');deviceMap.replaceChildren();const summary=document.createElement('div');summary.className='location-summary';const title=document.createElement('strong');title.textContent='Lokasi perangkat ditemukan';const coords=document.createElement('span');coords.textContent=lat.toFixed(6)+', '+lng.toFixed(6);const accuracy=document.createElement('small');accuracy.textContent='Akurasi ±'+Math.round(acc)+' meter';summary.append(title,coords,accuracy);deviceMap.append(summary);gpsMessage.textContent=acc<=30?'Akurasi GPS sangat baik (±'+Math.round(acc)+' m).':acc<=100?'Akurasi GPS memadai (±'+Math.round(acc)+' m).':'Akurasi GPS rendah (±'+Math.round(acc)+' m). Pindah ke area terbuka bila memungkinkan.';}
  function renderStatus(active,session){checkinBox.style.display=active?'none':'';checkoutBox.style.display=active?'':'none';document.getElementById('liveStrip').classList.toggle('off',!active);document.getElementById('liveTitle').textContent=active?'Sesi jam kerja aktif':'Sesi jam kerja belum aktif';document.getElementById('liveSub').textContent=active?'Sesi absensi aktif. Pastikan waktu pembaruan dan jumlah titik GPS terus bertambah untuk memeriksa pengiriman lokasi.':'Lakukan mulai bertugas untuk memulai pencatatan jam kerja dan lokasi.';document.getElementById('liveBadge').textContent=active?'Sedang Bertugas':'Belum Mulai Bertugas';document.getElementById('liveBadge').className='pill '+(active?'ok':'neutral');startBtn.disabled=active;stopBtn.disabled=!active;stateBadge.textContent=active?'Menunggu GPS':'Belum Mulai Bertugas';stateBadge.className='pill '+(active?'ok':'neutral');if(session&&session.checkin_at)document.getElementById('checkinAt').textContent=session.checkin_at;if(session&&session.last_seen_at)document.getElementById('lastUpdate').textContent=session.gps_fix_at||session.last_seen_at;}
  document.addEventListener('cikoko:tracking-status',e=>{const isActive=!!e.detail.active;renderStatus(isActive,e.detail.session||null);if(deviceMap.classList.contains('is-waiting'))gpsMessage.textContent=isActive?'Sesi kerja aktif. Menunggu koordinat GPS dari perangkat. Pastikan izin lokasi dan layanan lokasi HP aktif.':'Belum ada sesi jam kerja aktif. Untuk pratinjau posisi, tekan Uji Lokasi; pelacakan tugas dimulai setelah mulai bertugas.';});
  testGpsBtn.addEventListener('click',()=>{
    if(!navigator.geolocation){gpsMessage.textContent='Layanan lokasi tidak tersedia pada perangkat ini.';stateBadge.textContent='GPS Tidak Tersedia';stateBadge.className='pill pending';return;}
    testGpsBtn.disabled=true;testGpsBtn.textContent='Memeriksa…';gpsMessage.textContent='Meminta izin lokasi dan mencari koordinat GPS. Mohon tunggu sekitar 25 detik.';
    navigator.geolocation.getCurrentPosition(pos=>{
      showPos({lat:pos.coords.latitude,lng:pos.coords.longitude,acc:pos.coords.accuracy});
      const active=window.CikokoTracking&&window.CikokoTracking.isActive();
      gpsMessage.textContent='Pratinjau lokasi berhasil (akurasi ±'+Math.round(pos.coords.accuracy)+' m). '+(active?'Sesi kerja aktif; pengiriman GPS berlangsung melalui layanan pelacakan.':'Belum mulai bertugas: posisi ini tidak dikirim sebagai riwayat kerja.');
      stateBadge.textContent=active?'GPS Ditemukan':'Pratinjau GPS';stateBadge.className='pill ok';testGpsBtn.disabled=false;testGpsBtn.textContent='Uji Lokasi';
    },err=>{
      let reason='Posisi GPS tidak tersedia. Periksa izin lokasi, lokasi perangkat, dan koneksi internet.';
      if(err&&err.code===1)reason='Izin lokasi ditolak. Izinkan akses lokasi untuk aplikasi Kelurahan Cikoko pada Pengaturan HP, lalu coba lagi.';
      else if(err&&err.code===2)reason='Koordinat belum tersedia. Aktifkan Lokasi HP, pindah ke area terbuka, lalu coba lagi.';
      else if(err&&err.code===3)reason='Pencarian lokasi melewati batas waktu. Coba lagi di area dengan sinyal GPS yang lebih baik.';
      gpsMessage.textContent=reason;stateBadge.textContent=err&&err.code===1?'GPS Perlu Izin':err&&err.code===3?'GPS Melewati Waktu':'GPS Tidak Tersedia';stateBadge.className='pill pending';testGpsBtn.disabled=false;testGpsBtn.textContent='Uji Lokasi';
    },{enableHighAccuracy:true,maximumAge:0,timeout:25000});
  });
  document.addEventListener('cikoko:tracking-point',e=>{showPos(e.detail);if(e.detail.at)document.getElementById('lastUpdate').textContent=e.detail.at;stateBadge.textContent=e.detail.stored?'Lokasi Tersimpan':'Lokasi Diterima';stateBadge.className='pill '+(e.detail.stored?'pending':'ok');if(e.detail.stored)gpsMessage.textContent='Ini koordinat terakhir yang tersimpan di server, bukan bukti GPS sedang berjalan. Waktu tersimpan: '+(e.detail.at||'tidak tersedia')+'. Periksa panel Koneksi tracking Android di atas.';});
  document.addEventListener('cikoko:tracking-error',e=>{gpsMessage.textContent=e.detail.message||'Pembaruan lokasi tidak dapat dilakukan.';stateBadge.textContent='GPS Perlu Perhatian';stateBadge.className='pill pending';});
  startBtn.addEventListener('click',async()=>{const ok=await CikokoUI.confirm('Konfirmasi Mulai Bertugas','Sistem akan mencatat waktu mulai bertugas dan mengaktifkan pemantauan lokasi kerja.','Berikan izin lokasi dengan akurasi tinggi. Aplikasi Android menggunakan notifikasi pemantauan selama bertugas; browser memerlukan halaman aktif.','Mulai Jam Kerja');if(!ok)return;startBtn.disabled=true;CikokoUI.progress('Mengaktifkan Jam Kerja','Sistem sedang memperoleh koordinat GPS dan membuat sesi kerja.','Mohon tunggu hingga posisi perangkat berhasil diverifikasi.');try{const f=checkinInput.files&&checkinInput.files[0];if(!f)throw new Error('Ambil atau pilih foto mulai bertugas terlebih dahulu.');const r=await CikokoTracking.start(f,checkinInput.dataset.stampToken||'');showPos(r.position);renderStatus(true,r.data.session||null);checkoutInput.value='';document.getElementById('checkoutPreview').style.display='none';CikokoUI.success('Mulai Bertugas Berhasil',r.data.message||'Sesi jam kerja telah aktif.','Sesi kerja tercatat. Periksa panel Koneksi tracking Android, notifikasi, dan pertambahan titik GPS.');}catch(e){startBtn.disabled=false;CikokoUI.error('Mulai Bertugas Gagal',e.message,'Periksa izin lokasi, layanan GPS, dan koneksi internet lalu coba kembali.');}});
  stopBtn.addEventListener('click',async()=>{const ok=await CikokoUI.confirm('Konfirmasi Selesai Bertugas','Sesi jam kerja akan ditutup dan pemantauan lokasi dihentikan.','Posisi dan foto akhir diberi waktu WIB dari server. Laporan yang belum lengkap tetap ditandai sebagai kekurangan dokumentasi.','Selesaikan Jam Kerja');if(!ok)return;stopBtn.disabled=true;CikokoUI.progress('Menyelesaikan Jam Kerja','Sistem sedang menyimpan posisi terakhir dan waktu selesai bertugas.','Mohon tunggu hingga proses selesai.');try{const f=checkoutInput.files&&checkoutInput.files[0];if(!f)throw new Error('Ambil atau pilih foto selesai bertugas terlebih dahulu.');const r=await CikokoTracking.stop(f,checkoutInput.dataset.stampToken||'');renderStatus(false,null);CikokoUI.success('Selesai Bertugas Berhasil',r.data.message||'Sesi jam kerja telah ditutup.','Riwayat jam kerja telah disimpan. Halaman akan dimuat ulang.');setTimeout(()=>location.reload(),1100);}catch(e){stopBtn.disabled=false;CikokoUI.error('Selesai Bertugas Gagal',e.message);}});
  if(window.CikokoTracking)CikokoTracking.status().then(r=>renderStatus(r.active,r.session||null)).catch(()=>{});
})();
</script>
<?php ui_end();?>
