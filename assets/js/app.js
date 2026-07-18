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
  circle.style.strokeDashoffset = circumference;
  requestAnimationFrame(function () {
    circle.style.strokeDashoffset = circumference - (score / 100) * circumference;
  });
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
