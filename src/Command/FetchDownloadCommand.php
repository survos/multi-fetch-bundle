<?php
declare(strict_types=1);

namespace Survos\MultiFetchBundle\Command;

use Survos\MultiFetchBundle\Fetch\SymfonyConcurrentFetcher;
use Survos\MultiFetchBundle\Pagination\OffsetPagination;
use Survos\MultiFetchBundle\Extract\SolrDocsExtractor;
use Survos\MultiFetchBundle\Contract\Fetch\FetchOptions;
use Survos\JsonlBundle\Service\OrderedAppenderTarget;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Generic concurrent downloader that fetches offset/rows paginated endpoints
 * and writes JSONL.GZ directly using the JsonlBundle writer.
 *
 * Example:
 *   bin/console fetch:download \
 *     "https://api.deutsche-digitale-bibliothek.de/search/index/search/select?q=provider_id:4Q7JODFZGYQNB7QI2PHZXL52R3UUCTX" \
 *     data/i1.jsonl.gz \
 *     --format=solr \
 *     --concurrent=4 \
 *     --rows=100
 */
#[AsCommand(name: 'fetch:download', description: 'Download offset-paginated API data into a JSONL.GZ file.')]
final class FetchDownloadCommand extends Command
{
    public function __construct(
        private readonly SymfonyConcurrentFetcher $fetcher,
    ) {
        parent::__construct();
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument('url', 'Base URL (with any fixed query params, e.g. q=...)')]
        string $url,
        #[Argument('output', 'Destination .jsonl.gz path')]
        string $output,

        #[Option('rows', 'Rows per page (default 100)')]
        int $rows = 100,

        #[Option('format', 'API format (solr|items)')]
        string $format = 'solr',

        #[Option('concurrent', 'Number of concurrent requests')]
        int $concurrent = 8,

        #[Option('retries', 'Retries per request')]
        int $retries = 3,

        #[Option('timeout', 'Timeout per request (seconds)')]
        float $timeout = 45.0
    ): int {
        $io->title('JSONL Fetch Downloader');
        $io->writeln("Base URL: <info>$url</info>");
        $io->writeln("Output:   <info>$output</info>");
        $io->writeln("Rows:     <info>$rows</info>");
        $io->writeln("Format:   <info>$format</info>");
        $io->writeln("Concurrent: <info>$concurrent</info>");

        $headers = ['Accept' => 'application/json'];
        $options = new FetchOptions(concurrency: $concurrent, retries: $retries, timeout: $timeout, headers: $headers);

        // Determine extractor based on format
        $extractor = match ($format) {
            'solr' => new SolrDocsExtractor(),
            'items' => new \Survos\MultiFetchBundle\Extract\ItemsArrayExtractor(),
            default => throw new \InvalidArgumentException("Unknown format: $format"),
        };

        $writer = new OrderedAppenderTarget($output, onAppend: function (int $block, int $count, int $total) use ($io) {
            $io->info("Appended block $block ($count lines, total=$total)");
        });

        // Probe: fetch one page with rows=0 to get numFound
        $probeUrl = $url . (str_contains($url, '?') ? '&' : '?') . 'rows=0&start=0';
        $io->info("Probing total count via $probeUrl");
        $probeResp = $this->fetcher->http()->request('GET', $probeUrl, ['headers' => $headers]);
        $probeData = json_decode($probeResp->getContent(false), true);
        $numFound = (int)($probeData['response']['numFound'] ?? -1);
        if ($numFound < 0) {
            $io->error('Could not determine total from probe.');
            return Command::FAILURE;
        }
        $io->success("Total records: $numFound");

        $pagination = new OffsetPagination($rows);
        $pages = iterator_to_array($pagination->plan($numFound, 0));

        $ok = 0;
        $err = 0;
        $start = microtime(true);

        $this->fetcher->fetchMany(
            (function () use ($url, $rows, $pages) {
                foreach ($pages as $block => $startAt) {
                    yield sprintf('%s%sstart=%d&rows=%d',
                        $url,
                        str_contains($url, '?') ? '&' : '?',
                        $startAt,
                        $rows
                    );
                }
            })(),
            $options,
            onSuccess: function (string $u, string $content) use (&$ok, $extractor, $writer, $io) {
                $decoded = json_decode($content, true);
                $items = $extractor->items($decoded ?? []);
                $block = $this->parseStart($u);
                $writer->provide($block, $items);
                $ok++;
            },
            onError: function (string $u, \Throwable $e) use (&$err, $io) {
                $err++;
                $io->warning("Failed $u: " . $e->getMessage());
            }
        );

        $writer->close();

        $elapsed = microtime(true) - $start;
        $io->success("Done. ok=$ok err=$err in " . number_format($elapsed, 2) . "s");
        return Command::SUCCESS;
    }

    private function parseStart(string $url): int
    {
        parse_str(parse_url($url, PHP_URL_QUERY) ?? '', $query);
        return isset($query['start']) ? ((int)$query['start'] / (int)($query['rows'] ?? 1)) : 0;
    }
}
