<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_role('siswa');
$user = current_user();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_public'])) {
    $pdo->prepare('UPDATE skill_profiles SET is_public = 1 - is_public WHERE user_id = ?')->execute([$user['id']]);
    redirect('profile.php');
}

$stmt = $pdo->prepare(
    'SELECT sp.*, s.jurusan, s.kelas, s.sekolah FROM skill_profiles sp
     LEFT JOIN student_profiles s ON s.user_id = sp.user_id
     WHERE sp.user_id = ?'
);
$stmt->execute([$user['id']]);
$profile = $stmt->fetch() ?: ['overall_score'=>0,'clean_code_avg'=>0,'security_avg'=>0,'efficiency_avg'=>0,'tasks_completed'=>0,'badge'=>'Pemula','strengths'=>null,'weaknesses'=>null,'is_public'=>1,'jurusan'=>null,'kelas'=>null,'sekolah'=>null];

$historyStmt = $pdo->prepare(
    'SELECT s.id, s.submitted_at, t.title, r.overall_score, r.clean_code_score, r.security_score, r.efficiency_score
     FROM submissions s JOIN tasks t ON t.id = s.task_id
     LEFT JOIN ai_reviews r ON r.submission_id = s.id
     WHERE s.user_id = ? ORDER BY s.submitted_at DESC'
);
$historyStmt->execute([$user['id']]);
$history = $historyStmt->fetchAll();

$pageTitle = 'Profil Skill';
require __DIR__ . '/includes/header.php';
?>

<section class="max-w-4xl mx-auto px-6 py-10">
  <div class="flex flex-col md:flex-row md:items-start justify-between gap-6">
    <div class="flex items-center gap-4">
      <span class="w-16 h-16 rounded-2xl bg-accent-light text-accent-dark grid place-items-center text-xl font-bold"><?= e(initials($user['name'])) ?></span>
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-zinc-900"><?= e($user['name']) ?></h1>
        <p class="text-sm text-zinc-500"><?= e($profile['jurusan'] ?: '-') ?> &middot; <?= e($profile['sekolah'] ?: 'SMKN 9 Bekasi') ?></p>
      </div>
    </div>
    <form method="POST">
      <input type="hidden" name="toggle_public" value="1">
      <button type="submit" class="btn-tactile text-xs font-semibold px-4 py-2 rounded-lg border <?= $profile['is_public'] ? 'border-emerald-300 bg-emerald-50 text-emerald-700' : 'border-zinc-300 bg-zinc-50 text-zinc-600' ?>">
        <?= $profile['is_public'] ? '● Terlihat oleh mitra industri' : '○ Tersembunyi dari mitra' ?>
      </button>
    </form>
  </div>

  <!-- Score summary -->
  <div class="mt-10 grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="surface rounded-3xl p-8 flex items-center gap-6 lg:col-span-1">
      <div class="relative w-24 h-24 shrink-0">
        <svg class="score-ring w-24 h-24" data-score="<?= (int)$profile['overall_score'] ?>" viewBox="0 0 100 100">
          <circle cx="50" cy="50" r="42" fill="none" stroke="#e4e4e7" stroke-width="9"/>
          <circle class="progress" cx="50" cy="50" r="42" fill="none" stroke="#059669" stroke-width="9" stroke-linecap="round"/>
        </svg>
        <div class="absolute inset-0 grid place-items-center">
          <span class="text-xl font-extrabold text-zinc-900"><?= (int)$profile['overall_score'] ?></span>
        </div>
      </div>
      <div>
        <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400">Badge</p>
        <p class="mt-1 text-lg font-bold text-zinc-900"><?= e($profile['badge']) ?></p>
      </div>
    </div>
    <div class="lg:col-span-2 surface rounded-3xl p-8 grid grid-cols-3 divide-x divide-zinc-200">
      <div class="px-4 text-center first:pl-0">
        <p class="text-2xl font-extrabold <?= score_color_class((int)$profile['clean_code_avg']) ?>"><?= (int)$profile['clean_code_avg'] ?></p>
        <p class="text-xs text-zinc-500 mt-1">Clean Code</p>
      </div>
      <div class="px-4 text-center">
        <p class="text-2xl font-extrabold <?= score_color_class((int)$profile['security_avg']) ?>"><?= (int)$profile['security_avg'] ?></p>
        <p class="text-xs text-zinc-500 mt-1">Keamanan</p>
      </div>
      <div class="px-4 text-center">
        <p class="text-2xl font-extrabold <?= score_color_class((int)$profile['efficiency_avg']) ?>"><?= (int)$profile['efficiency_avg'] ?></p>
        <p class="text-xs text-zinc-500 mt-1">Efisiensi</p>
      </div>
    </div>
  </div>

  <?php if ($profile['strengths']): ?>
  <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div class="surface rounded-2xl p-5">
      <p class="text-xs font-semibold uppercase tracking-wider text-emerald-600">Kekuatan Utama</p>
      <p class="mt-1 font-semibold text-zinc-900"><?= e($profile['strengths']) ?></p>
    </div>
    <div class="surface rounded-2xl p-5">
      <p class="text-xs font-semibold uppercase tracking-wider text-amber-600">Perlu Ditingkatkan</p>
      <p class="mt-1 font-semibold text-zinc-900"><?= e($profile['weaknesses']) ?></p>
    </div>
  </div>
  <?php endif; ?>

  <!-- History -->
  <div class="mt-12">
    <h2 class="text-lg font-bold text-zinc-900 mb-4">Riwayat Penilaian</h2>
    <?php if (empty($history)): ?>
      <div class="surface rounded-3xl p-10 text-center">
        <p class="text-zinc-500 text-sm">Belum ada studi kasus yang diselesaikan.</p>
      </div>
    <?php else: ?>
    <div class="surface rounded-3xl divide-y divide-zinc-200">
      <?php foreach ($history as $h): ?>
      <a href="<?= APP_URL ?>/submission.php?id=<?= $h['id'] ?>" class="flex items-center justify-between px-6 py-4 hover:bg-zinc-50 transition-colors">
        <div>
          <p class="font-medium text-zinc-900 text-sm"><?= e($h['title']) ?></p>
          <p class="text-xs text-zinc-400 mt-0.5"><?= time_ago($h['submitted_at']) ?></p>
        </div>
        <span class="font-bold <?= score_color_class((int)$h['overall_score']) ?>"><?= (int)$h['overall_score'] ?></span>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
