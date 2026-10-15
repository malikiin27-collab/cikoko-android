<?php
require_once __DIR__.'/formal-routes.php';
if (!function_exists('ui_icon')) {
function ui_icon(string $name): string {
    $icons = array(
        'home'=>'<path d="M3 11.5 12 4l9 7.5v8a1.5 1.5 0 0 1-1.5 1.5H15v-6H9v6H4.5A1.5 1.5 0 0 1 3 19.5z"/>',
        'users'=>'<circle cx="9" cy="8" r="3"/><circle cx="17" cy="9" r="2.5"/><path d="M3 20c0-4 2.8-7 6-7s6 3 6 7M14 14c3.6 0 7 2.2 7 6"/>',
        'user'=>'<circle cx="12" cy="8" r="4"/><path d="M4 21c0-5 3.6-8 8-8s8 3 8 8"/>',
        'calendar'=>'<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 3v4M17 3v4M3 10h18"/>',
        'heart'=>'<path d="M20.8 4.7a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.5a5.5 5.5 0 0 0 0-7.8z"/><path d="M8 12h2l1-3 2 6 1-3h3"/>',
        'report'=>'<path d="M6 2h9l5 5v15H6z"/><path d="M14 2v6h6M9 13h8M9 17h6"/>',
        'folder'=>'<path d="M3 6h7l2 2h9v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>',
        'gear'=>'<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1-2.8 2.8-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.6v.2h-4V21a1.7 1.7 0 0 0-1-1.6 1.7 1.7 0 0 0-1.9.3l-.1.1L4.2 17l.1-.1a1.7 1.7 0 0 0 .3-1.9A1.7 1.7 0 0 0 3 14H2.8v-4H3a1.7 1.7 0 0 0 1.6-1 1.7 1.7 0 0 0-.3-1.9L4.2 7 7 4.2l.1.1a1.7 1.7 0 0 0 1.9.3A1.7 1.7 0 0 0 10 3V2.8h4V3a1.7 1.7 0 0 0 1 1.6 1.7 1.7 0 0 0 1.9-.3l.1-.1L19.8 7l-.1.1a1.7 1.7 0 0 0-.3 1.9 1.7 1.7 0 0 0 1.6 1h.2v4H21a1.7 1.7 0 0 0-1.6 1z"/>',
        'logout'=>'<path d="M10 17l5-5-5-5M15 12H3M14 3h5a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-5"/>',
        'pin'=>'<path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0z"/><circle cx="12" cy="10" r="2.5"/>',
        'search'=>'<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
        'bell'=>'<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>',
        'plus'=>'<path d="M12 5v14M5 12h14"/>',
        'check'=>'<path d="m5 12 4 4L19 6"/>',
        'upload'=>'<path d="M12 16V4M7 9l5-5 5 5M4 20h16"/>',
        'copy'=>'<rect x="8" y="8" width="12" height="12" rx="2"/><path d="M16 8V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h3"/>',
        'menu'=>'<path d="M4 7h16M4 12h16M4 17h16"/>',
        'camera'=>'<path d="M4 7h4l2-3h4l2 3h4v13H4z"/><circle cx="12" cy="13" r="4"/>',
        'clock'=>'<circle cx="12" cy="12" r="9"/><path d="M12 7v6l4 2"/>',
        'track'=>'<circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3"/><circle cx="12" cy="12" r="8"/>',
        'chevron'=>'<path d="m9 18 6-6-6-6"/>',
        'shield'=>'<path d="M12 3l8 3v6c0 5-3.4 8.2-8 9-4.6-.8-8-4-8-9V6z"/><path d="m9 12 2 2 4-4"/>',
        'info'=>'<circle cx="12" cy="12" r="9"/><path d="M12 11v6M12 7h.01"/>',
        'file'=>'<path d="M6 2h9l5 5v15H6z"/><path d="M14 2v6h6"/>',
        'trash'=>'<path d="M4 7h16M9 7V4h6v3M7 7l1 14h8l1-14M10 11v6M14 11v6"/>',
    );
    $body = $icons[$name] ?? $icons['menu'];
    return '<svg class="ui-icon" viewBox="0 0 24 24" aria-hidden="true">'.$body.'</svg>';
}}

