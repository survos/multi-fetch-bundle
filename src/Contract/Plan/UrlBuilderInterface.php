<?php
declare(strict_types=1);

namespace Survos\MultiFetchBundle\Contract\Plan;

/** Turns baseUrl and query arrays into canonical URLs. */
interface UrlBuilderInterface
{
    /**
     * @param array<string,scalar|array> $query
     */
    public function build(string $baseUrl, array $query): string;
}
