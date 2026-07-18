<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_login();
$user = current_user();
$pdo = db();
$pageTitle = 'Studi Kasus';
$errors = [];

// Mitra: buat task baru (Agent Task Issuer versi manual oleh manusia mitra)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user['role'] === 'mitra') {
    $title = trim($_POST['title'] ?? '');
    $categoryId = (int) ($_POST['category_id'] ?? 0);
    $industry = trim($_POST['industry_context'] ?? '');
    $brief = trim($_POST['case_brief'] ?? '');
    $starter = $_POST['starter_code'] ?? '';
    $difficulty = in_array($_POST['difficulty'] ?? '', ['pemula','menengah','mahir']) ? $_POST['difficulty'] : 'pemula';

    if ($title === '' || $brief === '' || $categoryId === 0) {
        $errors[] = 'Judul, kategori, dan deskripsi studi kasus wajib diisi.';
    } else {
        $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $title), '-')) . '-' . substr(uniqid(), -4);
        $pdo->prepare('INSERT INTO tasks (category_id, created_by, title, slug, industry_context, case_brief, starter_code, difficulty)
                        VALUES (?,?,?,?,?,?,?,?)')
            ->execute([$categoryId, $user['id'], $title, $slug, $industry, $brief, $starter, $difficulty]);
        flash('success', 'Studi kasus baru berhasil diterbitkan oleh Agent Task Issuer.');
        redirect('tasks.php');
    }
}

$categories = $pdo->query('SELECT * FROM task_categories ORDER BY name')->fetchAll();

if ($user['role'] === 'siswa') {
    $tasks = $pdo->prepare(
        "SELECT t.*, c.name AS category_name,
                (SELECT COUNT(*) FROM submissions s WHERE s.task_id = t.id AND s.user_id = ?) AS done
         FROM tasks t JOIN task_categories c ON c.id = t.category_id
         WHERE t.is_active = 1 ORDER BY t.created_at DESC"
    );
    $tasks->execute([$user['id']]);
} else {
    $tasks = $pdo->prepare(
        "SELECT t.*, c.name AS category_name,
                (SELECT COUNT(*) FROM submissions s WHERE s.task_id = t.id) AS submission_count
         FROM tasks t JOIN task_categories c ON c.id = t.category_id
         ORDER BY t.created_at DESC"
    );
    $tasks->execute();
}
$tasks = $tasks->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<section class="max-w-7xl mx-auto px-6 py-10">
  <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
    <div>
      <h1 class="text-3xl font-bold tracking-tight text-zinc-900">Studi Kasus</h1>
      <p class="mt-1 text-sm text-zinc-500"><?= $user['role']==='siswa' ? 'Diterbitkan oleh Agent Task Issuer & mitra industri.' : 'Kelola bank soal yang akan disajikan Agent Task Issuer.' ?></p>
    </div>
    <?php if ($user['role'] === 'mitra'): ?>
    <button onclick="document.getElementById('newTaskModal').classList.remove('hidden')" class="btn-tactile bg-zinc-900 text-white px-5 py-2.5 rounded-lg text-sm font-semibold hover:bg-zinc-800 w-fit">+ Terbitkan Studi Kasus</button>
    <?php endif; ?>
  </div>

  <?php if ($errors): ?>
    <div class="mt-6 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-sm px-4 py-3">
      <?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if (empty($tasks)): ?>
    <div class="mt-10 surface rounded-3xl p-14 text-center">
      <p class="text-zinc-500 text-sm">Belum ada studi kasus tersedia.</p>
    </div>
  <?php else: ?>
  <div class="mt-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
    <?php foreach ($tasks as $t): ?>
    <div class="spot-card surface rounded-2xl p-6 flex flex-col">
      <div class="flex items-center justify-between">
        <span class="badge-info text-[11px] font-semibold px-2 py-1 rounded-full"><?= e($t['category_name']) ?></span>
        <span class="text-[11px] font-medium text-zinc-400 capitalize"><?= e($t['difficulty']) ?></span>
      </div>
      <h3 class="mt-4 font-semibold text-zinc-900 leading-snug"><?= e($t['title']) ?></h3>
      <p class="mt-2 text-xs text-zinc-500 line-clamp-3"><?= e(mb_substr($t['case_brief'], 0, 110)) ?>…</p>
      <div class="mt-5 pt-4 border-t border-zinc-100 flex items-center justify-between">
        <span class="text-xs text-zinc-400"><?= e($t['industry_context']) ?></span>
        <?php if ($user['role'] === 'siswa'): ?>
          <a href="<?= APP_URL ?>/task.php?id=<?= $t['id'] ?>" class="text-sm font-semibold link-accent">
            <?= $t['done'] > 0 ? 'Lihat ulang →' : 'Kerjakan →' ?>
          </a>
        <?php else: ?>
          <span class="text-xs text-zinc-400"><?= (int)$t['submission_count'] ?> submission</span>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>