function ui_admin_menu(string $active): array {
    return array(
      array('dashboard','./','home','Dasbor Administrasi'),
      array('pegawai','pegawai.php','users','Data Pegawai'),
      array('pppk','pppk.php','report','Laporan Kinerja PPPK Cikoko'),
      array('pppk_kehadiran','pppk_kehadiran.php','calendar','Kehadiran PPPK'),
      array('cuti','cuti.php','calendar','Verifikasi Cuti'),
      array('sakit','sakit.php','heart','Keterangan Sakit'),
      array('laporan','laporan.php','report','Laporan Lapangan'),
      array('tracking','tracking.php','track','Pemantauan Jam Kerja'),
      array('files','files.php','folder','Dokumen Administrasi'),
      array('aplikasi','aplikasi.php','plus','Instal Aplikasi'),
      array('settings','settings.php','gear','Pengaturan Sistem'),
      array('logout','../logout.php','logout','Keluar'),
    );
}
function ui_employee_menu(string $active, array $u): array {
    $m = array(
      array('dashboard','./','home','Dasbor Pegawai'),
      array('profil','profil.php','user','Profil Pegawai'),
      array('cuti','cuti.php','calendar','Pengajuan Cuti'),
      array('sakit','sakit.php','heart','Keterangan Sakit'),
    );
    if (in_array(($u['role']??''), array('pjlp','pppk'), true)) {
        $m[] = array('aplikasi','aplikasi.php','plus','Instal Aplikasi');
        $m[] = array('tracking','tracking.php','track','Jam Kerja & Lokasi');
    }
    if (in_array(($u['role']??''),array('pjlp','pppk'),true)) $m[] = array('laporan','laporan.php','report','Laporan Kegiatan');
    if (($u['role']??'') === 'pppk') $m[] = array('pppk','pppk.php','report','Laporan Kinerja PPPK');
    if (($u['role']??'') === 'pppk') $m[] = array('pppk_kehadiran','pppk_kehadiran.php','calendar','Kehadiran PPPK');
    $m[] = array('logout','../logout.php','logout','Keluar');
    return $m;
}

function ui_sidebar(array $items, string $active): void { ?>
<aside class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <img class="official-logo" src="../assets/jaya-raya.png" alt="Lambang Jaya Raya">
    <div class="brand-copy"><strong>Kelurahan Cikoko</strong><span>Kecamatan Pancoran</span><small>Administrasi Pegawai</small></div>
  </div>
  <nav class="side-nav" aria-label="Navigasi utama">
    <?php foreach($items as $i): ?>
      <a class="<?= $i[0] === $active ? 'active' : '' ?>" href="<?=h(formalHref($i[1]))?>"><?=ui_icon($i[2])?><span><?=h($i[3])?></span></a>
    <?php endforeach; ?>
  </nav>
  <div class="sidebar-footer">
    <div class="sidebar-footer-title">Kelurahan Cikoko</div>
    <div>Kelurahan Cikoko<br>Pancoran, Jakarta Selatan</div>
  </div>
</aside>
<?php }

