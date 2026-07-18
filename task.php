<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/agents/ReviewerAuditorAgent.php';
require_once __DIR__ . '/includes/agents/ProfileGeneratorAgent.php';
require_login();
$user = current_user();
$pdo = db();

$taskId = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT t.*, c.name AS category_name FROM tasks t JOIN task_categories c ON c.id = t.category_id WHERE t.id = ?');
$stmt->execute([$taskId]);
$task = $stmt->fetch();
if (!$task) { flash('error', 'Studi kasus tidak ditemukan.'); redirect('tasks.php'); }

$pageTitle = $task['title'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user['role'] === 'siswa') {
    $code = $_POST['code_content'] ?? '';
    $notes = trim($_POST['notes'] ?? '');
    if (trim($code) === '') {
        $errors[] = 'Kode tidak boleh kosong.';
    } else {
        $ins = $pdo->prepare('INSERT INTO submissions (task_id, user_id, language, code_content, notes, status) VALUES (?,?,?,?,?,\'submitted\')');
        $ins->execute([$taskId, $user['id'], 'php', $code, $notes]);
        $submissionId = (int) $pdo->lastInsertId();

        // --- Agent Reviewer & Auditor bekerja secara sinkron ---
        $review = (new ReviewerAuditorAgent())->review($code, $task['case_brief']);
        $pdo->prepare(
            'INSERT INTO ai_reviews (submission_id, clean_code_score, security_score, efficiency_score, overall_score, summary, findings_json)
             VALUES (?,?,?,?,?,?,?)'
        )->execute([
            $submissionId, $review['clean_code_score'], $review['security_score'], $review['efficiency_score'],
            $review['overall_score'], $review['summary'], json_encode($review['findings'], JSON_UNESCAPED_UNICODE),
        ]);
        $pdo->prepare("UPDATE submissions SET status='reviewed' WHERE id = ?")->execute([$submissionId]);

        // --- Agent Profile Generator memperbarui skor kompetensi ---
        (new ProfileGeneratorAgent())->regenerate($user['id']);

        redirect('submission.php?id=' . $submissionId);
    }
}

$mySubmission = null;
if ($user['role'] === 'siswa') {
    $s = $pdo->prepare('SELECT * FROM submissions WHERE task_id = ? AND user_id = ? ORDER BY submitted_at DESC LIMIT 1');
    $s->execute([$taskId, $user['id']]);
    $mySubmission = $s->fetch();
}

require __DIR__ . '/includes/header.php';
?>

<section class="max-w-5xl mx-auto px-6 py-10">
  <a href="<?= APP_URL ?>/tasks.php" class="text-sm text-zinc-500 hover:text-zinc-900">&larr; Kembali ke Studi Kasus</a>

  <div class="mt-4 flex items-center gap-2">
    <span class="badge-info text-[11px] font-semibold px-2 py-1 rounded-full"><?= e($task['category_name']) ?></span>
    <span class="text-[11px] font-medium text-zinc-400 capitalize"><?= e($task['difficulty']) ?></span>
  </div>
  <h1 class="mt-3 text-3xl font-bold tracking-tight text-zinc-900"><?= e($task['title']) ?></h1>
  <p class="mt-1 text-sm text-zinc-500">Konteks industri: <?= e($task['industry_context'] ?: '-') ?></p>

  <div class="mt-8 surface rounded-3xl p-8">
    <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400 mb-3">Brief dari Agent Task Issuer</p>
    <p class="text-sm text-zinc-700 leading-relaxed whitespace-pre-line"><?= e($task['case_brief']) ?></p>

    <?php if ($task['starter_code']): ?>
    <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400 mt-6 mb-3">Kode Awal</p>
    <pre class="rounded-2xl bg-zinc-900 text-zinc-100 font-mono text-xs p-5 overflow-x-auto"><?= e($task['starter_code']) ?></pre>
    <?php endif; ?>
  </div>

  <?php if ($user['role'] === 'siswa'): ?>
    <?php if ($mySubmission): ?>
      <div class="mt-6 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm px-4 py-3 flex items-center justify-between">
        <span>Kamu sudah pernah mengirim solusi untuk studi kasus ini.</span>
        <a href="<?= APP_URL ?>/submission.php?id=<?= $mySubmission['id'] ?>" class="font-semibold link-accent">Lihat hasil audit &rarr;</a>
      </div>
    <?php endif; ?>

    <?php if ($errors): ?>
      <div class="mt-6 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-sm px-4 py-3">
        <?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="POST" class="mt-6 surface rounded-3xl p-8" id="submitForm">
      <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400 mb-3">Kirim Solusi Kamu</p>
      <textarea name="code_content" rows="14" required class="code-editor w-full border border-zinc-300 rounded-2xl px-4 py-4 text-xs bg-zinc-900 text-zinc-100 focus:outline-none focus:ring-2 focus:ring-accent/40" placeholder="Tempel kode PHP kamu di sini..."><?= e($task['starter_code'] ?? '') ?></textarea>
      <div class="mt-4 flex flex-col gap-2">
        <label class="text-sm font-medium text-zinc-700">Catatan untuk reviewer (opsional)</label>
        <textarea name="notes" rows="2" class="border border-zinc-300 rounded-lg px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent/40" placeholder="Jelaskan pendekatan yang kamu ambil…"></textarea>
      </div>
      <button type="submit" id="submitBtn" class="btn-tactile mt-5 bg-zinc-900 text-white px-6 py-3 rounded-lg text-sm font-semibold hover:bg-zinc-800">
        Kirim untuk Diaudit Agent Reviewer
      </button>
    </form>
    <script>
      document.getElementById('submitForm').addEventListener('submit', function () {
        var btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.innerHTML = 'Agent Reviewer sedang mengaudit kode kamu…';
        btn.classList.add('opacity-70');
      });
    </script>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
