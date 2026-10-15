<?php
require dirname(__DIR__).'/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store, private');
try{
    requireAdmin();
    ensureShiftPhotoSchema();
    $id=(int)($_GET['id']??0);
    if($id<=0) throw new RuntimeException('Sesi jam kerja tidak valid.');
    $s=db()->prepare("SELECT s.*,u.nama_lengkap,u.nomor_pegawai,u.role,u.folder_code FROM tracking_sessions s JOIN users u ON u.id=s.user_id WHERE s.id=? LIMIT 1");
    $s->execute(array($id));
    $row=$s->fetch();
    if(!$row) throw new RuntimeException('Riwayat sesi jam kerja tidak ditemukan.');
    $q=db()->prepare("SELECT latitude,longitude,accuracy,recorded_at FROM tracking_points WHERE session_id=? ORDER BY recorded_at ASC,id ASC LIMIT 12000");
    $q->execute(array($id));
    $points=$q->fetchAll();
    require_once dirname(__DIR__).'/tracking-quality.php';
    $route=cikokoGpsRoute($points);$distance=$route['distance_m'];
    $duration='-';
    if(!empty($row['checkin_at'])){
        $end=!empty($row['checkout_at'])?strtotime((string)$row['checkout_at']):time();
        $start=strtotime((string)$row['checkin_at']);
        if($start&&$end>=$start){$sec=$end-$start;$h=intdiv($sec,3600);$m=intdiv($sec%3600,60);$duration=$h.' jam '.$m.' menit';}
    }
    $out=array(
        'id'=>(int)$row['id'],
        'nama_lengkap'=>(string)$row['nama_lengkap'],
        'nomor_pegawai'=>(string)($row['nomor_pegawai']??''),
        'role_label'=>roleLabel((string)$row['role']),
        'status'=>(string)$row['status'],
        'status_label'=>statusLabel((string)$row['status']),
        'checkin_at'=>(string)$row['checkin_at'],
        'checkout_at'=>(string)($row['checkout_at']??''),
        'checkin_lat'=>(float)$row['checkin_lat'],'checkin_lng'=>(float)$row['checkin_lng'],'checkin_accuracy'=>(float)($row['checkin_accuracy']??0),
        'checkout_lat'=>$row['checkout_lat']!==null?(float)$row['checkout_lat']:null,'checkout_lng'=>$row['checkout_lng']!==null?(float)$row['checkout_lng']:null,'checkout_accuracy'=>$row['checkout_accuracy']!==null?(float)$row['checkout_accuracy']:null,
        'last_lat'=>(float)$row['last_lat'],'last_lng'=>(float)$row['last_lng'],'last_accuracy'=>(float)($row['last_accuracy']??0),'last_seen_at'=>(string)$row['last_seen_at'],
        'device_info'=>(string)($row['device_info']??''),
        'no_hp'=>(string)(currentEmployee((int)$row['user_id'])['no_hp']??''),
        'point_count'=>count($points),
        'shift_photo_counts'=>cikokoShiftPhotoCounts((int)$row['user_id'],$id),
        'shift_photo_total'=>array_sum(cikokoShiftPhotoCounts((int)$row['user_id'],$id)),
        'distance_m'=>round($distance,1),
        'route_segments'=>$route['segments'],'route_excluded'=>$route['excluded'],
        'duration'=>$duration,
        'checkin_photo_url'=>trackingPhotoUrl((string)$row['folder_code'],$row['checkin_photo']??null),
        'checkout_photo_url'=>trackingPhotoUrl((string)$row['folder_code'],$row['checkout_photo']??null),
        'checkin_maps'=>mapsUrl($row['checkin_lat'],$row['checkin_lng']),
        'checkout_maps'=>($row['checkout_lat']!==null&&$row['checkout_lng']!==null)?mapsUrl($row['checkout_lat'],$row['checkout_lng']):'',
        'points'=>$points
    );
    echo json_encode(array('ok'=>true,'session'=>$out),JSON_UNESCAPED_SLASHES);
}catch(Throwable $e){http_response_code(400);echo json_encode(array('ok'=>false,'message'=>userError($e)));}