function ui_topbar(string $name, string $role, string $photoUrl='', bool $showPwa=false, string $logoutUrl='../logout.php'): void { ?>
<header class="main-topbar">
  <div class="topbar-left">
    <button type="button" class="mobile-menu" data-sidebar-toggle aria-label="Buka menu"><?=ui_icon('menu')?></button>
    <div class="topbar-identity">
      <img src="../assets/jaya-raya.png" alt="Jaya Raya">
      <div><strong>Sistem Administrasi Pegawai</strong><span>Kelurahan Cikoko · Kecamatan Pancoran</span></div>
    </div>
  </div>
  <div class="topbar-actions">
    <?php if($showPwa):?><button type="button" class="pwa-install-top" data-pwa-install hidden><?=ui_icon('plus')?><span>Pasang PWA</span></button><?php endif;?>
    <div class="search-pill"><?=ui_icon('search')?><input type="search" placeholder="Cari pada halaman ini..." data-global-search aria-label="Cari pada halaman"></div>
    <div class="user-chip"><div class="avatar-circle<?= $photoUrl!==''?' has-photo':'' ?>"><?php if($photoUrl!==''):?><img src="<?=h($photoUrl)?>" alt="Foto profil <?=h($name)?>"><?php else:?><?=h(strtoupper(substr($name,0,1)))?><?php endif;?></div><div><strong><?php if((me()['role']??'')==='admin'):?><a href="<?=h(formalHref('../admin/settings.php'))?>" style="color:inherit;text-decoration:none" title="Ubah nama admin"><?=h($name)?></a><?php else:?><?=h($name)?><?php endif;?></strong><span><?=h($role)?></span></div></div>
    <a class="topbar-logout" href="<?=h(formalHref($logoutUrl))?>" aria-label="Keluar"><?=ui_icon('logout')?><span>Keluar</span></a>
  </div>
</header>
<?php }

function ui_begin(string $title, string $active, array $user, bool $admin=true): void {
    $role = $admin ? 'Pengelola Layanan Kelurahan' : roleLabel($user['role'] ?? '');
    ?><!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="csrf-token" content="<?=h(csrfToken())?>"><meta name="app-base-url" content="<?=h(BASE_URL)?>"><meta name="theme-color" content="#0b4b39"><meta name="apple-mobile-web-app-capable" content="yes"><meta name="apple-mobile-web-app-status-bar-style" content="default"><meta name="apple-mobile-web-app-title" content="Cikoko Pegawai"><link rel="manifest" href="../manifest.php"><link rel="apple-touch-icon" href="../assets/icons/apple-touch-icon.png"><title><?=h($title)?> - Kelurahan Cikoko</title><script>if(navigator.userAgent.indexOf("CikokoAndroid/")!==-1||navigator.standalone===true||window.matchMedia("(display-mode: standalone)").matches){document.documentElement.classList.add("cikoko-native");}</script><link rel="stylesheet" href="../assets/style.css?v=1105"><link rel="stylesheet" href="../assets/media-viewer.css?v=1105"><link rel="stylesheet" href="../assets/cikoko-modern-v11.css?v=1206"><link rel="stylesheet" href="../assets/app-v2.css?v=4000"><link rel="stylesheet" media="screen" href="../assets/app-screen-v6.css?v=8000"><style id="dialog-layer-v2">#appDialog{position:fixed!important;inset:0!important;z-index:2147483100!important;isolation:isolate}#appDialog.show{display:flex!important}#appDialog .app-dialog-backdrop{z-index:0!important}#appDialog .app-dialog-panel{position:relative!important;z-index:1!important;max-height:90dvh;overflow:auto}</style><link rel="stylesheet" media="screen" href="/assets/four-responsive-v5.css?v=20261015"><script src="/assets/four-responsive-v5.js?v=20261015" defer></script><script src="/assets/nav-fix-v6.js?v=7" defer></script></head><body class="app-body" data-employee-role="<?=h($admin?'admin':($user['role']??''))?>"><div class="app-shell"><?php
    ui_sidebar($admin ? ui_admin_menu($active) : ui_employee_menu($active,$user), $active);
    $photoUrl = (!$admin && function_exists('employeeProfilePhotoUrl')) ? employeeProfilePhotoUrl($user) : '';
    $GLOBALS['cikoko_ui_is_admin'] = $admin;
    $GLOBALS['cikoko_ui_user_role'] = (string)($user['role'] ?? '');
    $GLOBALS['cikoko_ui_active'] = $active;
    ?><main class="main-area"><?php ui_topbar($user['nama_lengkap'] ?? ($admin ? 'Pengelola Layanan' : 'Pegawai'), $role, $photoUrl, false, '../logout.php'); ?><div class="page-content" id="mainContent"><?php
}

