<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('mitra');
$user = current_user();
$pdo = db();

$studentId = (int) ($_GET['id'] ?? 0);

$companyStmt = $pdo->prepare('SELECT id FROM company_profiles WHERE user_id = ?');
$companyStmt->execute([$user['id']]);
$companyId = $companyStmt->fetchColumn();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $companyId) {
    $status = in_array($_POST['status'] ?? '', ['disimpan','dihubungi','interview','magang']) ? $_POST['status'] : 'disimpan';
    $note = trim($_POST['note'] ?? '');
    $pdo->prepare(
        'INSERT INTO recommendations (company_id, user_id, status, note) VALUES (?,?,?,?)
         ON DUPLICATE KEY UPDATE status = VALUES(status), note = VALUES(note)'
    )->execute([$companyId, $studentId, $status, $note]);
    flash('success', 'Status talenta berhasil diperbarui.');
    redirect('company/talent-detail.php?id=' . $studentId);
}

$stmt = $pdo->prepare(
    'SELECT u.name, u.email, sp.*, s.jurusan, s.kelas, s.sekolah, s.bio, s.github_url
     FROM users u
     JOIN skill_profiles sp ON sp.user_id = u.id
     LEFT JOIN student_profiles s ON s.user_id = u.id
     WHERE u.id = ?'
);
$stmt->execute([$studentId]);
$talent = $stmt->fetch();
if (!$talent) { flash('error', 'Talenta tidak ditemukan.'); redirect('company/talent.php'); }

$historyStmt = $pdo->prepare(
    'SELECT s.id, t.title, r.overall_score, s.submitted_at
     FROM submissions s JOIN tasks t ON t.id = s.task_id
     LEFT JOIN ai_reviews r ON r.submission_id = s.id
     WHERE s.user_id = ? ORDER BY s.submitted_at DESC'
);
$historyStmt->execute([$studentId]);
$history = $historyStmt->fetchAll();

$recStmt = $pdo->prepare('SELECT * FROM recommendations WHERE company_id = ? AND user_id = ?');
$recStmt->execute([$companyId, $studentId]);
$rec = $recStmt->fetch();

$pageTitle = $talent['name'];
require __DIR__ . '/../includes/header.php';
?>

<section class="max-w-4xl mx-auto px-6 py-10">
  <a href="<?= APP_URL ?>/company/talent.php" class="text-sm text-zinc-500 hover:text-zinc-900">&larr; Kembali ke Talent Pool</a>

  <div class="mt-4 flex flex-col md:flex-row md:items-center justify-between gap-6">
    <div class="flex items-center gap-4">
      <span class="w-16 h-16 rounded-2xl bg-accent-light text-accent-dark grid place-items-center text-xl font-bold"><?= e(initials($talent['name'])) ?></span>
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-zinc-900"><?= e($talent['name']) ?></h1>
        <p class="text-sm text-zinc-500"><?= e($talent['jurusan'] ?: '-') ?> &middot; <?= e($talent['sekolah'] ?: 'SMKN 9 Bekasi') ?></p>
      </div>
    </div>
    <span class="text-xs font-semibold px-3 py-1.5 rounded-full badge-info w-fit"><?= e($talent['badge']) ?></span>
  </div>

  <div class="mt-10 grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="surface rounded-3xl p-8 flex items-center gap-6 lg:col-span-1">
      <div class="relative w-24 h-24 shrink-0">
        <svg class="score-ring w-24 h-24" data-score="<?= (int)$talent['overall_score'] ?>" viewBox="0 0 100 100">
          <circle cx="50" cy="50" r="42" fill="none" stroke="#e4e4e7" stroke-width="9"/>
          <circle class="progress" cx="50" cy="50" r="42" fill="none" stroke="#059669" stroke-width="9" stroke-linecap="round"/>
        </svg>
        <div class="absolute inset-0 grid place-items-center">
          <span class="text-xl font-extrabold text-zinc-900"><?= (int)$talent['overall_score'] ?></span>
        </div>
      </div>
      <div>
        <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400">Skor Keseluruhan</p>
        <p class="mt-1 text-xs text-zinc-500"><?= (int)$talent['tasks_completed'] ?> studi kasus diselesaikan</p>
      </div>
    </div>
    <div class="lg:col-span-2 surface rounded-3xl p-8 grid grid-cols-3 divide-x divide-zinc-200">
      <div class="px-4 text-center first:pl-0">
        <p class="text-2xl font-extrabold <?= score_color_class((int)$talent['clean_code_avg']) ?>"><?= (int)$talent['clean_code_avg'] ?></p>
        <p class="text-xs text-zinc-500 mt-1">Clean Code</p>
      </div>
      <div class="px-4 text-center">
        <p class="text-2xl font-extrabold <?= score_color_class((int)$talent['security_avg']) ?>"><?= (int)$talent['security_avg'] ?></p>
        <p class="text-xs text-zinc-500 mt-1">Keamanan</p>
      </div>
      <div class="px-4 text-center">
        <p class="text-2xl font-extrabold <?= score_color_class((int)$talent['efficiency_avg']) ?>"><?= (int)$talent['efficiency_avg'] ?></p>
        <p class="text-xs text-zinc-500 mt-1">Efisiensi</p>
      </div>
    </div>
  </div>

  <!-- Recommendation action -->
  <div class="mt-8 surface rounded-3xl p-8">
    <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400 mb-3">Status Rekrutmen</p>
    <form method="POST" class="flex flex-col sm:flex-row gap-4">
      <select name="status" class="border border-zinc-300 rounded-lg px-3.5 py-2.5 text-sm">
        <?php foreach (['disimpan'=>'Disimpan','dihubungi'=>'Dihubungi','interview'=>'Interview','magang'=>'Diterima Magang'] as $val=>$label): ?>
          <option value="<?= $val ?>" <?= ($rec['status'] ?? '')===$val?'selected':'' ?>><?= $label ?></option>
        <?php endforeach; ?>
      </select>
      <input type="text" name="note" value="<?= e($rec['note'] ?? '') ?>" placeholder="Catatan internal (opsional)" class="flex-1 border border-zinc-300 rounded-lg px-3.5 py-2.5 text-sm">
      <button type="submit" class="btn-tactile bg-zinc-900 text-white px-6 py-2.5 rounded-lg text-sm font-semibold hover:bg-zinc-800">Simpan</button>
    </form>
  </div>

  <!-- History -->
  <div class="mt-10">
    <h2 class="text-lg font-bold text-zinc-900 mb-4">Riwayat Studi Kasus</h2>
    <?php if (empty($history)): ?>
      <div class="surface rounded-3xl p-10 text-center"><p class="text-zinc-500 text-sm">Belum ada riwayat.</p></div>
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

<?php require __DIR__ . '/../includes/footer.php'; ?>
