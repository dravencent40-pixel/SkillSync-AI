<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_login();
$user = current_user();
$pdo = db();

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare(
    'SELECT s.*, t.title AS task_title, t.case_brief, u.name AS student_name
     FROM submissions s
     JOIN tasks t ON t.id = s.task_id
     JOIN users u ON u.id = s.user_id
     WHERE s.id = ?'
);
$stmt->execute([$id]);
$submission = $stmt->fetch();

if (!$submission || ($user['role'] === 'siswa' && $submission['user_id'] != $user['id'])) {
    flash('error', 'Submission tidak ditemukan.');
    redirect('dashboard.php');
}

$reviewStmt = $pdo->prepare('SELECT * FROM ai_reviews WHERE submission_id = ?');
$reviewStmt->execute([$id]);
$review = $reviewStmt->fetch();
$findings = $review ? json_decode($review['findings_json'], true) : [];

$pageTitle = 'Hasil Audit';
require __DIR__ . '/includes/header.php';
?>

<section class="max-w-4xl mx-auto px-6 py-10">
  <a href="<?= APP_URL ?>/<?= $user['role']==='siswa' ? 'dashboard.php' : 'company/talent.php' ?>" class="text-sm text-zinc-500 hover:text-zinc-900">&larr; Kembali</a>

  <div class="mt-4 flex items-start justify-between gap-4 flex-wrap">
    <div>
      <p class="text-sm text-zinc-500">Hasil Audit &middot; <?= e($submission['student_name']) ?></p>
      <h1 class="text-3xl font-bold tracking-tight text-zinc-900"><?= e($submission['task_title']) ?></h1>
    </div>
    <?php if ($user['role'] === 'siswa'): ?>
    <a href="<?= APP_URL ?>/mentor.php?submission_id=<?= $submission['id'] ?>" class="btn-tactile bg-zinc-900 text-white px-5 py-2.5 rounded-lg text-sm font-semibold hover:bg-zinc-800">
      Tanya Agent Mentor
    </a>
    <?php endif; ?>
  </div>

  <?php if (!$review): ?>
    <div class="mt-8 surface rounded-3xl p-10 text-center">
      <p class="text-zinc-500 text-sm">Audit sedang diproses. Coba muat ulang beberapa saat lagi.</p>
    </div>
  <?php else: ?>

  <!-- Score overview -->
  <div class="mt-8 grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="surface rounded-3xl p-8 flex items-center gap-6 lg:col-span-1">
      <div class="relative w-24 h-24 shrink-0">
        <svg class="score-ring w-24 h-24" data-score="<?= (int)$review['overall_score'] ?>" viewBox="0 0 100 100">
          <circle cx="50" cy="50" r="42" fill="none" stroke="#e4e4e7" stroke-width="9"/>
          <circle class="progress" cx="50" cy="50" r="42" fill="none" stroke="#059669" stroke-width="9" stroke-linecap="round"/>
        </svg>
        <div class="absolute inset-0 grid place-items-center">
          <span class="text-xl font-extrabold text-zinc-900"><?= (int)$review['overall_score'] ?></span>
        </div>
      </div>
      <div>
        <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400">Skor Keseluruhan</p>
        <p class="mt-1 text-sm text-zinc-600 leading-relaxed"><?= e($review['summary']) ?></p>
      </div>
    </div>
    <div class="lg:col-span-2 surface rounded-3xl p-8 grid grid-cols-3 divide-x divide-zinc-200">
      <div class="px-4 text-center first:pl-0">
        <p class="text-2xl font-extrabold <?= score_color_class((int)$review['clean_code_score']) ?>"><?= (int)$review['clean_code_score'] ?></p>
        <p class="text-xs text-zinc-500 mt-1">Clean Code</p>
      </div>
      <div class="px-4 text-center">
        <p class="text-2xl font-extrabold <?= score_color_class((int)$review['security_score']) ?>"><?= (int)$review['security_score'] ?></p>
        <p class="text-xs text-zinc-500 mt-1">Keamanan</p>
      </div>
      <div class="px-4 text-center">
        <p class="text-2xl font-extrabold <?= score_color_class((int)$review['efficiency_score']) ?>"><?= (int)$review['efficiency_score'] ?></p>
        <p class="text-xs text-zinc-500 mt-1">Efisiensi</p>
      </div>
    </div>
  </div>

  <!-- Findings -->
  <div class="mt-10">
    <h2 class="text-lg font-bold text-zinc-900 mb-4">Temuan Agent Auditor</h2>
    <div class="surface rounded-3xl divide-y divide-zinc-200">
      <?php foreach ($findings as $f):
        $sevClass = ['critical'=>'badge-critical','warning'=>'badge-warning','info'=>'badge-info'][$f['severity']] ?? 'badge-info';
        $sevLabel = ['critical'=>'Kritis','warning'=>'Perhatian','info'=>'Info'][$f['severity']] ?? 'Info';
      ?>
      <div class="px-6 py-5">
        <div class="flex items-center gap-2">
          <span class="<?= $sevClass ?> text-[11px] font-semibold px-2 py-1 rounded-full"><?= $sevLabel ?></span>
          <p class="font-semibold text-zinc-900 text-sm"><?= e($f['title']) ?></p>
        </div>
        <p class="mt-2 text-sm text-zinc-600 leading-relaxed"><?= e($f['detail']) ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Submitted code -->
  <div class="mt-10">
    <h2 class="text-lg font-bold text-zinc-900 mb-4">Kode yang Dikirim</h2>
    <pre class="rounded-2xl bg-zinc-900 text-zinc-100 font-mono text-xs p-6 overflow-x-auto leading-relaxed"><?= e($submission['code_content']) ?></pre>
  </div>

  <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
