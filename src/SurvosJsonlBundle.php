<?php
declare(strict_types=1);

namespace Survos\JsonlBundle;

use Survos\JsonlBundle\Contract\BlockNamingStrategyInterface;
use Survos\JsonlBundle\Contract\ConcurrentFetcherInterface;
use Survos\JsonlBundle\Contract\RetryStrategyInterface;
use Survos\JsonlBundle\Contract\StateStoreInterface;
use Survos\JsonlBundle\Contract\UrlBuilderInterface;
use Survos\JsonlBundle\Fetch\SymfonyConcurrentFetcher;
use Survos\JsonlBundle\Path\DefaultUrlBuilder;
use Survos\JsonlBundle\Path\SimpleBlockNamingStrategy;
use Survos\JsonlBundle\Retry\ExponentialBackoffRetry;
use Survos\JsonlBundle\State\FileStateStore;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class SurvosJsonlBundle extends AbstractBundle
{
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        // Concrete services
        $builder->autowire(SimpleBlockNamingStrategy::class)->setAutowired(true)->setAutoconfigured(true)->setPublic(false);
        $builder->autowire(DefaultUrlBuilder::class)->setAutowired(true)->setAutoconfigured(true)->setPublic(false);
        $builder->autowire(FileStateStore::class)->setAutowired(true)->setAutoconfigured(true)->setPublic(false);

        // Fetcher + default retry policy
        $builder->autowire(ExponentialBackoffRetry::class)->setAutowired(true)->setAutoconfigured(true)->setPublic(false);
        $builder->autowire(SymfonyConcurrentFetcher::class)->setAutowired(true)->setAutoconfigured(true)->setPublic(false);

        // Interface aliases
        $builder->setAlias(BlockNamingStrategyInterface::class, SimpleBlockNamingStrategy::class)->setPublic(false);
        $builder->setAlias(UrlBuilderInterface::class,        DefaultUrlBuilder::class)->setPublic(false);
        $builder->setAlias(StateStoreInterface::class,        FileStateStore::class)->setPublic(false);
        $builder->setAlias(ConcurrentFetcherInterface::class, SymfonyConcurrentFetcher::class)->setPublic(false);
        $builder->setAlias(RetryStrategyInterface::class,     ExponentialBackoffRetry::class)->setPublic(false);
    }
}
