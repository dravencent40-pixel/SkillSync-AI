<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

$metaFile = __DIR__ . '/../uploads/cvs/metadata.json';
$talents = [];
if (file_exists($metaFile)) {
  $talents = array_reverse(json_decode(file_get_contents($metaFile), true) ?: []);
}

$pageTitle = 'Talent Pool';
require __DIR__ . '/../includes/header.php';
?>

<section class="max-w-7xl mx-auto px-6 py-10">
  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-3xl font-bold tracking-tight text-zinc-900">Talent Pool</h1>
      <p class="mt-1 text-sm text-zinc-500">Kumpulan CV yang diunggah pengguna.</p>
    </div>
    <a href="<?= APP_URL ?>/upload_cv.php" class="btn-tactile bg-zinc-900 text-white px-4 py-2 rounded-lg">Unggah CV</a>
  </div>

  <?php if (empty($talents)): ?>
    <div class="mt-10 surface rounded-3xl p-14 text-center">
      <p class="text-zinc-500 text-sm">Belum ada CV yang diunggah.</p>
    </div>
  <?php else: ?>
  <div class="mt-8 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach ($talents as $t): ?>
    <div class="cv-card">
      <div class="flex items-center gap-3">
        <span class="w-12 h-12 rounded-full bg-accent-light text-accent-dark grid place-items-center text-sm font-semibold"><?= e(initials($t['name'])) ?></span>
        <div class="flex-1">
          <p class="font-semibold text-zinc-900"><?= e($t['name']) ?></p>
          <p class="text-xs text-zinc-400 mt-1"><?= date('d M Y H:i', strtotime($t['uploaded_at'])) ?></p>
        </div>
      </div>
      <div class="mt-4 flex items-center justify-between">
        <a href="<?= APP_URL ?>/uploads/cvs/<?= rawurlencode($t['filename']) ?>" target="_blank" class="text-sm text-accent">Lihat</a>
        <a href="<?= APP_URL ?>/uploads/cvs/<?= rawurlencode($t['filename']) ?>" download class="text-sm text-zinc-600">Unduh</a>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
