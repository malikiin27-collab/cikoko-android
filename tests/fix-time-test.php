<?php
require __DIR__.'/../server-patch/api/mobile/tracking_v9.php';
trackingRequireCurrentFix(time());
foreach(array(null,'bad',time()-40,time()+60,INF,NAN) as $value){
    $rejected=false;
    try{trackingRequireCurrentFix($value);}catch(RuntimeException $e){$rejected=true;}
    if(!$rejected)throw new RuntimeException('Invalid timestamp was accepted');
}
echo "PASS: 7 check-in/check-out timestamp cases\n";
