<?php
require_once __DIR__ . '/AIClient.php';

/**
 * SkillSync AI — Agent Reviewer & Auditor
 *
 * Berperan sebagai "Senior Tech Lead" yang mengaudit kode kiriman siswa:
 * - clean_code_score  : penamaan, komentar, panjang baris/fungsi, konsistensi
 * - security_score    : pola rawan SQL Injection, XSS, secret hardcoded, eval()
 * - efficiency_score  : query di dalam loop (N+1), perulangan tidak perlu
 *
 * Jika AIClient tersedia (API key diset), agent memakai Claude untuk audit
 * yang jauh lebih dalam. Jika tidak, agent memakai auditStatic() — heuristik
 * berbasis pattern-matching sehingga tetap bisa berjalan offline.
 */
class ReviewerAuditorAgent
{
    private AIClient $ai;

    public function __construct()
    {
        $this->ai = new AIClient();
    }

    /**
     * @return array{clean_code_score:int,security_score:int,efficiency_score:int,
     *               overall_score:int,summary:string,findings:array}
     */
    public function review(string $code, string $taskBrief): array
    {
        if ($this->ai->isAvailable()) {
            $result = $this->reviewWithAI($code, $taskBrief);
            if ($result !== null) {
                return $result;
            }
        }
        return $this->auditStatic($code);
    }

    private function reviewWithAI(string $code, string $taskBrief): ?array
    {
        $system = "Kamu adalah SkillSync AI Reviewer & Auditor — Senior Tech Lead yang mengaudit kode siswa SMK "
                . "untuk kesiapan magang di industri. Nilai berdasarkan: clean code (penamaan, struktur, komentar), "
                . "keamanan (SQL Injection, XSS, secret hardcoded, validasi input), dan efisiensi (kompleksitas, "
                . "query N+1, redundansi). Bersikap membangun dan spesifik, sebutkan nomor baris bila relevan. "
                . "Balas dalam format JSON: {\"clean_code_score\":0-100,\"security_score\":0-100,"
                . "\"efficiency_score\":0-100,\"summary\":\"ringkasan 2-3 kalimat berbahasa Indonesia\","
                . "\"findings\":[{\"severity\":\"info|warning|critical\",\"title\":\"...\",\"detail\":\"...\"}]}";

        $user = "Studi kasus:\n{$taskBrief}\n\nKode kiriman siswa:\n```\n{$code}\n```";

        $result = $this->ai->completeJson($system, [['role' => 'user', 'content' => $user]], 1500);
        if ($result === null || !isset($result['clean_code_score'])) {
            return null;
        }

        $clean = (int) $result['clean_code_score'];
        $sec   = (int) $result['security_score'];
        $eff   = (int) $result['efficiency_score'];

        return [
            'clean_code_score' => $clean,
            'security_score'   => $sec,
            'efficiency_score' => $eff,
            'overall_score'    => $this->weightedOverall($clean, $sec, $eff),
            'summary'          => $result['summary'] ?? 'Ulasan tersedia pada daftar temuan.',
            'findings'         => $result['findings'] ?? [],
        ];
    }

