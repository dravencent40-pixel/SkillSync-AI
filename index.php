<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
if (is_logged_in()) redirect('dashboard.php');
$pageTitle = 'Beranda';
require __DIR__ . '/includes/header.php';
?>

<!-- HERO: split screen, left content / right asset — anti-center-bias -->
<section class="max-w-7xl mx-auto px-6 pt-16 pb-20 grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
  <div class="lg:col-span-7 animate-fade-up">
    <span class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-accent-dark bg-accent-light px-3 py-1.5 rounded-full">
      Lomba AI Agent Innovation · Goodeva Technology
    </span>
    <h1 class="mt-6 text-4xl md:text-6xl font-extrabold tracking-tighter leading-none text-zinc-900">
      AI Technical Project Lead,<br class="hidden md:block"> siap kerja 24 jam.
    </h1>
    <p class="mt-6 text-base text-zinc-600 leading-relaxed max-w-[58ch]">
      SkillSync AI menugaskan studi kasus industri, mengaudit source code siswa SMK secara otomatis,
      membimbing lewat mentor chatbot interaktif, dan menerbitkan profil kompetensi transparan
      yang siap direkomendasikan langsung ke perusahaan mitra.
    </p>
    <div class="mt-9 flex flex-wrap gap-3">
      <a href="<?= APP_URL ?>/register.php?role=siswa" class="btn-tactile bg-zinc-900 text-white px-6 py-3.5 rounded-xl font-semibold text-sm hover:bg-zinc-800">
        Daftar sebagai Siswa
      </a>
      <a href="<?= APP_URL ?>/register.php?role=mitra" class="btn-tactile bg-white border border-zinc-300 text-zinc-800 px-6 py-3.5 rounded-xl font-semibold text-sm hover:bg-zinc-100">
        Daftar sebagai Mitra Industri
      </a>
    </div>
    <div class="mt-10 flex items-center gap-6 text-sm text-zinc-500">
      <div><span class="font-bold text-zinc-900">4</span> AI Agent bekerja sama</div>
      <div class="w-1 h-1 rounded-full bg-zinc-300"></div>
      <div>Skor kompetensi <span class="font-bold text-zinc-900">real-time</span></div>
    </div>
  </div>

  <div class="lg:col-span-5 animate-fade-up" style="animation-delay:.1s">
    <div class="surface rounded-3xl p-6 spot-card">
      <div class="flex items-center justify-between mb-5">
        <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400">Live Audit Preview</p>
        <span class="w-2 h-2 rounded-full bg-accent animate-pulse"></span>
      </div>
      <div class="rounded-2xl bg-zinc-900 text-zinc-100 font-mono text-xs p-4 leading-relaxed overflow-x-auto">
<span class="text-zinc-500">// login.php</span><br>
$query = <span class="text-rose-400">"SELECT * FROM users WHERE u='$u'"</span>;<br>
<span class="text-zinc-500">// Agent Reviewer menandai baris ini ⤴</span>
      </div>
      <div class="mt-5 grid grid-cols-3 gap-3 text-center">
        <div class="rounded-xl border border-zinc-200 p-3">
          <p class="text-lg font-bold text-rose-600">42</p>
          <p class="text-[11px] text-zinc-500 mt-0.5">Keamanan</p>
        </div>
        <div class="rounded-xl border border-zinc-200 p-3">
          <p class="text-lg font-bold text-amber-600">78</p>
          <p class="text-[11px] text-zinc-500 mt-0.5">Clean Code</p>
        </div>
        <div class="rounded-xl border border-zinc-200 p-3">
          <p class="text-lg font-bold text-emerald-600">85</p>
          <p class="text-[11px] text-zinc-500 mt-0.5">Efisiensi</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- 4 AGENT — divide-y logic grouping instead of heavy cards -->
<section class="max-w-7xl mx-auto px-6 py-16 border-t border-zinc-200/70">
  <h2 class="text-2xl md:text-3xl font-bold tracking-tight text-zinc-900 max-w-md">Empat agent, satu alur kerja Senior Tech Lead</h2>
  <div class="mt-10 divide-y divide-zinc-200/70">
    <?php
    $agents = [
      ['01', 'Agent Task Issuer', 'Mengeluarkan studi kasus & kodingan riil dari industri, dipersonalisasi berdasarkan kategori terlemah tiap siswa.'],
      ['02', 'Agent Reviewer & Auditor', 'Menilai source code secara otomatis: clean code, keamanan (SQLi/XSS), dan efisiensi (N+1 query, kompleksitas).'],
      ['03', 'Agent Mentor', 'Chatbot interaktif yang membimbing perbaikan bug dengan hint bertahap, bukan sekadar jawaban jadi.'],
      ['04', 'Agent Profile Generator', 'Mengagregasi seluruh hasil audit menjadi skor kompetensi transparan, siap direkomendasikan ke mitra.'],
    ];
    foreach ($agents as $a): ?>
    <div class="grid grid-cols-1 md:grid-cols-12 gap-4 py-7 items-start">
      <div class="md:col-span-2 text-3xl font-extrabold text-zinc-200 tracking-tighter"><?= $a[0] ?></div>
      <div class="md:col-span-3 font-semibold text-zinc-900"><?= e($a[1]) ?></div>
      <div class="md:col-span-7 text-sm text-zinc-600 leading-relaxed max-w-[65ch]"><?= e($a[2]) ?></div>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- BENEFIT strip -->
<section class="max-w-7xl mx-auto px-6 py-16 border-t border-zinc-200/70">
  <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
    <div>
      <p class="text-3xl font-extrabold text-accent">100%</p>
      <p class="mt-2 text-sm text-zinc-600">Transparansi skor kompetensi teknis siswa, tanpa bias CV konvensional.</p>
    </div>
    <div>
      <p class="text-3xl font-extrabold text-accent">24/7</p>
      <p class="mt-2 text-sm text-zinc-600">Mentor AI siap membimbing kapan saja, tidak terbatas jam kerja pembimbing.</p>
    </div>
    <div>
      <p class="text-3xl font-extrabold text-accent">1 Klik</p>
      <p class="mt-2 text-sm text-zinc-600">Mitra industri langsung menyaring talenta siap magang dari Talent Pool.</p>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
