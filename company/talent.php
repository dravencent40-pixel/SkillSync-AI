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
  <div class="mt-8 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 animate-entry">
    <?php foreach ($talents as $t): ?>
    <div class="cv-card">
      <div class="flex items-start gap-3">
        <?php if (!empty($t['thumb']) && file_exists(__DIR__ . '/../' . $t['thumb'])): ?>
          <img src="<?= APP_URL ?>/<?= ltrim($t['thumb'], '/') ?>" alt="thumb" class="w-12 h-12 rounded-md object-cover" />
        <?php else: ?>
          <span class="w-12 h-12 rounded-full bg-accent-light text-accent-dark grid place-items-center text-sm font-semibold"><?= e(initials($t['name'])) ?></span>
        <?php endif; ?>
        <div class="flex-1">
          <div class="flex items-center justify-between gap-3">
            <div>
              <p class="font-semibold text-zinc-900"><?= e($t['name']) ?></p>
              <p class="meta"><?= e($t['original'] ?? '') ?></p>
            </div>
            <div class="text-right">
              <div class="file-badge"><?= strtoupper(pathinfo($t['original'] ?? ($t['filename'] ?? ''), PATHINFO_EXTENSION) ?: 'PDF') ?></div>
            </div>
          </div>
          <div class="mt-3 flex items-center justify-between">
            <p class="text-xs muted"><?= date('d M Y H:i', strtotime($t['uploaded_at'])) ?></p>
            <div class="flex items-center gap-3">
              <a href="#" data-preview="<?= APP_URL ?>/uploads/cvs/<?= rawurlencode($t['filename']) ?>" data-meta="<?= e($t['name']) ?> — <?= e($t['original']) ?>" class="text-sm text-accent">Pratinjau</a>
              <a href="<?= APP_URL ?>/uploads/cvs/<?= rawurlencode($t['filename']) ?>" download class="text-sm text-zinc-600">Unduh</a>
            </div>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
