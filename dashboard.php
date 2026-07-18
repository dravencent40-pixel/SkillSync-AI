<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/agents/TaskIssuerAgent.php';
require_login();
$user = current_user();
$pdo = db();
$pageTitle = 'Dashboard';

if ($user['role'] === 'siswa') {
    $profileStmt = $pdo->prepare('SELECT * FROM skill_profiles WHERE user_id = ?');
    $profileStmt->execute([$user['id']]);
    $profile = $profileStmt->fetch() ?: ['overall_score'=>0,'clean_code_avg'=>0,'security_avg'=>0,'efficiency_avg'=>0,'tasks_completed'=>0,'badge'=>'Pemula'];

    $recentStmt = $pdo->prepare(
        'SELECT s.id, s.submitted_at, t.title, r.overall_score
         FROM submissions s JOIN tasks t ON t.id = s.task_id
         LEFT JOIN ai_reviews r ON r.submission_id = s.id
         WHERE s.user_id = ? ORDER BY s.submitted_at DESC LIMIT 5'
    );
    $recentStmt->execute([$user['id']]);
    $recent = $recentStmt->fetchAll();

    $recommended = (new TaskIssuerAgent())->recommendedTasks($user['id'], 3);
}

require __DIR__ . '/includes/header.php';
?>

<?php if ($user['role'] === 'siswa'): ?>
<section class="max-w-7xl mx-auto px-6 py-10">
  <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
    <div>
      <p class="text-sm text-zinc-500">Selamat datang kembali,</p>
      <h1 class="text-3xl font-bold tracking-tight text-zinc-900"><?= e($user['name']) ?></h1>
    </div>
    <a href="<?= APP_URL ?>/tasks.php" class="btn-tactile bg-zinc-900 text-white px-5 py-2.5 rounded-lg text-sm font-semibold hover:bg-zinc-800 w-fit">Ambil Studi Kasus Baru</a>
  </div>

  <!-- Bento row 1: score ring + 3 metric strips -->
  <div class="mt-10 grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="surface rounded-3xl p-8 flex items-center gap-6 spot-card lg:col-span-1">
      <div class="relative w-28 h-28 shrink-0">
        <svg class="score-ring w-28 h-28" data-score="<?= (int)$profile['overall_score'] ?>" viewBox="0 0 100 100">
          <circle cx="50" cy="50" r="42" fill="none" stroke="#e4e4e7" stroke-width="9"/>
          <circle class="progress" cx="50" cy="50" r="42" fill="none" stroke="#059669" stroke-width="9" stroke-linecap="round"/>
        </svg>
        <div class="absolute inset-0 grid place-items-center">
          <span class="text-2xl font-extrabold text-zinc-900"><?= (int)$profile['overall_score'] ?></span>
        </div>
      </div>
      <div>
        <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400">Skor Keseluruhan</p>
        <p class="mt-1 text-lg font-bold text-zinc-900"><?= e($profile['badge']) ?></p>
        <p class="text-xs text-zinc-500 mt-1"><?= (int)$profile['tasks_completed'] ?> studi kasus diselesaikan</p>
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

  <!-- Recommended tasks -->
  <div class="mt-12">
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-lg font-bold text-zinc-900">Direkomendasikan Agent Task Issuer</h2>
      <a href="<?= APP_URL ?>/tasks.php" class="text-sm link-accent font-medium">Lihat semua</a>
    </div>
    <?php if (empty($recommended)): ?>
      <div class="surface rounded-3xl p-10 text-center">
        <p class="text-zinc-500 text-sm">Semua studi kasus tersedia sudah kamu kerjakan. Nantikan task baru dari mitra industri!</p>
      </div>
    <?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
      <?php foreach ($recommended as $t): ?>
      <a href="<?= APP_URL ?>/task.php?id=<?= $t['id'] ?>" class="spot-card surface rounded-2xl p-6 hover:-translate-y-0.5 transition-transform">
        <span class="badge-info text-[11px] font-semibold px-2 py-1 rounded-full"><?= e($t['category_name']) ?></span>
        <h3 class="mt-4 font-semibold text-zinc-900 leading-snug"><?= e($t['title']) ?></h3>
        <p class="mt-2 text-xs text-zinc-500 capitalize"><?= e($t['difficulty']) ?> &middot; <?= e($t['industry_context']) ?></p>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- Recent submissions -->
  <div class="mt-12">
    <h2 class="text-lg font-bold text-zinc-900 mb-4">Riwayat Submission</h2>
    <?php if (empty($recent)): ?>
      <div class="surface rounded-3xl p-10 text-center">
        <p class="text-zinc-500 text-sm">Belum ada submission. Ambil studi kasus pertamamu sekarang.</p>
      </div>
    <?php else: ?>
    <div class="surface rounded-3xl divide-y divide-zinc-200">
      <?php foreach ($recent as $r): ?>
      <a href="<?= APP_URL ?>/submission.php?id=<?= $r['id'] ?>" class="flex items-center justify-between px-6 py-4 hover:bg-zinc-50 transition-colors">
        <div>
          <p class="font-medium text-zinc-900 text-sm"><?= e($r['title']) ?></p>
          <p class="text-xs text-zinc-400 mt-0.5"><?= time_ago($r['submitted_at']) ?></p>
        </div>
        <?php if ($r['overall_score'] !== null): ?>
          <span class="font-bold <?= score_color_class((int)$r['overall_score']) ?>"><?= (int)$r['overall_score'] ?></span>
        <?php else: ?>
          <span class="text-xs text-zinc-400">Diproses…</span>
        <?php endif; ?>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<?php else: /* ============ MITRA DASHBOARD ============ */
    $taskCount = $pdo->query('SELECT COUNT(*) c FROM tasks')->fetch()['c'];
    $submissionCount = $pdo->query('SELECT COUNT(*) c FROM submissions')->fetch()['c'];
    $topTalents = $pdo->query(
        "SELECT u.id, u.name, sp.overall_score, sp.badge, sp.tasks_completed
         FROM skill_profiles sp JOIN users u ON u.id = sp.user_id
         WHERE sp.tasks_completed > 0 ORDER BY sp.overall_score DESC LIMIT 5"
    )->fetchAll();
