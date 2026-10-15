<?php
require dirname(__DIR__).'/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
$me=requireLogin();$u=currentEmployee((int)$me['id']);
if(!in_array(($u['role']??''),array('pjlp','pppk'),true)){http_response_code(403);echo json_encode(array('ok'=>false,'message'=>'Fitur jam kerja dan lokasi hanya tersedia untuk pegawai PJLP / PPSU dan PPPK Paruh Waktu.'));exit;}
ensureShiftPhotoSchema();
$action=(string)($_POST['action']??$_GET['action']??'status');
function tnum($k){$v=$_POST[$k]??null;return is_numeric($v)?(float)$v:null;}
function validPoint($lat,$lng,$acc){if($lat===null||$lng===null||!is_finite($lat)||!is_finite($lng)||($acc!==null&&!is_finite($acc)))return false;if($lat<-90||$lat>90||$lng<-180||$lng>180)return false;if($acc!==null&&($acc<0||$acc>2000))return false;return true;}
function trackingImageAllowed(){return array('image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp');}
try{
 if($_SERVER['REQUEST_METHOD']==='POST')verifyCsrf();
 if($action!=='status'&&$_SERVER['REQUEST_METHOD']!=='POST')throw new RuntimeException('Gunakan POST untuk perubahan sesi.');
 if($action==='start'){
  require_once dirname(__DIR__).'/api/mobile/tracking_v9.php';trackingRequireCurrentFix($_POST['fix_epoch']??null);
  $lat=tnum('lat');$lng=tnum('lng');$acc=tnum('acc');if(!validPoint($lat,$lng,$acc))throw new RuntimeException('Lokasi tidak valid. Aktifkan GPS dan coba kembali.');
  if($acc===null||$acc<=0||$acc>30)throw new RuntimeException('Akurasi lokasi masih rendah (±'.round($acc).' m). Tunggu hingga GPS lebih akurat lalu coba kembali.');
  $active=activeTrackingSession((int)$u['id']);
  if(!$active){
    if(empty($_FILES['photo']['name']))throw new RuntimeException('Foto mulai bertugas wajib diambil agar dokumentasi posisi kerja tersimpan.');
    $dir=employeeDir($u).'/tracking';
    $photo=uploadSafe('photo',$dir,trackingImageAllowed(),10485760);
    $at=date('Y-m-d H:i:s');
    try {$hash=cikokoAcceptPhoto($dir.'/'.$photo,'CHECK-IN',$u,$at,$lat,$lng,(string)($_POST['photo_stamp_token']??''));}catch(Throwable $e){@unlink($dir.'/'.$photo);throw $e;}
    $s=db()->prepare("INSERT INTO tracking_sessions(user_id,checkin_at,checkin_lat,checkin_lng,checkin_accuracy,checkin_photo,checkin_photo_sha256,last_lat,last_lng,last_accuracy,last_seen_at,status,device_info) VALUES(?,?,?,?,?,?,?,?,?,?,NOW(),'active',?)");
    $s->execute(array((int)$u['id'],$at,$lat,$lng,$acc,$photo,$hash,$lat,$lng,$acc,substr((string)($_SERVER['HTTP_USER_AGENT']??''),0,250)));
    $sid=(int)db()->lastInsertId();
    db()->prepare("INSERT INTO tracking_points(session_id,user_id,latitude,longitude,accuracy) VALUES(?,?,?,?,?)")->execute(array($sid,(int)$u['id'],$lat,$lng,$acc));
    audit((int)$u['id'],'Mulai bertugas jam kerja ' . roleLabel((string)$u['role']));
  } else $sid=(int)$active['id'];
  echo json_encode(array('ok'=>true,'message'=>'Mulai bertugas berhasil. Posisi dan foto mulai bertugas telah disimpan, pembaruan lokasi jam kerja sekarang aktif.','session_id'=>$sid,'at'=>date('Y-m-d H:i:s')));exit;
 }
 if($action==='point'){
  $active=activeTrackingSession((int)$u['id']);if(!$active)throw new RuntimeException('Belum ada sesi jam kerja yang aktif.');
  $lat=tnum('lat');$lng=tnum('lng');$acc=tnum('acc');if(!validPoint($lat,$lng,$acc))throw new RuntimeException('Lokasi tidak valid.');
  require_once dirname(__DIR__).'/api/mobile/tracking_v9.php';
  $epoch=isset($_POST['fix_epoch'])&&is_numeric($_POST['fix_epoch'])?(int)$_POST['fix_epoch']:null;
  $result=trackingSavePoint((int)$u['id'],(int)$active['id'],$lat,$lng,$acc,$epoch);
  echo json_encode(array_merge(array('ok'=>true,'message'=>'Lokasi diproses.'),$result));exit;
 }
 if($action==='stop'){
  $active=activeTrackingSession((int)$u['id']);if(!$active)throw new RuntimeException('Tidak ada sesi jam kerja yang aktif.');
  if(empty($_FILES['photo']['name']))throw new RuntimeException('Foto selesai bertugas wajib diambil sebelum sesi jam kerja diselesaikan.');
  $lat=tnum('lat');$lng=tnum('lng');$acc=tnum('acc');if(!validPoint($lat,$lng,$acc)||$acc===null||$acc<=0||$acc>30)throw new RuntimeException('Lokasi selesai bertugas belum akurat. Tunggu GPS ±30 meter atau lebih baik.');
  require_once dirname(__DIR__).'/api/mobile/tracking_v9.php';trackingRequireCurrentFix($_POST['fix_epoch']??null);
  $dir=employeeDir($u).'/tracking';
  $photo=uploadSafe('photo',$dir,trackingImageAllowed(),10485760);
  $at=date('Y-m-d H:i:s');
  try {$hash=cikokoAcceptPhoto($dir.'/'.$photo,'CHECK-OUT',$u,$at,$lat,$lng,(string)($_POST['photo_stamp_token']??''));}catch(Throwable $e){@unlink($dir.'/'.$photo);throw $e;}
  db()->prepare("UPDATE tracking_sessions SET checkout_at=?,checkout_lat=?,checkout_lng=?,checkout_accuracy=?,checkout_photo=?,checkout_photo_sha256=?,last_lat=?,last_lng=?,last_accuracy=?,last_seen_at=NOW(),status='closed' WHERE id=?")->execute(array($at,$lat,$lng,$acc,$photo,$hash,$lat,$lng,$acc,(int)$active['id']));
  db()->prepare("INSERT INTO tracking_points(session_id,user_id,latitude,longitude,accuracy) VALUES(?,?,?,?,?)")->execute(array((int)$active['id'],(int)$u['id'],$lat,$lng,$acc));
  audit((int)$u['id'],'Selesai bertugas jam kerja ' . roleLabel((string)$u['role']));
  echo json_encode(array('ok'=>true,'message'=>'Selesai bertugas berhasil. Posisi terakhir dan foto selesai bertugas telah disimpan sebagai dokumentasi sesi kerja.','at'=>date('Y-m-d H:i:s')));exit;
 }
 $active=activeTrackingSession((int)$u['id']);
 if($active){
  require_once dirname(__DIR__).'/api/mobile/tracking_v9.php';ensureTrackingV9();
  $qualityQuery=db()->prepare('SELECT fix_epoch FROM tracking_live_quality WHERE session_id=?');$qualityQuery->execute(array((int)$active['id']));$fix=$qualityQuery->fetchColumn();
  $active['gps_fix_at']=$fix!==false?date('Y-m-d H:i:s',(int)$fix):null;
 }
 echo json_encode(array('ok'=>true,'active'=>(bool)$active,'session'=>$active?:null));
}catch(Throwable $e){http_response_code(400);echo json_encode(array('ok'=>false,'message'=>userError($e)));}
