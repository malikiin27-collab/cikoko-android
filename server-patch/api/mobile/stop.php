<?php
declare(strict_types=1);
require __DIR__.'/common.php';
mobileRequireMethod('POST');
$auth=mobileAuth();
$data=mobileRequestData();
try {
    $uid=(int)$auth['uid']; $u=currentEmployee($uid); $active=activeTrackingSession($uid);
    if(!$active) throw new RuntimeException('Tidak ada sesi jam kerja aktif.');
    if(empty($_FILES['photo']['name'])) throw new RuntimeException('Foto Check-Out wajib diambil sebelum tracking dihentikan.');
    $lat=mobileNum($data,'lat'); $lng=mobileNum($data,'lng'); $acc=mobileNum($data,'acc');
    if(!mobileValidPoint($lat,$lng,$acc)||$acc===null||$acc<=0||$acc>30)throw new RuntimeException('Lokasi selesai bertugas belum akurat. Tunggu GPS ±30 meter atau lebih baik.');
    require_once __DIR__.'/tracking_v9.php';trackingRequireCurrentFix($data['fix_epoch']??null);
    $photo=uploadSafe('photo',employeeDir($u).'/tracking',mobileTrackingImageAllowed(),10485760);
    $at=date('Y-m-d H:i:s');
    try {$hash=cikokoSealPhoto(employeeDir($u).'/tracking/'.$photo,'CHECK-OUT',$u,$at,$lat,$lng);}catch(Throwable $e){@unlink(employeeDir($u).'/tracking/'.$photo);throw $e;}
    db()->prepare("UPDATE tracking_sessions SET checkout_at=?,checkout_lat=?,checkout_lng=?,checkout_accuracy=?,checkout_photo=?,checkout_photo_sha256=?,last_lat=?,last_lng=?,last_accuracy=?,last_seen_at=NOW(),status='closed' WHERE id=? AND user_id=?")->execute(array($at,$lat,$lng,$acc,$photo,$hash,$lat,$lng,$acc,(int)$active['id'],$uid));
    db()->prepare("INSERT INTO tracking_points(session_id,user_id,latitude,longitude,accuracy) VALUES(?,?,?,?,?)")->execute(array((int)$active['id'],$uid,$lat,$lng,$acc));
    audit($uid,'Check-out jam kerja melalui aplikasi mobile');
    mobileJson(array('ok'=>true,'message'=>'Check-Out berhasil. Tracking latar belakang telah dihentikan dan foto disimpan.','at'=>date('Y-m-d H:i:s')));
} catch(Throwable $e){ mobileJson(array('ok'=>false,'message'=>userError($e)),400); }
