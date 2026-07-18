<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_role('siswa');
$user = current_user();
$pdo = db();

$submissionId = isset($_GET['submission_id']) ? (int) $_GET['submission_id'] : null;
$taskTitle = null;
if ($submissionId) {
    $chk = $pdo->prepare('SELECT s.id, t.title FROM submissions s JOIN tasks t ON t.id = s.task_id WHERE s.id = ? AND s.user_id = ?');
    $chk->execute([$submissionId, $user['id']]);
    $row = $chk->fetch();
    if (!$row) { $submissionId = null; } else { $taskTitle = $row['title']; }
}

// Cari atau buat percakapan
$convStmt = $pdo->prepare('SELECT * FROM mentor_conversations WHERE user_id = ? AND submission_id ' . ($submissionId ? '= ?' : 'IS NULL') . ' ORDER BY created_at DESC LIMIT 1');
$submissionId ? $convStmt->execute([$user['id'], $submissionId]) : $convStmt->execute([$user['id']]);
$conversation = $convStmt->fetch();

if (!$conversation) {
    $title = $taskTitle ? 'Diskusi: ' . $taskTitle : 'Sesi Mentoring';
    $pdo->prepare('INSERT INTO mentor_conversations (submission_id, user_id, title) VALUES (?,?,?)')
        ->execute([$submissionId, $user['id'], $title]);
    $convId = (int) $pdo->lastInsertId();
    $conversation = ['id' => $convId, 'title' => $title];

    $welcome = $taskTitle
        ? "Halo {$user['name']}! Aku sudah lihat hasil audit untuk \"{$taskTitle}\". Ada bagian yang mau kamu diskusikan lebih lanjut?"
        : "Halo {$user['name']}! Aku Agent Mentor SkillSync AI. Ceritakan apa yang sedang kamu kerjakan.";
    $pdo->prepare("INSERT INTO mentor_messages (conversation_id, sender, message) VALUES (?, 'agent', ?)")->execute([$convId, $welcome]);
}

$msgStmt = $pdo->prepare('SELECT * FROM mentor_messages WHERE conversation_id = ? ORDER BY created_at ASC');
$msgStmt->execute([$conversation['id']]);
$messages = $msgStmt->fetchAll();

$pageTitle = 'Agent Mentor';
require __DIR__ . '/includes/header.php';
?>

<section class="max-w-3xl mx-auto px-6 py-10">
  <div class="flex items-center gap-3">
    <span class="w-11 h-11 rounded-2xl bg-zinc-900 text-white grid place-items-center font-bold">M</span>
    <div>
      <h1 class="text-xl font-bold text-zinc-900">Agent Mentor</h1>
      <p class="text-xs text-zinc-500"><?= $taskTitle ? 'Konteks: ' . e($taskTitle) : 'Sesi bebas' ?></p>
    </div>
  </div>

  <div id="chatWindow" class="mt-6 surface rounded-3xl p-6 h-[55vh] overflow-y-auto flex flex-col gap-4">
    <?php foreach ($messages as $m): ?>
      <?php if ($m['sender'] === 'agent'): ?>
        <div class="flex gap-3 max-w-[80%] animate-fade-up">
          <span class="w-8 h-8 rounded-xl bg-zinc-900 text-white grid place-items-center text-xs font-bold shrink-0">M</span>
          <div class="bubble-agent px-4 py-3 text-sm leading-relaxed"><?= nl2br(e($m['message'])) ?></div>
        </div>
      <?php else: ?>
        <div class="flex gap-3 max-w-[80%] ml-auto flex-row-reverse animate-fade-up">
          <span class="w-8 h-8 rounded-xl bg-accent-light text-accent-dark grid place-items-center text-xs font-bold shrink-0"><?= e(initials($user['name'])) ?></span>
          <div class="bubble-user px-4 py-3 text-sm leading-relaxed"><?= nl2br(e($m['message'])) ?></div>
        </div>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>

  <form id="chatForm" class="mt-4 flex items-end gap-3">
    <input type="hidden" id="conversationId" value="<?= (int)$conversation['id'] ?>">
    <textarea id="chatInput" rows="1" required placeholder="Tanyakan tentang bug, konsep, atau feedback kode kamu…"
      class="flex-1 resize-none border border-zinc-300 rounded-2xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-accent/40 focus:border-accent"></textarea>
    <button type="submit" id="sendBtn" class="btn-tactile bg-zinc-900 text-white w-12 h-12 rounded-2xl grid place-items-center hover:bg-zinc-800 shrink-0">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2 11 13"/><path d="M22 2 15 22l-4-9-9-4 20-7z"/></svg>
    </button>
  </form>
