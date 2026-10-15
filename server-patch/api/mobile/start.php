<?php
declare(strict_types=1);
require __DIR__.'/common.php';
mobileRequireMethod('POST');
$auth=mobileAuth();
$data=mobileRequestData();
try {
    ensureShiftPhotoSchema();
    $uid=(int)$auth['uid']; $u=currentEmployee($uid);
    $active=activeTrackingSession($uid);
    if($active){
        mobileJson(array('ok'=>true,'message'=>'Sesi kerja sebelumnya masih aktif. Tracking dilanjutkan.','session_id'=>(int)$active['id'],'resumed'=>true,'session'=>$active));
    }
    $lat=mobileNum($data,'lat'); $lng=mobileNum($data,'lng'); $acc=mobileNum($data,'acc');
    if(!mobileValidPoint($lat,$lng,$acc)) throw new RuntimeException('Lokasi Check-In tidak valid. Aktifkan GPS presisi lalu coba kembali.');
    if($acc===null || $acc<=0 || $acc>30) throw new RuntimeException('Akurasi GPS masih rendah (±'.round($acc).' m). Tunggu hingga lokasi lebih akurat.');
    require_once __DIR__.'/tracking_v9.php';trackingRequireCurrentFix($data['fix_epoch']??null);
    if(empty($_FILES['photo']['name'])) throw new RuntimeException('Foto Check-In wajib diambil dari aplikasi.');
    $photo=uploadSafe('photo',employeeDir($u).'/tracking',mobileTrackingImageAllowed(),10485760);
    $device=trim((string)($data['device_info']??$auth['device_name']??'Android Mobile'));
    $at=date('Y-m-d H:i:s');
    try {$hash=cikokoSealPhoto(employeeDir($u).'/tracking/'.$photo,'CHECK-IN',$u,$at,$lat,$lng);}catch(Throwable $e){@unlink(employeeDir($u).'/tracking/'.$photo);throw $e;}
    $s=db()->prepare("INSERT INTO tracking_sessions(user_id,checkin_at,checkin_lat,checkin_lng,checkin_accuracy,checkin_photo,checkin_photo_sha256,last_lat,last_lng,last_accuracy,last_seen_at,status,device_info) VALUES(?,?,?,?,?,?,?,?,?,?,NOW(),'active',?)");
    $s->execute(array($uid,$at,$lat,$lng,$acc,$photo,$hash,$lat,$lng,$acc,substr($device,0,250)));
    $sid=(int)db()->lastInsertId();
    db()->prepare("INSERT INTO tracking_points(session_id,user_id,latitude,longitude,accuracy,recorded_at) VALUES(?,?,?,?,?,NOW())")->execute(array($sid,$uid,$lat,$lng,$acc));
    audit($uid,'Check-in jam kerja melalui aplikasi mobile');
    mobileJson(array('ok'=>true,'message'=>'Check-In berhasil. Tracking latar belakang telah diaktifkan.','session_id'=>$sid,'at'=>date('Y-m-d H:i:s')));
} catch(Throwable $e){ mobileJson(array('ok'=>false,'message'=>userError($e)),400); }
