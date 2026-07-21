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
    // Use fileinfo if available, otherwise fall back to the client-provided type
    if (function_exists('finfo_open')) {
      $finfo = finfo_open(FILEINFO_MIME_TYPE);
      $mime = finfo_file($finfo, $file['tmp_name']);
      finfo_close($finfo);
    } else {
      $mime = $file['type'] ?? null;
    }

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
      'thumb' => null,
        'size' => $file['size'],
        'uploaded_at' => date('c')
    ];
    $meta[] = $entry;
    // attempt to generate thumbnail for PDFs
    if ($mime === 'application/pdf') {
      $thumbDir = $uploadsDir . '/thumbs';
      if (!is_dir($thumbDir)) mkdir($thumbDir, 0755, true);
      $thumbName = pathinfo($basename, PATHINFO_FILENAME) . '.png';
      $thumbPath = $thumbDir . '/' . $thumbName;

      $thumbCreated = false;

      // Use Imagick if available
      if (class_exists('Imagick')) {
        try {
          $imagick = new Imagick();
          $imagick->setResolution(150, 150);
          $imagick->readImage($dest . '[0]');
          $imagick->setImageFormat('png');
          $imagick->setImageColorspace(
            defined('Imagick::COLORSPACE_SRGB') ? Imagick::COLORSPACE_SRGB : 1
          );
          $imagick->writeImage($thumbPath);
          $imagick->clear();
          $imagick->destroy();
          $thumbCreated = file_exists($thumbPath);
        } catch (Exception $e) {
          // ignore thumbnail errors
        }
      }

      // Fallback to pdftoppm if available
      if (!$thumbCreated) {
        $pdftoppm = trim(shell_exec('which pdftoppm 2>/dev/null')) ?: null;
        if ($pdftoppm) {
          // generate first page as png
          $cmd = escapeshellcmd($pdftoppm) . ' -singlefile -png ' . escapeshellarg($dest) . ' ' . escapeshellarg($thumbDir . '/' . pathinfo($basename, PATHINFO_FILENAME));
          @exec($cmd);
          if (file_exists($thumbPath)) $thumbCreated = true;
        }
      }

      if ($thumbCreated) {
        // update last appended entry's thumb value
        $meta[count($meta)-1]['thumb'] = 'uploads/cvs/thumbs/' . $thumbName;
      }
    }

    file_put_contents($metaFile, json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    flash('success', 'CV berhasil diunggah. Menampilkan pratinjau...');
    redirect('upload_cv.php?preview=' . rawurlencode($basename));
}

$cvList = [];
if (file_exists($metaFile)) {
    $cvList = json_decode(file_get_contents($metaFile), true) ?: [];
}

$pageTitle = 'Upload CV Publik';
require __DIR__ . '/includes/header.php';
?>

<section class="w-full px-6 py-12 cv-upload bg-white">
  <div class="card card-hero animate-entry w-full">
    <div class="grid grid-cols-1 gap-6 items-start">
      <div class="w-full">
        <h1 class="text-2xl font-bold">Unggah CV Kamu</h1>
        <p class="text-sm text-zinc-500 mt-1">Bagikan CV dalam format PDF/DOC/DOCX. Setelah unggah, pratinjau akan muncul jika file berupa PDF.</p>

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
            <div class="file-input-custom">
              <svg class="icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 3v10" stroke="#059669" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" stroke="#059669" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
              <input type="file" name="cv_file" accept=".pdf,.doc,.docx" required />
            </div>
          </div>
          <div class="flex items-center gap-3">
            <button type="submit" class="btn-tactile bg-accent text-white px-5 py-2.5 rounded-lg">
              <svg class="icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 5v14" stroke="#fff" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M19 12H5" stroke="#fff" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
              Unggah CV
            </button>
            <a href="<?= APP_URL ?>" class="text-sm text-zinc-500">Kembali ke beranda</a>
          </div>
        </form>
      </div>

      <aside class="w-full mt-6">
        <div class="surface rounded-2xl p-5 cv-card">
          <h3 class="text-sm font-semibold">Tips Cepat</h3>
          <ul class="mt-3 text-sm muted space-y-2">
            <li>Gunakan PDF untuk pratinjau langsung.</li>
            <li>Pastikan ukuran di bawah 5MB.</li>
            <li>Hapus data sensitif sebelum mengunggah.</li>
          </ul>
        </div>
      </aside>
    </div>
  </div>

  <?php if (!empty($_GET['preview'])):
    $previewFile = basename($_GET['preview']);
    $meta = [];
    if (file_exists($metaFile)) { $meta = json_decode(file_get_contents($metaFile), true) ?: []; }
    $entry = null;
    foreach ($meta as $m) { if ($m['filename'] === $previewFile) { $entry = $m; break; } }
  ?>
    <div class="cv-preview mt-8">
      <?php if ($entry): ?>
        <div class="surface rounded-3xl p-6">
          <div class="flex items-start gap-6">
            <div class="flex-1">
              <p class="text-sm text-zinc-500">Unggah oleh</p>
              <h2 class="text-lg font-semibold"><?= e($entry['name']) ?></h2>
              <p class="text-xs text-zinc-400 mt-1"><?= e($entry['original']) ?> • <?= date('d M Y H:i', strtotime($entry['uploaded_at'])) ?></p>
            </div>
            <div class="shrink-0">
              <a href="uploads/cvs/<?= rawurlencode($entry['filename']) ?>" class="btn-tactile bg-zinc-900 text-white px-4 py-2 rounded-lg" target="_blank">Buka di tab baru</a>
            </div>
          </div>

          <div class="mt-6">
            <?php if ($entry['mime'] === 'application/pdf'): ?>
              <div class="preview-frame rounded-lg overflow-hidden border border-zinc-200">
                <iframe src="uploads/cvs/<?= rawurlencode($entry['filename']) ?>" style="width:100%;height:650px;border:0;" title="Pratinjau CV"></iframe>
              </div>
            <?php else: ?>
              <p class="text-sm text-zinc-500">Pratinjau hanya tersedia untuk PDF. <a href="uploads/cvs/<?= rawurlencode($entry['filename']) ?>" class="link-accent">Unduh file</a></p>
            <?php endif; ?>
          </div>
        </div>
      <?php else: ?>
        <div class="surface rounded-3xl p-6 text-center"><p class="text-zinc-500">File pratinjau tidak ditemukan.</p></div>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php';
