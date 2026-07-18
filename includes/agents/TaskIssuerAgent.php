<?php
/**
 * SkillSync AI — Agent Task Issuer
 *
 * Bertugas memilih/merekomendasikan studi kasus dari bank soal (tabel `tasks`)
 * yang paling relevan untuk siswa tertentu, diprioritaskan pada kategori yang
 * skornya paling lemah (personalized learning path). Task bank sendiri diisi
 * oleh mitra industri lewat halaman tasks.php — agent berperan memilih urutan
 * penyajian yang paling bermanfaat bagi tiap siswa.
 */
class TaskIssuerAgent
{
    public function recommendedTasks(int $userId, int $limit = 3): array
    {
        $pdo = db();

        // Ambil profil skill siswa untuk tahu area terlemah
        $stmt = $pdo->prepare('SELECT clean_code_avg, security_avg, efficiency_avg FROM skill_profiles WHERE user_id = ?');
        $stmt->execute([$userId]);
        $profile = $stmt->fetch();

        $weakCategorySlug = null;
        if ($profile) {
            $areas = [
                'keamanan-aplikasi' => $profile['security_avg'],
                'data-backend'      => $profile['efficiency_avg'],
                'web-development'   => $profile['clean_code_avg'],
            ];
            asort($areas);
            $weakCategorySlug = array_key_first($areas);
        }

        // Task yang belum pernah dikerjakan siswa ini
        $sql = "SELECT t.*, c.name AS category_name, c.slug AS category_slug
                FROM tasks t
                JOIN task_categories c ON c.id = t.category_id
                WHERE t.is_active = 1
                  AND t.id NOT IN (SELECT task_id FROM submissions WHERE user_id = ?)";
        $params = [$userId];

        if ($weakCategorySlug) {
            $sql .= " ORDER BY (c.slug = ?) DESC, t.created_at DESC";
            $params[] = $weakCategorySlug;
        } else {
            $sql .= " ORDER BY t.created_at DESC";
        }
        $sql .= " LIMIT " . (int) $limit;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
