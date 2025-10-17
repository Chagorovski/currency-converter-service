<?php
namespace App\Service;

use Throwable;

class MetricsClient
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
            return;
        }

        $tagStr = '';
        foreach ($tags as $key => $value) {
            $tagStr .= ','
                . preg_replace('/[ ,]/', '_', (string)$key) . '='
                . preg_replace('/[ ,]/', '_', (string)$value);
        }

        $fieldParts = [];
        foreach ($fields as $key => $value) {
            $fieldParts[] =
                is_numeric($value) ?
                    ($key . '=' . (is_int($value) ? $value : (float)$value)) :
                    ($key . '="' . str_replace('"', '\"', (string)$value) . '"');
        }

        if (!$fieldParts) {
            return;
        }

        $body = $measurement . $tagStr . ' ' . implode(',', $fieldParts);

        try {
            $curlSession = curl_init();
            curl_setopt_array($curlSession, [
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
            curl_exec($curlSession);
            curl_close($curlSession);
        } catch (Throwable) { /* fire-and-forget */ }
    }
}
