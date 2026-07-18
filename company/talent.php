<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('mitra');
$pdo = db();

$minScore = isset($_GET['min_score']) ? (int) $_GET['min_score'] : 0;
$badge = $_GET['badge'] ?? '';

$sql = "SELECT u.id, u.name, sp.overall_score, sp.badge, sp.tasks_completed, sp.strengths, s.jurusan
        FROM skill_profiles sp
        JOIN users u ON u.id = sp.user_id
        LEFT JOIN student_profiles s ON s.user_id = u.id
        WHERE sp.tasks_completed > 0 AND sp.is_public = 1 AND sp.overall_score >= ?";
$params = [$minScore];
if ($badge !== '') { $sql .= " AND sp.badge = ?"; $params[] = $badge; }
$sql .= " ORDER BY sp.overall_score DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$talents = $stmt->fetchAll();

$pageTitle = 'Talent Pool';
require __DIR__ . '/../includes/header.php';
?>

<section class="max-w-7xl mx-auto px-6 py-10">
  <h1 class="text-3xl font-bold tracking-tight text-zinc-900">Talent Pool</h1>
  <p class="mt-1 text-sm text-zinc-500">Talenta siswa SMK dengan skor kompetensi transparan dari Agent Profile Generator.</p>

  <form method="GET" class="mt-8 flex flex-wrap items-end gap-4">
    <div class="flex flex-col gap-2">
      <label class="text-xs font-semibold uppercase tracking-wider text-zinc-400">Skor Minimum</label>
      <select name="min_score" class="border border-zinc-300 rounded-lg px-3.5 py-2.5 text-sm">
        <option value="0" <?= $minScore===0?'selected':'' ?>>Semua</option>
        <option value="55" <?= $minScore===55?'selected':'' ?>>55+</option>
        <option value="75" <?= $minScore===75?'selected':'' ?>>75+</option>
        <option value="90" <?= $minScore===90?'selected':'' ?>>90+</option>
      </select>
    </div>
    <div class="flex flex-col gap-2">
      <label class="text-xs font-semibold uppercase tracking-wider text-zinc-400">Badge</label>
      <select name="badge" class="border border-zinc-300 rounded-lg px-3.5 py-2.5 text-sm">
        <option value="">Semua</option>
        <?php foreach (['Pemula','Junior Ready','Job Ready','Top Talent'] as $b): ?>
        <option value="<?= $b ?>" <?= $badge===$b?'selected':'' ?>><?= $b ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button type="submit" class="btn-tactile bg-zinc-900 text-white px-5 py-2.5 rounded-lg text-sm font-semibold hover:bg-zinc-800">Filter</button>
  </form>

  <?php if (empty($talents)): ?>
    <div class="mt-10 surface rounded-3xl p-14 text-center">
      <p class="text-zinc-500 text-sm">Belum ada talenta yang sesuai filter.</p>
    </div>
  <?php else: ?>
  <div class="mt-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
    <?php foreach ($talents as $t): ?>
    <a href="<?= APP_URL ?>/company/talent-detail.php?id=<?= $t['id'] ?>" class="spot-card surface rounded-2xl p-6 flex flex-col hover:-translate-y-0.5 transition-transform">
      <div class="flex items-center gap-3">
        <span class="w-11 h-11 rounded-full bg-accent-light text-accent-dark grid place-items-center text-sm font-semibold"><?= e(initials($t['name'])) ?></span>
        <div>
          <p class="font-semibold text-zinc-900 text-sm"><?= e($t['name']) ?></p>
          <p class="text-xs text-zinc-400"><?= e($t['jurusan'] ?: '-') ?></p>
        </div>
      </div>
      <div class="mt-5 flex items-center justify-between">
        <span class="text-xs font-semibold px-2.5 py-1 rounded-full badge-info"><?= e($t['badge']) ?></span>
        <span class="font-bold text-lg <?= score_color_class((int)$t['overall_score']) ?>"><?= (int)$t['overall_score'] ?></span>
      </div>
      <p class="mt-3 text-xs text-zinc-500"><?= (int)$t['tasks_completed'] ?> studi kasus &middot; Kekuatan: <?= e($t['strengths'] ?: '-') ?></p>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
