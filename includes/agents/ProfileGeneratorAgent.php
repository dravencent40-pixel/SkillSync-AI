<?php
/**
 * SkillSync AI — Agent Profile Generator
 *
 * Mengagregasi seluruh ai_reviews milik seorang siswa menjadi satu
 * skill_profiles yang transparan dan siap direkomendasikan ke mitra industri.
 */
class ProfileGeneratorAgent
{
    public function regenerate(int $userId): array
    {
        $pdo = db();

        $stmt = $pdo->prepare(
            'SELECT r.clean_code_score, r.security_score, r.efficiency_score, r.overall_score
             FROM ai_reviews r
             JOIN submissions s ON s.id = r.submission_id
             WHERE s.user_id = ?'
        );
        $stmt->execute([$userId]);
        $reviews = $stmt->fetchAll();

        $count = count($reviews);
        if ($count === 0) {
            $data = [
                'overall_score' => 0, 'clean_code_avg' => 0, 'security_avg' => 0,
                'efficiency_avg' => 0, 'tasks_completed' => 0, 'badge' => 'Pemula',
                'strengths' => null, 'weaknesses' => null,
            ];
        } else {
            $avg = fn(string $key) => (int) round(array_sum(array_column($reviews, $key)) / $count);
            $clean = $avg('clean_code_score');
            $sec   = $avg('security_score');
            $eff   = $avg('efficiency_score');
            $overall = $avg('overall_score');

            $areas = ['Clean Code' => $clean, 'Keamanan' => $sec, 'Efisiensi' => $eff];
            arsort($areas);
            $strengths = array_key_first($areas);
            asort($areas);
            $weaknesses = array_key_first($areas);

            $data = [
                'overall_score'   => $overall,
                'clean_code_avg'  => $clean,
                'security_avg'    => $sec,
                'efficiency_avg'  => $eff,
                'tasks_completed' => $count,
                'badge'           => badge_from_score($overall),
                'strengths'       => $strengths,
                'weaknesses'      => $weaknesses,
            ];
        }

        $exists = $pdo->prepare('SELECT id FROM skill_profiles WHERE user_id = ?');
        $exists->execute([$userId]);

        if ($exists->fetch()) {
            $sql = 'UPDATE skill_profiles SET overall_score=?, clean_code_avg=?, security_avg=?, efficiency_avg=?,
                    tasks_completed=?, badge=?, strengths=?, weaknesses=? WHERE user_id=?';
            $pdo->prepare($sql)->execute([
                $data['overall_score'], $data['clean_code_avg'], $data['security_avg'], $data['efficiency_avg'],
                $data['tasks_completed'], $data['badge'], $data['strengths'], $data['weaknesses'], $userId,
            ]);
        } else {
            $sql = 'INSERT INTO skill_profiles (user_id, overall_score, clean_code_avg, security_avg, efficiency_avg,
                    tasks_completed, badge, strengths, weaknesses) VALUES (?,?,?,?,?,?,?,?,?)';
            $pdo->prepare($sql)->execute([
                $userId, $data['overall_score'], $data['clean_code_avg'], $data['security_avg'], $data['efficiency_avg'],
                $data['tasks_completed'], $data['badge'], $data['strengths'], $data['weaknesses'],
            ]);
        }

        return $data;
    }
}
