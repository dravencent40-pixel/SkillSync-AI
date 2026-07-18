# SkillSync AI
**AI Technical Project Lead & Assessment Agent untuk SMK/Perusahaan Tech**
Proposal Project — Lomba AI Agent Innovation, Goodeva Technology (Kategori Pendidikan/Inovatif)

---

## 1. Masalah

Perusahaan teknologi kesulitan menyaring calon anak magang dari SMK karena CV/portofolio
konvensional tidak mencerminkan kemampuan teknis nyata. Guru pembimbing pun kewalahan
memberi review kode dan mentoring satu-per-satu ke puluhan siswa.

## 2. Solusi — 4 AI Agent yang bekerja sebagai "Senior Tech Lead" virtual

| Agent | Peran | Implementasi di project ini |
|---|---|---|
| **Task Issuer** | Mengeluarkan studi kasus/kodingan riil dari industri | `includes/agents/TaskIssuerAgent.php` — memilih task dari bank soal, diprioritaskan pada kategori dengan skor siswa paling lemah (personalisasi) |
| **Reviewer & Auditor** | Menilai source code otomatis: clean code, keamanan, efisiensi | `includes/agents/ReviewerAuditorAgent.php` — audit lewat Claude API, dengan fallback heuristik pattern-matching offline |
| **Mentor** | Memberi feedback teknis & arahan interaktif untuk bug | `includes/agents/MentorAgent.php` + chatbot real-time di `mentor.php` / `api/chat.php` |
| **Profile Generator** | Membuka transparansi skor kompetensi siap direkomendasikan | `includes/agents/ProfileGeneratorAgent.php` — agregasi skor ke `skill_profiles`, ditampilkan ke mitra lewat Talent Pool |

## 3. Instalasi

**Kebutuhan:** PHP 8.1+, MySQL 8 / MariaDB 10.5+, server lokal (XAMPP/Laragon/`php -S`).

```bash
# 1. Import database
mysql -u root -p < database/schema.sql
mysql -u root -p < database/seed.sql   # opsional, data contoh + akun demo

# 2. Sesuaikan koneksi
# edit config/config.php -> DB_HOST, DB_NAME, DB_USER, DB_PASS, APP_URL

# 3. Jalankan
php -S localhost:8000
# buka http://localhost:8000
```

**Akun demo** (password: `password123`):
- Siswa: `rafi@smkn9bekasi.sch.id`
- Mitra: `admin@goodeva.tech`

## 4. Mode AI: Online vs Offline

Agar bisa didemokan tanpa biaya API atau koneksi internet, setiap agent punya **fallback
heuristik lokal**. Untuk mengaktifkan kecerdasan penuh via Claude:

```php
// config/config.php
putenv('ANTHROPIC_API_KEY=sk-ant-xxxxxxxx');
```

Tanpa API key, `ReviewerAuditorAgent` tetap mengaudit kode memakai pattern-matching
(deteksi SQL Injection, XSS, kredensial hardcoded, query N+1, dsb — lihat `auditStatic()`),
dan `MentorAgent` menjawab lewat basis pengetahuan pattern-matching (`replyLocal()`).

## 5. Struktur Database (`database/schema.sql`)

12 tabel ternormalisasi: `users`, `student_profiles`, `company_profiles`, `task_categories`,
`tasks`, `submissions`, `ai_reviews`, `mentor_conversations`, `mentor_messages`,
`skill_profiles`, `recommendations`, `activity_logs`. Relasi & foreign key lengkap —
lihat komentar di dalam file schema untuk detail tiap kolom.

## 6. Struktur Folder

```
skillsync-ai/
├── database/        schema.sql, seed.sql
├── config/          config.php, database.php
├── includes/
│   ├── agents/       AIClient, TaskIssuerAgent, ReviewerAuditorAgent, MentorAgent, ProfileGeneratorAgent
│   ├── header.php, footer.php, functions.php
├── assets/          css/style.css, js/app.js
├── api/chat.php     endpoint AJAX Agent Mentor
├── company/         talent.php, talent-detail.php (dashboard mitra industri)
└── *.php            index, login, register, dashboard, tasks, task, submission, mentor, profile
```

## 7. Alur Pengguna

**Siswa:** daftar → dashboard menampilkan skor & rekomendasi task dari Agent Task Issuer →
kerjakan studi kasus → kode langsung diaudit Agent Reviewer & Auditor → lihat temuan &
skor → diskusi lanjut dengan Agent Mentor → skor terakumulasi otomatis di Profil Skill.

**Mitra:** daftar → terbitkan studi kasus dari industri nyata → pantau submission masuk →
jelajahi Talent Pool terurut skor → buka profil detail siswa → tandai status rekrutmen
(disimpan/dihubungi/interview/magang).

## 8. Catatan Desain UI/UX

Desain custom (bukan template generik): palet netral zinc + satu warna aksen (emerald),
tipografi Outfit + JetBrains Mono, layout split-screen & bento grid (bukan center-bias),
kartu dengan diffusion shadow, radial score ring teranimasi, skeleton/empty/error state,
serta chatbot dengan typing indicator dan bubble style asimetris.
