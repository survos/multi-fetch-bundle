<?php
declare(strict_types=1);

namespace Survos\MultiFetchBundle\Contract\Progress;

/** High-level progress signals during fetching/planning (optional). */
interface ProgressListenerInterface
{
    public function onPlanned(int $units): void;
    public function onFetchedUnit(int $unitIndex): void;
    public function onErrorUnit(int $unitIndex, string $url, string $reason): void;
}