</section>

<script>
const chatWindow = document.getElementById('chatWindow');
const chatForm = document.getElementById('chatForm');
const chatInput = document.getElementById('chatInput');
const sendBtn = document.getElementById('sendBtn');
const conversationId = document.getElementById('conversationId').value;

chatWindow.scrollTop = chatWindow.scrollHeight;

function bubble(sender, text) {
  const wrap = document.createElement('div');
  wrap.className = 'flex gap-3 max-w-[80%] animate-fade-up' + (sender === 'user' ? ' ml-auto flex-row-reverse' : '');
  const initials = sender === 'agent' ? 'M' : '<?= e(initials($user["name"])) ?>';
  const avatarClass = sender === 'agent' ? 'bg-zinc-900 text-white' : 'bg-accent-light text-accent-dark';
  const bubbleClass = sender === 'agent' ? 'bubble-agent' : 'bubble-user';
  wrap.innerHTML = `<span class="w-8 h-8 rounded-xl ${avatarClass} grid place-items-center text-xs font-bold shrink-0">${initials}</span>
                     <div class="${bubbleClass} px-4 py-3 text-sm leading-relaxed"></div>`;
  wrap.querySelector('div').textContent = text;
  chatWindow.appendChild(wrap);
  chatWindow.scrollTop = chatWindow.scrollHeight;
}

function typingIndicator(show) {
  let el = document.getElementById('typingIndicator');
  if (show) {
    if (el) return;
    el = document.createElement('div');
    el.id = 'typingIndicator';
    el.className = 'flex gap-3 max-w-[80%]';
    el.innerHTML = `<span class="w-8 h-8 rounded-xl bg-zinc-900 text-white grid place-items-center text-xs font-bold shrink-0">M</span>
      <div class="bubble-agent px-4 py-3 flex gap-1">
        <span class="typing-dot w-1.5 h-1.5 rounded-full bg-zinc-400"></span>
        <span class="typing-dot w-1.5 h-1.5 rounded-full bg-zinc-400"></span>
        <span class="typing-dot w-1.5 h-1.5 rounded-full bg-zinc-400"></span>
      </div>`;
    chatWindow.appendChild(el);
    chatWindow.scrollTop = chatWindow.scrollHeight;
  } else if (el) {
    el.remove();
  }
}

chatForm.addEventListener('submit', async function (e) {
  e.preventDefault();
  const text = chatInput.value.trim();
  if (!text) return;
  bubble('user', text);
  chatInput.value = '';
  sendBtn.disabled = true;
  typingIndicator(true);

  try {
    const res = await fetch('<?= APP_URL ?>/api/chat.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ conversation_id: conversationId, message: text })
    });
    const data = await res.json();
    typingIndicator(false);
    bubble('agent', data.reply || 'Maaf, terjadi kendala. Coba lagi ya.');
  } catch (err) {
    typingIndicator(false);
    bubble('agent', 'Koneksi terganggu, coba kirim ulang pesanmu.');
  }
  sendBtn.disabled = false;
});

chatInput.addEventListener('keydown', function (e) {
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault();
    chatForm.requestSubmit();
  }
});
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
