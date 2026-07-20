<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

$uploadsDir = __DIR__ . '/uploads/cvs';
$metaFile = $uploadsDir . '/metadata.json';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if (empty($name) || empty($_FILES['cv_file']['name'])) {
        flash('error', 'Isi nama dan pilih file CV.');
        redirect('upload_cv.php');
    }

    $file = $_FILES['cv_file'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        flash('error', 'Gagal mengunggah file. Coba lagi.');
        redirect('upload_cv.php');
    }

    $allowed = ['application/pdf','application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowed)) {
        flash('error', 'Hanya file PDF / DOC / DOCX yang diperbolehkan.');
        redirect('upload_cv.php');
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        flash('error', 'Ukuran file maksimal 5MB.');
        redirect('upload_cv.php');
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $basename = bin2hex(random_bytes(8)) . '_' . time() . '.' . $ext;
    $dest = $uploadsDir . '/' . $basename;

    if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0755, true);

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        flash('error', 'Gagal menyimpan file. Periksa izin direktori.');
        redirect('upload_cv.php');
    }

    $meta = [];
    if (file_exists($metaFile)) {
        $meta = json_decode(file_get_contents($metaFile), true) ?: [];
    }
    $entry = [
        'id' => bin2hex(random_bytes(6)),
        'name' => $name,
        'email' => $email,
        'filename' => $basename,
        'original' => $file['name'],
        'mime' => $mime,
        'size' => $file['size'],
        'uploaded_at' => date('c')
    ];
    $meta[] = $entry;
    file_put_contents($metaFile, json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    flash('success', 'CV berhasil diunggah. Terima kasih!');
    redirect('upload_cv.php');
}

$cvList = [];
if (file_exists($metaFile)) {
    $cvList = array_reverse(json_decode(file_get_contents($metaFile), true) ?: []);
}

$pageTitle = 'Upload CV Publik';
require __DIR__ . '/includes/header.php';
?>

<section class="max-w-4xl mx-auto px-6 py-12 cv-upload">
  <div class="card">
    <h1 class="text-2xl font-bold">Upload CV untuk Dilihat Publik</h1>
    <p class="text-sm text-zinc-500 mt-1">Siapa saja dapat mengunggah CV. CV akan tersedia untuk dilihat dan diunduh.</p>

    <form method="POST" enctype="multipart/form-data" class="mt-6 grid grid-cols-1 gap-4">
      <div class="flex flex-col gap-2">
        <label class="text-sm font-medium">Nama</label>
        <input name="name" required class="border border-zinc-200 rounded-lg px-3.5 py-2.5 text-sm" />
      </div>
      <div class="flex flex-col gap-2">
        <label class="text-sm font-medium">Email (opsional)</label>
        <input name="email" type="email" class="border border-zinc-200 rounded-lg px-3.5 py-2.5 text-sm" />
      </div>
      <div class="flex flex-col gap-2">
        <label class="text-sm font-medium">Pilih File CV (PDF/DOC/DOCX, max 5MB)</label>
        <input type="file" name="cv_file" accept=".pdf,.doc,.docx" required />
      </div>
      <div class="flex items-center gap-3">
        <button type="submit" class="btn-tactile bg-accent text-white px-5 py-2.5 rounded-lg">Unggah CV</button>
        <a href="<?= APP_URL ?>" class="text-sm text-zinc-500">Kembali ke beranda</a>
      </div>
    </form>
  </div>

  <div class="mt-8">
    <h2 class="text-lg font-bold">CV Terbaru</h2>
    <?php if (empty($cvList)): ?>
      <div class="surface rounded-3xl p-8 mt-4 text-center"><p class="text-zinc-500">Belum ada CV yang diunggah.</p></div>
    <?php else: ?>
      <div class="cv-list mt-4">
        <?php foreach ($cvList as $c): ?>
          <div class="cv-card">
            <div class="flex items-center justify-between">
              <div>
                <p class="font-semibold"><?= e($c['name']) ?></p>
                <p class="meta"><?= e($c['original']) ?> • <?= date('d M Y H:i', strtotime($c['uploaded_at'])) ?></p>
              </div>
              <div class="actions">
                <a href="uploads/cvs/<?= rawurlencode($c['filename']) ?>" target="_blank" class="text-sm text-accent">Lihat</a>
                <a href="uploads/cvs/<?= rawurlencode($c['filename']) ?>" download class="text-sm text-zinc-600">Unduh</a>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php';
