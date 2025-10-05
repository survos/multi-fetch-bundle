<?php
declare(strict_types=1);

namespace Survos\MultiFetchBundle\Contract\Orchestrate;

use Survos\MultiFetchBundle\Contract\Fetch\FetchOptions;
use Survos\MultiFetchBundle\Contract\Pagination\ProbeResult;
use Survos\MultiFetchBundle\Contract\Pagination\ResumePoint;

/**
 * High-level orchestration: given a set of planned units, fetch them concurrently,
 * extract items, and pass to an IngestTargetInterface (e.g., JSONL appender).
 */
interface OrchestratorInterface
{
    /**
     * @param iterable<array{url:string, unit:int}> $units
     * @param IngestTargetInterface $target
     */
    public function run(
        iterable $units,
        FetchOptions $options,
        IngestTargetInterface $target
    ): OrchestratorResult;
}

/** Minimal result DTO */
final class OrchestratorResult
{
    public function __construct(
        public readonly int $ok,
        public readonly int $err,
        public readonly float $seconds
    ) {}
}