function ui_end(): void { ?>
</div></main></div><div class="sidebar-overlay" data-sidebar-overlay></div>
<?php if(!empty($GLOBALS['cikoko_ui_is_admin'])):
  $adminTab = (string)($GLOBALS['cikoko_ui_active'] ?? 'dashboard');
  $adminNav = array(
    array('dashboard','index.php','home','Dasbor'),
    array('pegawai','pegawai.php','users','Pegawai'),
    array('laporan','laporan.php','report','Laporan'),
    array('tracking','tracking.php','track','Lokasi'),
  ); ?>
<nav class="cikoko-bottom-nav mw-admin-nav" aria-label="Navigasi pengelolaan">
  <?php foreach($adminNav as $entry):?><a href="<?=h(formalHref($entry[1]))?>" class="cikoko-nav-item <?=$entry[0]===$adminTab?'is-active':''?>" <?=$entry[0]===$adminTab?'aria-current="page"':''?>><?=ui_icon($entry[2])?><span><?=h($entry[3])?></span></a><?php endforeach;?>
</nav>
<div class="cikoko-sheet" id="cikokoMoreSheet" hidden aria-hidden="true">
  <div class="cikoko-sheet-shade" data-more-close></div>
  <section class="cikoko-sheet-panel" role="dialog" aria-modal="true" aria-label="Layanan Pengelolaan">
    <div class="cikoko-sheet-handle"></div><h2>Layanan Pengelolaan</h2><p>Pilih layanan administrasi yang diperlukan.</p>
    <a href="cuti.php"><?=ui_icon('calendar')?><span><strong>Verifikasi Cuti</strong><small>Periksa dan tindak lanjuti pengajuan</small></span><?=ui_icon('chevron')?></a>
    <a href="sakit.php"><?=ui_icon('heart')?><span><strong>Keterangan Sakit</strong><small>Kelola surat keterangan pegawai</small></span><?=ui_icon('chevron')?></a>
    <a href="files.php"><?=ui_icon('folder')?><span><strong>Dokumen Administrasi</strong><small>Dokumen yang dapat diakses sesuai kewenangan</small></span><?=ui_icon('chevron')?></a>
    <a href="settings.php"><?=ui_icon('gear')?><span><strong>Pengaturan</strong><small>Kelola kebutuhan operasional</small></span><?=ui_icon('chevron')?></a>
    <button type="button" data-more-full><?=ui_icon('menu')?><span><strong>Seluruh Menu</strong><small>Buka navigasi layanan lengkap</small></span><?=ui_icon('chevron')?></button>
    <button type="button" class="cikoko-sheet-close" data-more-close>Tutup</button>
  </section>
</div>
<?php endif; ?>
<?php if(empty($GLOBALS['cikoko_ui_is_admin'])):
  $currentTab = (string)($GLOBALS['cikoko_ui_active'] ?? '');
  $canWork = in_array((string)($GLOBALS['cikoko_ui_user_role']??''), array('pjlp','pppk'), true);
  $navLinks = array(
    array('profil','profil.php','user','Profil'),
    array('cuti','cuti.php','calendar','Cuti'),
    array('laporan',$canWork?'laporan.php':'index.php','report','Kerja'),
    array('tracking',$canWork?'tracking.php':'index.php','clock','Absen'),
  );
?>
<nav class="cikoko-bottom-nav" aria-label="Navigasi utama pegawai">
  <?php foreach($navLinks as $entry): ?><a href="<?=h(formalHref($entry[1]))?>" class="cikoko-nav-item <?= $entry[0]===$currentTab?'is-active':'' ?>" <?= $entry[0]===$currentTab?'aria-current="page"':'' ?>><?=ui_icon($entry[2])?><span><?=h($entry[3])?></span></a><?php endforeach; ?>
</nav>
<div class="cikoko-sheet" id="cikokoMoreSheet" hidden aria-hidden="true">
  <div class="cikoko-sheet-shade" data-more-close></div>
  <section class="cikoko-sheet-panel" role="dialog" aria-modal="true" aria-label="Layanan Lainnya">
    <div class="cikoko-sheet-handle"></div><h2>Layanan Lainnya</h2><p>Akses layanan tambahan dalam aplikasi.</p>
    <a href="index.php"><?=ui_icon('home')?><span><strong>Dasbor Pegawai</strong><small>Ringkasan informasi dan layanan</small></span><?=ui_icon('chevron')?></a>
    <a href="sakit.php"><?=ui_icon('heart')?><span><strong>Keterangan Sakit</strong><small>Pengajuan dan riwayat keterangan sakit</small></span><?=ui_icon('chevron')?></a>
    <button type="button" data-more-full><?=ui_icon('menu')?><span><strong>Menu Lengkap</strong><small>Lihat seluruh layanan pegawai</small></span><?=ui_icon('chevron')?></button>
    <button type="button" class="cikoko-sheet-close" data-more-close>Tutup</button>
  </section>
</div>
<?php endif; ?>
<div class="cikoko-photo-flow" id="cikokoPhotoFlow" hidden aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="cikokoFlowTitle">
 <div class="cikoko-photo-flow-shade"></div>
 <div class="cikoko-photo-flow-panel compact-flow">
  <div class="cikoko-flow-icon" id="cikokoFlowPreview"><span><?=ui_icon('camera')?></span></div>
  <h2 id="cikokoFlowTitle">Mempersiapkan Foto</h2><p id="cikokoFlowMessage" aria-live="polite">Mohon tunggu. Foto sedang diproses.</p>
  <div class="cikoko-flow-meter"><span id="cikokoFlowPercent">0%</span><div class="cikoko-flow-track"><div id="cikokoFlowTrack"></div></div></div>
  <ol class="cikoko-flow-steps two-steps"><li id="cikokoFlowStep1">Mempersiapkan foto</li><li id="cikokoFlowStep2">Menambahkan cap waktu dan lokasi</li></ol>
  <div class="cikoko-flow-note" id="cikokoFlowNote">Foto siap digunakan setelah cap waktu dan lokasi selesai dibuat.</div>
  <button type="button" id="cikokoFlowClose" class="btn block" hidden>Tutup</button>
 </div>
</div>
<div class="app-dialog" id="appDialog" aria-hidden="true">
  <div class="app-dialog-backdrop" data-dialog-close></div>
  <div class="app-dialog-panel" role="dialog" aria-modal="true" aria-labelledby="appDialogTitle">
    <div class="dialog-status-icon" id="appDialogIcon"><?=ui_icon('info')?></div>
    <div class="dialog-content">
      <h3 id="appDialogTitle">Informasi</h3>
      <p id="appDialogMessage"></p>
      <div class="dialog-detail" id="appDialogDetail" hidden></div>
      <div class="dialog-progress" id="appDialogProgress" hidden><span></span></div>
      <div class="dialog-actions" id="appDialogActions"><button type="button" class="btn light" data-dialog-cancel>Batal</button><button type="button" class="btn" data-dialog-confirm>Lanjutkan</button></div>
    </div>
  </div>
</div>
<script src="../assets/cikoko-modern-v11.js?v=4000"></script><script src="../assets/app-dialog-v2.js?v=20261012-2043"></script><script src="../assets/media-viewer.js?v=6000"></script><script src="../assets/photo-capture-v5.js?v=5003"></script><?php if(empty($GLOBALS['cikoko_ui_is_admin']) && in_array(($GLOBALS['cikoko_ui_user_role']??''),array('pjlp','pppk'),true)):?><script src="../assets/tracking-client.js?v=20261015-23"></script><script src="../assets/native-sync.js?v=20261015-23"></script><?php endif;?><script src="../assets/native-camera-v5.js?v=5003"></script><script src="../assets/install-v8.js?v=8000"></script><script src="../assets/photo-preview.js?v=1"></script><script src="../assets/formal-links.js?v=20261013-1"></script></body></html><?php }

function ui_page_title(string $title, string $subtitle='', string $crumb=''): void { ?>
<div class="page-heading"><div><?php if($crumb):?><div class="breadcrumb"><?=h($crumb)?></div><?php endif;?><h1><?=h($title)?></h1><?php if($subtitle):?><p><?=h($subtitle)?></p><?php endif;?></div></div>
<?php }
?>