    /**
     * Mode heuristik offline — pattern-matching sederhana namun cukup masuk akal
     * untuk mendemokan alur "Agent Reviewer & Auditor" tanpa API key.
     */
    private function auditStatic(string $code): array
    {
        $findings = [];
        $lines = explode("\n", $code);
        $totalLines = max(count($lines), 1);

        // --- Security checks -------------------------------------------------
        $securityScore = 100;
        if (preg_match('/\$_(GET|POST|REQUEST)\s*\[[^\]]+\]\s*\.?\s*["\']?\s*\.?\s*(SELECT|INSERT|UPDATE|DELETE)/i', $code)
            || preg_match('/["\']\s*\.\s*\$_(GET|POST|REQUEST)/i', $code) && preg_match('/SELECT|INSERT|UPDATE|DELETE/i', $code)) {
            $securityScore -= 40;
            $findings[] = ['severity' => 'critical', 'title' => 'Potensi SQL Injection',
                'detail' => 'Input pengguna tampak digabungkan langsung ke query SQL tanpa prepared statement. Gunakan PDO::prepare() dengan parameter binding.'];
        }
        if (preg_match('/\b(eval|exec|system|passthru|shell_exec)\s*\(/i', $code)) {
            $securityScore -= 30;
            $findings[] = ['severity' => 'critical', 'title' => 'Fungsi berbahaya terdeteksi',
                'detail' => 'Penggunaan eval()/exec()/system() sangat rawan disalahgunakan untuk Remote Code Execution.'];
        }
        if (preg_match('/echo\s+\$_(GET|POST|REQUEST)/i', $code)) {
            $securityScore -= 20;
            $findings[] = ['severity' => 'warning', 'title' => 'Potensi Cross-Site Scripting (XSS)',
                'detail' => 'Output input pengguna langsung tanpa htmlspecialchars() berisiko XSS.'];
        }
        if (preg_match('/(password|api_key|secret)\s*=\s*["\'][^"\']{4,}["\']/i', $code)) {
            $securityScore -= 15;
            $findings[] = ['severity' => 'warning', 'title' => 'Kredensial ter-hardcode',
                'detail' => 'Ditemukan string yang menyerupai password/API key tertulis langsung di kode. Pindahkan ke environment variable.'];
        }
        $securityScore = max(0, min(100, $securityScore));

        // --- Efficiency checks -------------------------------------------------
        $efficiencyScore = 100;
        if (preg_match('/for(each)?\s*\([^)]*\)\s*\{[^}]*(query|find|select)\s*\(/is', $code)) {
            $efficiencyScore -= 35;
            $findings[] = ['severity' => 'warning', 'title' => 'Kemungkinan query N+1',
                'detail' => 'Ditemukan pemanggilan query di dalam loop. Pertimbangkan JOIN atau satu query dengan WHERE IN (...) di luar loop.'];
        }
        if (preg_match_all('/for\s*\(.*for\s*\(.*for\s*\(/is', $code)) {
            $efficiencyScore -= 15;
            $findings[] = ['severity' => 'info', 'title' => 'Nested loop dalam (kompleksitas tinggi)',
                'detail' => 'Terdapat perulangan bersarang lebih dari dua tingkat, periksa apakah bisa disederhanakan.'];
        }
        $efficiencyScore = max(0, min(100, $efficiencyScore));

        // --- Clean code checks ----------------------------------------------
        $cleanScore = 100;
        $longLines = 0;
        $commentLines = 0;
        foreach ($lines as $line) {
            if (strlen($line) > 120) $longLines++;
            if (preg_match('/^\s*(\/\/|#|\*|\/\*)/', $line)) $commentLines++;
        }
        if ($longLines > 0) {
            $penalty = min(20, $longLines * 3);
            $cleanScore -= $penalty;
            $findings[] = ['severity' => 'info', 'title' => 'Baris terlalu panjang',
                'detail' => "$longLines baris melebihi 120 karakter. Pecah menjadi beberapa baris agar mudah dibaca."];
        }
        $commentRatio = $commentLines / $totalLines;
        if ($commentRatio < 0.03 && $totalLines > 15) {
            $cleanScore -= 15;
            $findings[] = ['severity' => 'info', 'title' => 'Minim komentar/dokumentasi',
                'detail' => 'Tambahkan komentar singkat pada bagian logika yang kompleks untuk memudahkan maintenance.'];
        }
        if (preg_match('/function\s+[a-z]/', $code) && preg_match('/function\s+[A-Z]/', $code)) {
            $cleanScore -= 10;
            $findings[] = ['severity' => 'info', 'title' => 'Konvensi penamaan tidak konsisten',
                'detail' => 'Campuran camelCase dan PascalCase pada nama fungsi ditemukan. Pilih satu konvensi dan konsisten.'];
        }
        if (preg_match('/\bTODO\b|\bFIXME\b/i', $code)) {
            $cleanScore -= 5;
            $findings[] = ['severity' => 'info', 'title' => 'Masih ada penanda TODO/FIXME',
                'detail' => 'Selesaikan bagian yang masih ditandai TODO sebelum submit final.'];
        }
        $cleanScore = max(0, min(100, $cleanScore));

        if (empty($findings)) {
            $findings[] = ['severity' => 'info', 'title' => 'Tidak ada masalah signifikan terdeteksi',
                'detail' => 'Kode cukup rapi pada pemeriksaan pola dasar. Tetap perhatikan edge case dan penanganan error.'];
        }

        $overall = $this->weightedOverall($cleanScore, $securityScore, $efficiencyScore);
        $summary = "Audit otomatis (mode lokal) menilai kode ini dengan skor keseluruhan {$overall}/100. "
                 . "Fokus perbaikan utama: " . $this->topWeakArea($cleanScore, $securityScore, $efficiencyScore) . ".";

        return [
            'clean_code_score' => $cleanScore,
            'security_score'   => $securityScore,
            'efficiency_score' => $efficiencyScore,
            'overall_score'    => $overall,
            'summary'          => $summary,
            'findings'         => $findings,
        ];
    }

    private function weightedOverall(int $clean, int $sec, int $eff): int
    {
        // Keamanan diberi bobot lebih besar karena kritikal untuk kode produksi.
        return (int) round(($clean * 0.35) + ($sec * 0.4) + ($eff * 0.25));
    }

    private function topWeakArea(int $clean, int $sec, int $eff): string
    {
        $areas = ['Clean Code' => $clean, 'Keamanan' => $sec, 'Efisiensi' => $eff];
        asort($areas);
        return array_key_first($areas);
    }
}
