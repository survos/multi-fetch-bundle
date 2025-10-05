<?php
declare(strict_types=1);

namespace Survos\MultiFetchBundle\Contract\Extract;

/** Extract the items array from a decoded JSON response. */
interface ExtractorInterface
{
    /** @return array<int,mixed> */
    public function items(array $decoded): array;
}
