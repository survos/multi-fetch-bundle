<?php
declare(strict_types=1);

namespace Survos\MultiFetchBundle\Contract;

use Survos\MultiFetchBundle\Contract\DTO\FetchOptions;

interface ConcurrentFetcherInterface
{
    /**
     * @param iterable<int|string,array{url:string,method?:string,headers?:array,body?:string|resource|null}> $requests
     * @return iterable<int|string,array{status:int,headers:array,body:string}>
     */
    public function fetchMany(iterable $requests, FetchOptions $options): iterable;
}
