<?php
/** Conservative display estimate. Original GPS records remain untouched. */
function cikokoGpsDistance(array $a, array $b): float {
    $x=sin(deg2rad($b['latitude']-$a['latitude'])/2)**2
        +cos(deg2rad($a['latitude']))*cos(deg2rad($b['latitude']))
        *sin(deg2rad($b['longitude']-$a['longitude'])/2)**2;
    return 12742000*asin(sqrt(max(0,min(1,$x))));
}
function cikokoGpsPoint(array $p): bool {
    foreach(array('latitude','longitude','accuracy') as $k) {
        if(!isset($p[$k])||!is_numeric($p[$k])||!is_finite((float)$p[$k]))return false;
    }
    return abs((float)$p['latitude'])<=90 && abs((float)$p['longitude'])<=180
        && (float)$p['accuracy']>0 && (float)$p['accuracy']<=30;
}
function cikokoGpsRoute(array $points): array {
    $segments=array();$segment=array();$anchor=null;$previous=null;$distance=0.0;$excluded=0;
    $points=array_values($points);
    foreach($points as $index=>$p) {
        $t=strtotime((string)($p['recorded_at']??''));
        if(!cikokoGpsPoint($p)||$t===false) {
            if($segment)$segments[]=$segment;
            $segment=array();$anchor=null;$previous=null;$excluded++;continue;
        }
        if($previous!==null && isset($points[$index+1]) && cikokoGpsPoint($points[$index+1])) {
            $next=$points[$index+1];$nextTime=strtotime((string)($next['recorded_at']??''));
            $prevTime=strtotime($previous['recorded_at']);
            if($nextTime!==false && $t>$prevTime && $nextTime>$t && $nextTime-$prevTime<=20
                && cikokoGpsDistance($previous,$next)<=max(10,$previous['accuracy']+$next['accuracy'])
                && cikokoGpsDistance($previous,$p)>max(20,2*($previous['accuracy']+$p['accuracy']))
                && cikokoGpsDistance($p,$next)>max(20,2*($p['accuracy']+$next['accuracy']))) {
                $excluded++;continue;
            }
        }
        if($previous!==null) {
            $dt=$t-strtotime($previous['recorded_at']);
            if($dt<=0){$excluded++;continue;}
            // Never draw an invented trip across a reception gap or implausible jump.
            if($dt>60||cikokoGpsDistance($previous,$p)>45*$dt+$p['accuracy']+$previous['accuracy']) {
                if($segment)$segments[]=$segment;
                $segment=array();$anchor=null;
            }
        }
        $previous=$p;
        if($anchor===null){$anchor=$p;$segment[]=$p;continue;}
        $d=cikokoGpsDistance($anchor,$p);
        // Compare to the retained anchor, not the previous sample: slow walking
        // accumulates displacement while stationary jitter inside uncertainty does not.
        if($d<=max(10,(float)$anchor['accuracy']+(float)$p['accuracy']))continue;
        // Count displacement only after leaving the uncertainty deadband.
        // This remains an estimate; do not treat it as measured travel distance.
        $distance+=$d;
        $segment[]=$p;$anchor=$p;
    }
    if($segment)$segments[]=$segment;
    return array('distance_m'=>round($distance,1),'segments'=>$segments,'excluded'=>$excluded);
}
