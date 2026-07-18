<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
if (is_logged_in()) redirect('dashboard.php');

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = db()->prepare('SELECT * FROM users WHERE email = ? AND is_active = 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        unset($user['password_hash']);
        $_SESSION['user'] = $user;
        redirect('dashboard.php');
    }
    $errors[] = 'Email atau password salah.';
}

$pageTitle = 'Masuk';
require __DIR__ . '/includes/header.php';
?>

<section class="max-w-md mx-auto px-6 py-14">
  <h1 class="text-3xl font-bold tracking-tight text-zinc-900">Selamat datang kembali</h1>
  <p class="mt-2 text-sm text-zinc-500">Masuk untuk melanjutkan progres kamu.</p>

  <div class="mt-5 rounded-xl bg-zinc-100 text-zinc-600 text-xs px-4 py-3">
    Demo: <span class="font-mono">rafi@smkn9bekasi.sch.id</span> / <span class="font-mono">admin@goodeva.tech</span> — password: <span class="font-mono">password123</span>
  </div>

  <?php if ($errors): ?>
    <div class="mt-5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-sm px-4 py-3">
      <?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <form method="POST" class="mt-6 space-y-4">
    <div class="flex flex-col gap-2">
      <label class="text-sm font-medium text-zinc-700">Email</label>
      <input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>" class="border border-zinc-300 rounded-lg px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent/40 focus:border-accent">
    </div>
    <div class="flex flex-col gap-2">
      <label class="text-sm font-medium text-zinc-700">Password</label>
      <input type="password" name="password" required class="border border-zinc-300 rounded-lg px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent/40 focus:border-accent">
    </div>
    <button type="submit" class="btn-tactile w-full bg-zinc-900 text-white rounded-lg py-3 text-sm font-semibold hover:bg-zinc-800">Masuk</button>
  </form>

  <p class="mt-6 text-sm text-zinc-500 text-center">Belum punya akun? <a href="<?= APP_URL ?>/register.php" class="link-accent font-medium">Daftar</a></p>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
