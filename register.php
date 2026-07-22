<?php
<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

flash('error', 'Pendaftaran telah dinonaktifkan. Gunakan fitur Upload CV jika ingin membagikan profil.');
redirect('upload_cv.php');

