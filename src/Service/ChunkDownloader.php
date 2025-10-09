<?php
declare(strict_types=1);

namespace Survos\MultiFetchBundle\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final class ChunkDownloader
{
    public function __construct(private readonly HttpClientInterface $http) {}

    /**
     * Download a URL to $destination atomically: write to .part then rename.
     * Ensures parent directories exist (mkdir -p).
     */
    public function download(string $url, string $destination, ?float $timeout = 0.0, array $headers = []): void
    {
        $dir = \dirname($destination);
        if (!\is_dir($dir) && !\mkdir($dir, 0o775, true) && !\is_dir($dir)) {
            throw new \RuntimeException("Cannot create directory: $dir");
        }
        $tmp = $destination . '.part';

        $response = $this->http->request('GET', $url, [
            'headers' => $headers,
            'timeout' => $timeout ?: null,
        ]);

        $fh = \fopen($tmp, 'wb');
        if (!$fh) {
            throw new \RuntimeException("Cannot open for writing: $tmp");
        }

        try {
            foreach ($this->http->stream($response, $timeout ?: null) as $chunk) {
                if ($chunk->isTimeout()) { continue; }
                $data = $chunk->getContent();
                if ($data !== '') {
                    $bytes = \fwrite($fh, $data);
                    if ($bytes === false) {
                        throw new \RuntimeException("Write failed: $tmp");
                    }
                }
                if ($chunk->isLast()) {
                    break;
                }
            }
        } finally {
            \fclose($fh);
        }

        if (!\rename($tmp, $destination)) {
            @\unlink($tmp);
            throw new \RuntimeException("Atomic rename failed to $destination");
        }
    }
}
