<?php
/**
 * SkillSync AI — Helper Functions
 */

function e(?string $str): string
{
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . APP_URL . '/' . ltrim($path, '/'));
    exit;
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool
{
    return isset($_SESSION['user']);
}

function require_login(): void
{
    if (!is_logged_in()) {
        flash('error', 'Silakan masuk terlebih dahulu.');
        redirect('login.php');
    }
}

function require_role(string $role): void
{
    require_login();
    if (current_user()['role'] !== $role) {
        flash('error', 'Kamu tidak memiliki akses ke halaman ini.');
        redirect('dashboard.php');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function get_flashes(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

function badge_from_score(int $score): string
{
    if ($score >= 90) return 'Top Talent';
    if ($score >= 75) return 'Job Ready';
    if ($score >= 55) return 'Junior Ready';
    return 'Pemula';
}

function score_color_class(int $score): string
{
    if ($score >= 85) return 'text-emerald-600';
    if ($score >= 65) return 'text-amber-600';
    return 'text-rose-600';
}

function time_ago(string $datetime): string
{
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'baru saja';
    if ($diff < 3600) return floor($diff / 60) . ' menit lalu';
    if ($diff < 86400) return floor($diff / 3600) . ' jam lalu';
    return floor($diff / 86400) . ' hari lalu';
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    $init = strtoupper(substr($parts[0] ?? 'U', 0, 1));
    if (isset($parts[1])) {
        $init .= strtoupper(substr($parts[1], 0, 1));
    }
    return $init;
}
