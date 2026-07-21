</main>

<footer class="mt-24 border-t border-zinc-200/70">
  <div class="max-w-7xl mx-auto px-6 py-10 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
    <div class="flex items-center gap-2.5">
      <span class="w-7 h-7 rounded-lg bg-zinc-900 text-white grid place-items-center font-bold text-xs">S</span>
      <span class="text-sm text-zinc-500">SkillSync AI — Proposal Project Lomba AI Agent Innovation, Goodeva Technology.</span>
    </div>
    <div class="flex flex-col items-start sm:items-end gap-1">
      <p class="text-xs text-zinc-400">Dibuat oleh Kelompok Tekabe &middot; Kategori Pendidikan / Inovatif</p>
      <div class="flex items-center gap-3 text-xs">
        <a href="mailto:taufiqridhoo34@gmail.com" class="text-zinc-400 hover:text-zinc-700 transition-colors cursor-pointer" onclick="window.location.href='mailto:taufiqridhoo34@gmail.com'; return false;">taufiqridhoo34@gmail.com</a>
        <span class="text-zinc-300">&middot;</span>
        <a href="mailto:riwantoraihan@gmail.com" class="text-zinc-400 hover:text-zinc-700 transition-colors cursor-pointer" onclick="window.location.href='mailto:riwantoraihan@gmail.com'; return false;">riwantoraihan@gmail.com</a>
      </div>
    </div>
  </div>
</footer>

<!-- Global preview modal -->
<div id="previewModal" class="modal-overlay" aria-hidden="true">
  <div class="modal-card">
    <aside class="modal-aside">
      <div style="display:flex;justify-content:space-between;align-items:center;gap:.5rem;margin-bottom:.75rem;">
        <div>
          <h4 class="text-sm font-semibold">Pratinjau CV</h4>
          <p class="text-xs muted">Lihat dokumen di sini</p>
        </div>
        <button class="modal-close" aria-label="Tutup">×</button>
      </div>
      <div class="modal-meta text-sm muted">—</div>
      <div style="margin-top:1rem;font-size:.85rem;color:#6b7280">Tip: tekan Esc untuk menutup.</div>
    </aside>
    <div class="modal-body">
      <iframe class="modal-iframe" src="about:blank" title="Pratinjau CV"></iframe>
    </div>
  </div>
</div>

<script src="<?= APP_URL ?>/assets/js/app.js"></script>
</body>
</html>
