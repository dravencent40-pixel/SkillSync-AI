<?php
/**
 * SkillSync AI — AIClient
 * Lapisan tipis di atas Anthropic Messages API.
 *
 * Jika ANTHROPIC_API_KEY tidak diset, isAvailable() akan mengembalikan false
 * dan setiap Agent (Reviewer, Mentor, dst) otomatis jatuh ke logika heuristik
 * lokal miliknya masing-masing — sehingga project tetap bisa didemokan
 * end-to-end tanpa API key maupun koneksi internet.
 */
class AIClient
{
    private string $apiKey;
    private string $model;

    public function __construct()
    {
        $this->apiKey = getenv('ANTHROPIC_API_KEY') ?: '';
        $this->model  = defined('AI_MODEL') ? AI_MODEL : 'claude-sonnet-4-6';
    }

    public function isAvailable(): bool
    {
        return $this->apiKey !== '';
    }

    /**
     * Kirim satu permintaan ke Claude. Mengembalikan teks jawaban, atau null jika gagal.
     *
     * @param string $system   System prompt (mendefinisikan peran agent)
     * @param array  $messages Riwayat pesan [['role'=>'user'|'assistant','content'=>'...'], ...]
     */
    public function complete(string $system, array $messages, int $maxTokens = 1024): ?string
    {
        if (!$this->isAvailable()) {
            return null;
        }

        $payload = json_encode([
            'model'      => $this->model,
            'max_tokens' => $maxTokens,
            'system'     => $system,
            'messages'   => $messages,
        ]);

        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'x-api-key: ' . $this->apiKey,
                'anthropic-version: 2023-06-01',
            ],
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $httpCode !== 200) {
            error_log('AIClient error: HTTP ' . $httpCode . ' ' . $response);
            return null;
        }

        $data = json_decode($response, true);
        $text = '';
        foreach ($data['content'] ?? [] as $block) {
            if (($block['type'] ?? '') === 'text') {
                $text .= $block['text'];
            }
        }
        return $text !== '' ? $text : null;
    }

    /**
     * Sama seperti complete(), tapi mengharapkan (dan membersihkan) jawaban JSON murni.
     * Mengembalikan array hasil decode, atau null jika gagal/tidak tersedia.
     */
    public function completeJson(string $system, array $messages, int $maxTokens = 1024): ?array
    {
        $text = $this->complete($system . "\n\nPENTING: Balas HANYA dengan objek JSON valid, tanpa teks lain, tanpa markdown code fence.", $messages, $maxTokens);
        if ($text === null) {
            return null;
        }
        $clean = trim(preg_replace('/```json|```/', '', $text));
        $decoded = json_decode($clean, true);
        return is_array($decoded) ? $decoded : null;
    }
}
