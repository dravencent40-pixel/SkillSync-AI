<?php
$user = current_user();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($pageTitle) ? e($pageTitle) . ' · ' : '' ?>SkillSync AI</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
<script>
  tailwind.config = { theme: { extend: {
    colors: { accent: { DEFAULT: '#059669', dark: '#047857', light: '#d1fae5' } },
    fontFamily: { sans: ['Outfit','sans-serif'], mono: ['JetBrains Mono','monospace'] },
    borderRadius: { '3xl': '1.75rem' }
  } } };
</script>
</head>
<body class="min-h-[100dvh] bg-zinc-50 text-zinc-900 antialiased">

<header class="sticky top-0 z-40 bg-zinc-50/90 backdrop-blur border-b border-zinc-200/70">
  <div class="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between">
    <a href="<?= APP_URL ?>/index.php" class="flex items-center gap-2.5 shrink-0">
      <span class="w-8 h-8 rounded-xl bg-zinc-900 text-white grid place-items-center font-bold text-sm">S</span>
      <span class="font-semibold tracking-tight text-lg">SkillSync <span class="text-accent">AI</span></span>
    </a>

    <?php if ($user): ?>
      <nav class="hidden md:flex items-center gap-1 text-sm font-medium">
        <?php if ($user['role'] === 'siswa'): ?>
          <a href="<?= APP_URL ?>/dashboard.php" class="px-3 py-2 rounded-lg <?= $currentPage==='dashboard.php'?'bg-zinc-900 text-white':'text-zinc-600 hover:bg-zinc-100' ?>">Dashboard</a>
          <a href="<?= APP_URL ?>/tasks.php" class="px-3 py-2 rounded-lg <?= $currentPage==='tasks.php'||$currentPage==='task.php'?'bg-zinc-900 text-white':'text-zinc-600 hover:bg-zinc-100' ?>">Studi Kasus</a>
          <a href="<?= APP_URL ?>/profile.php" class="px-3 py-2 rounded-lg <?= $currentPage==='profile.php'?'bg-zinc-900 text-white':'text-zinc-600 hover:bg-zinc-100' ?>">Profil Skill</a>
        <?php else: ?>
          <a href="<?= APP_URL ?>/dashboard.php" class="px-3 py-2 rounded-lg <?= $currentPage==='dashboard.php'?'bg-zinc-900 text-white':'text-zinc-600 hover:bg-zinc-100' ?>">Dashboard</a>
          <a href="<?= APP_URL ?>/tasks.php" class="px-3 py-2 rounded-lg <?= $currentPage==='tasks.php'?'bg-zinc-900 text-white':'text-zinc-600 hover:bg-zinc-100' ?>">Kelola Task</a>
          <a href="<?= APP_URL ?>/company/talent.php" class="px-3 py-2 rounded-lg <?= $currentPage==='talent.php'?'bg-zinc-900 text-white':'text-zinc-600 hover:bg-zinc-100' ?>">Talent Pool</a>
        <?php endif; ?>
      </nav>
      <div class="flex items-center gap-3">
        <span class="hidden sm:flex w-9 h-9 rounded-full bg-accent-light text-accent-dark items-center justify-center text-xs font-semibold"><?= e(initials($user['name'])) ?></span>
        <a href="<?= APP_URL ?>/logout.php" class="text-sm font-medium text-zinc-500 hover:text-zinc-900 transition-colors">Keluar</a>
      </div>
    <?php else: ?>
      <div class="flex items-center gap-3">
        <!-- Login/Register removed as requested -->
      </div>
    <?php endif; ?>
  </div>
</header>

<main>
<?php foreach (get_flashes() as $f): ?>
  <div class="max-w-7xl mx-auto px-6 pt-4">
    <div class="rounded-xl px-4 py-3 text-sm font-medium border <?= $f['type']==='error' ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200' ?>">
      <?= e($f['message']) ?>
    </div>
  </div>
<?php endforeach; ?>
