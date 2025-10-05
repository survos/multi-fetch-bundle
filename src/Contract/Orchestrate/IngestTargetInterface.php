<?php
declare(strict_types=1);

namespace Survos\MultiFetchBundle\Contract\Orchestrate;

/**
 * Where extracted items land.
 * The JsonlBundle will provide an adapter that appends to JSONL in order.
 */
interface IngestTargetInterface
{
    /** Called when a unit (e.g., block) is ready with its extracted items. */
    public function provide(int $unitIndex, array $items): void;

    /** Called when ingestion is done (flush/close finalization). */
    public function finish(): void;
}
