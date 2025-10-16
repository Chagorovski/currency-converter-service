<?php
namespace App\Service;

use Throwable;

final class MetricsClient
{
    public function __construct(
        private string $url,
        private string $token,
        private string $org,
        private string $bucket,
    ){}

    public function record(string $measurement, array $fields = [], array $tags = []): void
    {
        if (!$this->url || !$this->token || !$this->org || !$this->bucket) {
            return;
        }

        if (empty($fields)) {
            return; // nothing to write
        }

        $tagStr = '';
        foreach ($tags as $k => $v) {
            $tagStr .= ',' . preg_replace('/[ ,]/', '_', (string)$k) . '=' . preg_replace('/[ ,]/', '_', (string)$v);
        }

        $fieldParts = [];
        foreach ($fields as $k => $v) {
            $fieldParts[] =
                is_numeric($v) ?
                    ($k . '=' . (is_int($v) ? $v : (float)$v)) :
                    ($k . '="' . str_replace('"', '\"', (string)$v) . '"');
        }

        if (!$fieldParts) {
            return;
        }

        $body = $measurement . $tagStr . ' ' . implode(',', $fieldParts);

        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => rtrim($this->url, '/') . '/api/v2/write?org=' . rawurlencode($this->org)
                    . '&bucket=' . rawurlencode($this->bucket) . '&precision=ms',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Token ' . $this->token,
                    'Content-Type: text/plain; charset=utf-8',
                ],
                CURLOPT_TIMEOUT => 1,
            ]);
            curl_exec($ch);
            curl_close($ch);
        } catch (Throwable) { /* fire-and-forget */ }
    }
}
