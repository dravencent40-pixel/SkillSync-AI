<?php
/**
 * SkillSync AI — Konfigurasi Aplikasi
 * Salin file ini, sesuaikan nilai di bawah dengan environment kamu.
 */

// --- Database -----------------------------------------------------------
define('DB_HOST', 'localhost');
define('DB_NAME', 'skillsync_ai');
define('DB_USER', 'root');
define('DB_PASS', '');

// --- Aplikasi -------------------------------------------------------------
define('APP_NAME', 'SkillSync AI');
// For local development with XAMPP (Apache on port 80)
define('APP_URL', 'http://localhost/skillsync');

// --- AI Agent (Anthropic Claude API) --------------------------------------
// Kosongkan ANTHROPIC_API_KEY jika belum punya API key: sistem akan otomatis
// memakai mode "Local Heuristic Agent" (rule-based) agar tetap bisa didemokan
// tanpa koneksi internet / biaya API sama sekali.
putenv('ANTHROPIC_API_KEY='); // isi: putenv('ANTHROPIC_API_KEY=sk-ant-xxxx');
define('AI_MODEL', 'claude-sonnet-4-6');

// --- Sesi -------------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Jakarta');
error_reporting(E_ALL);
ini_set('display_errors', '1');
