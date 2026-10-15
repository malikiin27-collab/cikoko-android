<?php
declare(strict_types=1);require __DIR__.'/common.php';require __DIR__.'/tracking_v9.php';mobileRequireMethod('POST');
try{
 $auth=mobileAuth();$data=mobileRequestData();
 $uid=(int)$auth['uid'];$points=$data['points']??null;
 if(!is_array($points)||count($points)<1||count($points)>100)throw new RuntimeException('Batch GPS harus berisi 1 sampai 100 titik.');
 ensureTrackingV9();$pdo=db();$ack=array();$rejected=array();$accepted=0;
 $pdo->beginTransaction();
 foreach($points as $p){
  if(!is_array($p))throw new RuntimeException('Format titik tidak valid.');
  $pid=(string)($p['id']??'');if(!preg_match('/^[a-f0-9-]{36}$/D',$pid))throw new RuntimeException('ID titik tidak valid.');
  $sid=(int)($p['session_id']??0);$epoch=(int)($p['fix_epoch']??0);$lat=mobileNum($p,'lat');$lng=mobileNum($p,'lng');$acc=mobileNum($p,'acc');$speed=mobileNum($p,'speed')??0;
  if(!mobileValidPoint($lat,$lng,$acc)||$acc===null||!is_finite($lat)||!is_finite($lng)||!is_finite($acc)||!is_finite($speed)||$speed<0||$speed>100){$ack[]=$pid;$rejected[$pid]='invalid';continue;}
  $hash=hash('sha256',json_encode(array($sid,$epoch,$lat,$lng,$acc,$speed)));
  $q=$pdo->prepare('SELECT payload_hash FROM mobile_tracking_receipts WHERE user_id=? AND point_id=?');$q->execute(array($uid,$pid));$receipt=$q->fetchColumn();
  if($receipt!==false){$ack[]=$pid;if(!hash_equals((string)$receipt,$hash))$rejected[$pid]='id_conflict';continue;}
  $q=$pdo->prepare('SELECT * FROM tracking_sessions WHERE id=? AND user_id=? FOR UPDATE');$q->execute(array($sid,$uid));$s=$q->fetch();
  if(!$s){$ack[]=$pid;$rejected[$pid]='session';continue;}
  $start=strtotime((string)$s['checkin_at']);$end=empty($s['checkout_at'])?time()+30:strtotime((string)$s['checkout_at']);
  if($epoch<time()-86400||$epoch>time()+30||$epoch<$start||$epoch>$end||$acc<=0||$acc>30){$ack[]=$pid;$rejected[$pid]='time_or_accuracy';continue;}
  $q=$pdo->prepare('SELECT * FROM tracking_live_quality WHERE session_id=?');$q->execute(array($sid));$quality=$q->fetch();
  if($quality&&$epoch>(int)$quality['fix_epoch']){
   $delta=$epoch-(int)$quality['fix_epoch'];$distance=trackingV9Distance((float)$s['last_lat'],(float)$s['last_lng'],$lat,$lng);
   if($s['status']==='active'&&$distance>45*$delta+$acc+(float)$quality['accuracy']){$ack[]=$pid;$rejected[$pid]='jump';continue;}
  }
  $pdo->prepare('INSERT INTO mobile_tracking_receipts(user_id,point_id,session_id,fix_epoch,payload_hash) VALUES(?,?,?,?,?)')->execute(array($uid,$pid,$sid,$epoch,$hash));
  $pdo->prepare('INSERT INTO tracking_points(session_id,user_id,latitude,longitude,accuracy,recorded_at) VALUES(?,?,?,?,?,?)')->execute(array($sid,$uid,$lat,$lng,$acc,date('Y-m-d H:i:s',$epoch)));
  if($s['status']==='active'&&($epoch>($quality?(int)$quality['fix_epoch']:strtotime((string)$s['last_seen_at'])))){
   $pdo->prepare('UPDATE tracking_sessions SET last_lat=?,last_lng=?,last_accuracy=?,last_seen_at=NOW() WHERE id=? AND user_id=? AND status=\'active\'')->execute(array($lat,$lng,$acc,$sid,$uid));
   $pdo->prepare('INSERT INTO tracking_live_quality(session_id,fix_epoch,accuracy,speed,received_at) VALUES(?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE fix_epoch=VALUES(fix_epoch),accuracy=VALUES(accuracy),speed=VALUES(speed),received_at=NOW()')->execute(array($sid,$epoch,$acc,$speed));
  }
  $ack[]=$pid;$accepted++;
 }
 $pdo->commit();mobileJson(array('ok'=>true,'ack'=>$ack,'accepted'=>$accepted,'rejected'=>$rejected,'server_epoch'=>time()));
}catch(Throwable $e){if(isset($pdo)&&$pdo->inTransaction())$pdo->rollBack();error_log('Cikoko GPS batch: '.$e->getMessage());mobileJson(array('ok'=>false,'code'=>'GPS_BATCH_ERROR','message'=>'Server belum menyimpan batch GPS. Periksa log server.'),500);}
