<?php
require dirname(__DIR__).'/bootstrap.php';requireLogin();
$file=dirname(__DIR__).'/downloads/kelurahan-cikoko.apk';
if(!is_file($file)){http_response_code(404);exit('Aplikasi belum tersedia. Silakan hubungi pengelola.');}
header('Content-Type: application/vnd.android.package-archive');
header('Content-Disposition: attachment; filename="Cikoko.apk"');
header('Content-Length: '.filesize($file));header('Cache-Control: private, no-store');header('X-Content-Type-Options: nosniff');session_write_close();readfile($file);exit;
