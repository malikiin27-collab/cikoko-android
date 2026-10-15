<?php
require __DIR__.'/../server-patch/tracking-quality.php';
function point($east,$north,$second,$acc=5){return array('latitude'=>-6.25+$north/111195,'longitude'=>106.85+$east/(111195*cos(deg2rad(-6.25))),'accuracy'=>$acc,'recorded_at'=>date('Y-m-d H:i:s',1700000000+$second));}
function check($ok,$message){if(!$ok)throw new RuntimeException($message);}
$still=array();for($i=0;$i<369;$i++)$still[]=point(($i%3-1)*2,($i%5-2),$i*5);
$r=cikokoGpsRoute($still);check($r['distance_m']==0,'stationary jitter counted as travel');
$walk=array();for($i=0;$i<31;$i++)$walk[]=point($i*5,0,$i*5);
$r=cikokoGpsRoute($walk);check($r['distance_m']>=130&&$r['distance_m']<=151,'slow walking lost or overcounted');
$r=cikokoGpsRoute(array(point(0,0,0),point(100,0,5),point(1,0,10)));
check($r['distance_m']==0&&$r['excluded']==1,'isolated indoor spike counted');
$r=cikokoGpsRoute(array(point(0,0,0),point(1000,0,180)));
check($r['distance_m']==0&&count($r['segments'])==2,'outage connected by invented trip');
$r=cikokoGpsRoute(array(point(0,0,0),point(100,0,5,150),point(150,0,10)));
check($r['distance_m']==0&&$r['excluded']==1,'poor accuracy connected');
$r=cikokoGpsRoute(array(point(0,0,0),point(1000,0,5)));
check($r['distance_m']==0&&count($r['segments'])==2,'impossible jump counted');
$r=cikokoGpsRoute(array(point(0,0,0),point(100,0,0),point(1,0,5)));
check($r['distance_m']==0,'duplicate timestamp counted');
check(!cikokoGpsPoint(array('latitude'=>NAN,'longitude'=>106,'accuracy'=>5)),'NaN accepted');
check(!cikokoGpsPoint(point(0,0,0,0)),'unknown accuracy accepted');
check(!cikokoGpsPoint(point(0,0,0,31)),'poor fix accepted');
check(cikokoGpsRoute(array())['distance_m']==0,'empty route failed');
echo "PASS: 11 route regression cases\n";
