<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
if (is_logged_in()) redirect('dashboard.php');

$role = in_array($_GET['role'] ?? '', ['siswa', 'mitra']) ? $_GET['role'] : 'siswa';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = in_array($_POST['role'] ?? '', ['siswa', 'mitra']) ? $_POST['role'] : 'siswa';
    $extra    = trim($_POST['extra'] ?? '');

    if ($name === '') $errors[] = 'Nama wajib diisi.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email tidak valid.';
    if (strlen($password) < 6) $errors[] = 'Password minimal 6 karakter.';

    if (empty($errors)) {
        $pdo = db();
        $check = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $check->execute([$email]);
        if ($check->fetch()) {
            $errors[] = 'Email sudah terdaftar. Silakan masuk.';
        } else {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, role, avatar_initial) VALUES (?,?,?,?,?)');
            $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $role, initials($name)]);
            $userId = (int) $pdo->lastInsertId();

            if ($role === 'siswa') {
                $pdo->prepare('INSERT INTO student_profiles (user_id, jurusan, kelas) VALUES (?,?,?)')
                    ->execute([$userId, $extra ?: 'Rekayasa Perangkat Lunak', '-']);
                $pdo->prepare('INSERT INTO skill_profiles (user_id) VALUES (?)')->execute([$userId]);
            } else {
                $pdo->prepare('INSERT INTO company_profiles (user_id, company_name, industry) VALUES (?,?,?)')
                    ->execute([$userId, $extra ?: $name, 'Teknologi']);
            }
            $pdo->commit();

            flash('success', 'Akun berhasil dibuat. Silakan masuk.');
            redirect('login.php');
        }
    }
}

$pageTitle = 'Daftar';
require __DIR__ . '/includes/header.php';
?>

<section class="max-w-md mx-auto px-6 py-14">
  <h1 class="text-3xl font-bold tracking-tight text-zinc-900">Buat akun</h1>
  <p class="mt-2 text-sm text-zinc-500">Bergabung sebagai siswa atau mitra industri.</p>

  <div class="mt-6 grid grid-cols-2 gap-2 p-1 bg-zinc-100 rounded-xl text-sm font-semibold" id="roleTabs">
    <a href="?role=siswa" class="text-center py-2 rounded-lg <?= $role==='siswa' ? 'bg-white shadow text-zinc-900' : 'text-zinc-500' ?>">Siswa</a>
    <a href="?role=mitra" class="text-center py-2 rounded-lg <?= $role==='mitra' ? 'bg-white shadow text-zinc-900' : 'text-zinc-500' ?>">Mitra Industri</a>
  </div>

  <?php if ($errors): ?>
    <div class="mt-5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-sm px-4 py-3 space-y-1">
      <?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <form method="POST" class="mt-6 space-y-4">
    <input type="hidden" name="role" value="<?= e($role) ?>">
    <div class="flex flex-col gap-2">
      <label class="text-sm font-medium text-zinc-700"><?= $role==='siswa' ? 'Nama Lengkap' : 'Nama Penanggung Jawab' ?></label>
      <input type="text" name="name" required class="border border-zinc-300 rounded-lg px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent/40 focus:border-accent" placeholder="<?= $role==='siswa' ? 'Rafi Pratama' : 'Nama HR / Tech Lead' ?>">
    </div>
    <div class="flex flex-col gap-2">
      <label class="text-sm font-medium text-zinc-700">Email</label>
      <input type="email" name="email" required class="border border-zinc-300 rounded-lg px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent/40 focus:border-accent" placeholder="nama@email.com">
    </div>
    <div class="flex flex-col gap-2">
      <label class="text-sm font-medium text-zinc-700"><?= $role==='siswa' ? 'Jurusan' : 'Nama Perusahaan' ?></label>
      <input type="text" name="extra" class="border border-zinc-300 rounded-lg px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent/40 focus:border-accent" placeholder="<?= $role==='siswa' ? 'Rekayasa Perangkat Lunak' : 'Goodeva Technology' ?>">
    </div>
    <div class="flex flex-col gap-2">
      <label class="text-sm font-medium text-zinc-700">Password</label>
      <input type="password" name="password" required minlength="6" class="border border-zinc-300 rounded-lg px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent/40 focus:border-accent" placeholder="Minimal 6 karakter">
      <p class="text-xs text-zinc-400">Helper: gunakan kombinasi huruf & angka.</p>
    </div>
    <button type="submit" class="btn-tactile w-full bg-zinc-900 text-white rounded-lg py-3 text-sm font-semibold hover:bg-zinc-800">Buat Akun</button>
  </form>

  <p class="mt-6 text-sm text-zinc-500 text-center">Sudah punya akun? <a href="<?= APP_URL ?>/login.php" class="link-accent font-medium">Masuk</a></p>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