<?php if ($user['role'] === 'mitra'): ?>
<div id="newTaskModal" class="hidden fixed inset-0 z-50 bg-zinc-900/40 backdrop-blur-sm grid place-items-center p-4">
  <div class="bg-white rounded-3xl max-w-lg w-full p-8 max-h-[90vh] overflow-y-auto animate-fade-up">
    <div class="flex items-center justify-between mb-6">
      <h2 class="text-lg font-bold text-zinc-900">Terbitkan Studi Kasus</h2>
      <button onclick="document.getElementById('newTaskModal').classList.add('hidden')" class="text-zinc-400 hover:text-zinc-700 text-xl leading-none">&times;</button>
    </div>
    <form method="POST" class="space-y-4">
      <div class="flex flex-col gap-2">
        <label class="text-sm font-medium text-zinc-700">Judul</label>
        <input type="text" name="title" required class="border border-zinc-300 rounded-lg px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent/40 focus:border-accent" placeholder="Perbaiki Endpoint API yang Rentan XSS">
      </div>
      <div class="grid grid-cols-2 gap-4">
        <div class="flex flex-col gap-2">
          <label class="text-sm font-medium text-zinc-700">Kategori</label>
          <select name="category_id" required class="border border-zinc-300 rounded-lg px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent/40">
            <?php foreach ($categories as $c): ?>
              <option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="flex flex-col gap-2">
          <label class="text-sm font-medium text-zinc-700">Tingkat Kesulitan</label>
          <select name="difficulty" class="border border-zinc-300 rounded-lg px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent/40">
            <option value="pemula">Pemula</option>
            <option value="menengah">Menengah</option>
            <option value="mahir">Mahir</option>
          </select>
        </div>
      </div>
      <div class="flex flex-col gap-2">
        <label class="text-sm font-medium text-zinc-700">Konteks Industri</label>
        <input type="text" name="industry_context" class="border border-zinc-300 rounded-lg px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent/40" placeholder="Fintech, E-commerce, Logistik…">
      </div>
      <div class="flex flex-col gap-2">
        <label class="text-sm font-medium text-zinc-700">Deskripsi Studi Kasus</label>
        <textarea name="case_brief" required rows="4" class="border border-zinc-300 rounded-lg px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent/40" placeholder="Jelaskan permasalahan riil yang harus diselesaikan siswa…"></textarea>
      </div>
      <div class="flex flex-col gap-2">
        <label class="text-sm font-medium text-zinc-700">Kode Awal (opsional)</label>
        <textarea name="starter_code" rows="4" class="code-editor border border-zinc-300 rounded-lg px-3.5 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-accent/40" placeholder="<?php\n// kode bermasalah yang perlu diperbaiki siswa"></textarea>
      </div>
      <button type="submit" class="btn-tactile w-full bg-zinc-900 text-white rounded-lg py-3 text-sm font-semibold hover:bg-zinc-800">Terbitkan</button>
    </form>
  </div>
</div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
