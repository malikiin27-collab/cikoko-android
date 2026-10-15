<?php
function ensureTrackingV9(): void {
 static $done=false;if($done)return;$done=true;
 db()->exec("CREATE TABLE IF NOT EXISTS mobile_tracking_receipts (user_id INT UNSIGNED NOT NULL,point_id VARCHAR(40) NOT NULL,session_id BIGINT UNSIGNED NOT NULL,fix_epoch BIGINT NOT NULL,payload_hash CHAR(64) NOT NULL,PRIMARY KEY(user_id,point_id),INDEX idx_receipt_session(session_id,fix_epoch)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
 db()->exec("CREATE TABLE IF NOT EXISTS tracking_live_quality (session_id BIGINT UNSIGNED PRIMARY KEY,fix_epoch BIGINT NOT NULL,accuracy DECIMAL(10,2) NOT NULL,speed DECIMAL(10,2) NOT NULL DEFAULT 0,received_at DATETIME NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}
function trackingV9Distance(float $a,float $b,float $c,float $d): float {
 $x=sin(deg2rad($c-$a)/2)**2+cos(deg2rad($a))*cos(deg2rad($c))*sin(deg2rad($d-$b)/2)**2;return 6371000*2*asin(sqrt(min(1,max(0,$x))));
}

// Shared writer: serialize updates and never turn delayed fixes into a live position.
function trackingSavePoint(int $uid, int $sid, float $lat, float $lng, ?float $acc, ?int $epoch): array {
 if(!is_finite($lat)||!is_finite($lng)||$lat < -90||$lat > 90||$lng < -180||$lng > 180||$acc===null||!is_finite($acc)||$acc<=0||$acc>30)throw new RuntimeException('GPS tidak valid atau akurasi belum mencapai ±30 meter.');
 $known=$epoch!==null;$fix=$epoch??time();
 if($fix<time()-86400||$fix>time()+30)throw new RuntimeException('Waktu GPS tidak valid.');
 ensureTrackingV9();$pdo=db();$pdo->beginTransaction();
 try {
  $q=$pdo->prepare('SELECT * FROM tracking_sessions WHERE id=? AND user_id=? FOR UPDATE');$q->execute(array($sid,$uid));$s=$q->fetch();
  if(!$s||$s['status']!=='active')throw new RuntimeException('Sesi kerja sudah berakhir.');
  if($fix<strtotime($s['checkin_at']))throw new RuntimeException('Titik GPS berasal dari sebelum sesi kerja.');
  $q=$pdo->prepare('SELECT * FROM tracking_live_quality WHERE session_id=?');$q->execute(array($sid));$quality=$q->fetch();
  $previous=$quality?(int)$quality['fix_epoch']:strtotime($s['last_seen_at']);
  if($fix<=$previous||$fix-$previous<5){$pdo->commit();return array('throttled'=>true,'at'=>date('Y-m-d H:i:s',$previous));}
  if($quality&&trackingV9Distance((float)$s['last_lat'],(float)$s['last_lng'],$lat,$lng)>45*($fix-$previous)+$acc+(float)$quality['accuracy'])throw new RuntimeException('Perpindahan GPS tidak wajar. Tunggu lokasi lebih akurat.');
  $at=date('Y-m-d H:i:s',$fix);
  $pdo->prepare('INSERT INTO tracking_points(session_id,user_id,latitude,longitude,accuracy,recorded_at) VALUES(?,?,?,?,?,?)')->execute(array($sid,$uid,$lat,$lng,$acc,$at));
  $pdo->prepare("UPDATE tracking_sessions SET last_lat=?,last_lng=?,last_accuracy=?,last_seen_at=? WHERE id=? AND status='active'")->execute(array($lat,$lng,$acc,$at,$sid));
  if($known){$pdo->prepare('INSERT INTO tracking_live_quality(session_id,fix_epoch,accuracy,speed,received_at) VALUES(?,?,?,0,NOW()) ON DUPLICATE KEY UPDATE fix_epoch=VALUES(fix_epoch),accuracy=VALUES(accuracy),received_at=NOW()')->execute(array($sid,$fix,$acc));}
  else {$pdo->prepare('DELETE FROM tracking_live_quality WHERE session_id=?')->execute(array($sid));}
  $pdo->commit();return array('throttled'=>false,'at'=>$at);
 }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}

// Check-in/out must not stamp a cached position as a new measurement.
function trackingRequireCurrentFix($value): void {
 if(!is_numeric($value)||!is_finite((float)$value)||(float)$value<time()-30||(float)$value>time()+5)
  throw new RuntimeException('Lokasi belum diperbarui. Aktifkan GPS, tunggu lokasi akurat, lalu coba kembali.');
}