?>
<section class="max-w-7xl mx-auto px-6 py-10">
  <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
    <div>
      <p class="text-sm text-zinc-500">Dashboard Mitra</p>
      <h1 class="text-3xl font-bold tracking-tight text-zinc-900"><?= e($user['name']) ?></h1>
    </div>
    <a href="<?= APP_URL ?>/tasks.php" class="btn-tactile bg-zinc-900 text-white px-5 py-2.5 rounded-lg text-sm font-semibold hover:bg-zinc-800 w-fit">+ Terbitkan Studi Kasus</a>
  </div>

  <div class="mt-10 grid grid-cols-1 md:grid-cols-3 gap-6">
    <div class="surface rounded-3xl p-8">
      <p class="text-3xl font-extrabold text-zinc-900"><?= (int)$taskCount ?></p>
      <p class="text-sm text-zinc-500 mt-1">Studi kasus aktif</p>
    </div>
    <div class="surface rounded-3xl p-8">
      <p class="text-3xl font-extrabold text-zinc-900"><?= (int)$submissionCount ?></p>
      <p class="text-sm text-zinc-500 mt-1">Total submission dinilai Agent Reviewer</p>
    </div>
    <div class="surface rounded-3xl p-8">
      <p class="text-3xl font-extrabold text-accent"><?= count($topTalents) ?></p>
      <p class="text-sm text-zinc-500 mt-1">Talenta dengan profil skill aktif</p>
    </div>
  </div>

  <div class="mt-12">
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-lg font-bold text-zinc-900">Top Talent Pool</h2>
      <a href="<?= APP_URL ?>/company/talent.php" class="text-sm link-accent font-medium">Lihat semua talenta</a>
    </div>
    <?php if (empty($topTalents)): ?>
      <div class="surface rounded-3xl p-10 text-center">
        <p class="text-zinc-500 text-sm">Belum ada siswa yang menyelesaikan studi kasus.</p>
      </div>
    <?php else: ?>
    <div class="surface rounded-3xl divide-y divide-zinc-200">
      <?php foreach ($topTalents as $t): ?>
      <a href="<?= APP_URL ?>/company/talent-detail.php?id=<?= $t['id'] ?>" class="flex items-center justify-between px-6 py-4 hover:bg-zinc-50 transition-colors">
        <div class="flex items-center gap-3">
          <span class="w-9 h-9 rounded-full bg-accent-light text-accent-dark grid place-items-center text-xs font-semibold"><?= e(initials($t['name'])) ?></span>
          <div>
            <p class="font-medium text-zinc-900 text-sm"><?= e($t['name']) ?></p>
            <p class="text-xs text-zinc-400"><?= e($t['badge']) ?> &middot; <?= (int)$t['tasks_completed'] ?> task</p>
          </div>
        </div>
        <span class="font-bold <?= score_color_class((int)$t['overall_score']) ?>"><?= (int)$t['overall_score'] ?></span>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
