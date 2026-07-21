// SkillSync AI — shared frontend behaviour

// Spotlight-border hover effect for .spot-card elements
document.querySelectorAll('.spot-card').forEach(function (card) {
  card.addEventListener('mousemove', function (e) {
    var rect = card.getBoundingClientRect();
    card.style.setProperty('--x', (e.clientX - rect.left) + 'px');
    card.style.setProperty('--y', (e.clientY - rect.top) + 'px');
  });
});

// Animate score rings on load (elements with data-score attribute)
document.querySelectorAll('.score-ring').forEach(function (ring) {
  var circle = ring.querySelector('circle.progress');
  if (!circle) return;
  var score = parseFloat(ring.dataset.score || '0');
  var radius = circle.r.baseVal.value;
  var circumference = 2 * Math.PI * radius;
  circle.style.strokeDasharray = circumference;
  // apply offset quickly with a short transition to keep it subtle
  circle.style.transition = 'stroke-dashoffset 0.18s ease';
  circle.style.strokeDashoffset = circumference - (score / 100) * circumference;
});

// Code textarea: preserve tab key behaviour instead of losing focus
document.querySelectorAll('textarea.code-editor').forEach(function (ta) {
  ta.addEventListener('keydown', function (e) {
    if (e.key === 'Tab') {
      e.preventDefault();
      var start = ta.selectionStart, end = ta.selectionEnd;
      ta.value = ta.value.substring(0, start) + '    ' + ta.value.substring(end);
      ta.selectionStart = ta.selectionEnd = start + 4;
    }
  });
});

// Modal preview for CVs
(function () {
  function openModal(url, metaHtml) {
    var overlay = document.getElementById('previewModal');
    if (!overlay) return;
    var iframe = overlay.querySelector('iframe.modal-iframe');
    var meta = overlay.querySelector('.modal-meta');
    iframe.src = url;
    if (meta && metaHtml) meta.innerHTML = metaHtml;
    overlay.classList.add('open');
    document.body.style.overflow = 'hidden';
  }
  function closeModal() {
    var overlay = document.getElementById('previewModal');
    if (!overlay) return;
    var iframe = overlay.querySelector('iframe.modal-iframe');
    iframe.src = 'about:blank';
    overlay.classList.remove('open');
    document.body.style.overflow = '';
  }

  // attach click handlers for preview buttons
  document.addEventListener('click', function (e) {
    var t = e.target.closest('[data-preview]');
    if (t) {
      e.preventDefault();
      var url = t.getAttribute('data-preview');
      var meta = t.getAttribute('data-meta') || '';
      openModal(url, meta);
    }

    if (e.target.matches('#previewModal .modal-close') || e.target.matches('#previewModal')) {
      closeModal();
    }
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeModal();
  });

})();
