<?php
declare(strict_types=1);

namespace Survos\MultiFetchBundle;

use Survos\MultiFetchBundle\Command\FetchDownloadCommand;
use Survos\MultiFetchBundle\Contract\ConcurrentFetcherInterface;
use Survos\MultiFetchBundle\Contract\RetryStrategyInterface;
use Survos\MultiFetchBundle\Fetch\SymfonyConcurrentFetcher;
use Survos\MultiFetchBundle\Retry\ExponentialBackoffRetry;
use Survos\MultiFetchBundle\Service\ChunkDownloader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class SurvosMultiFetchBundle extends AbstractBundle
{
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        // Core services
        $builder->autowire(ExponentialBackoffRetry::class)
            ->setAutowired(true)->setAutoconfigured(true)->setPublic(false);

        $builder->autowire(SymfonyConcurrentFetcher::class)
            ->setAutowired(true)->setAutoconfigured(true)->setPublic(false);

        $builder->autowire(ChunkDownloader::class)
            ->setAutowired(true)->setAutoconfigured(true)->setPublic(false);

        // Interface aliases
        $builder->setAlias(ConcurrentFetcherInterface::class, SymfonyConcurrentFetcher::class)->setPublic(false);
        $builder->setAlias(RetryStrategyInterface::class, ExponentialBackoffRetry::class)->setPublic(false);

        // Interface aliases (only if app hasn't provided its own)
        if (!$builder->hasAlias(ConcurrentFetcherInterface::class)) {
            $builder->setAlias(ConcurrentFetcherInterface::class, SymfonyConcurrentFetcher::class)
                ->setPublic(false);
        }

        if (!$builder->hasAlias(RetryStrategyInterface::class)) {
            $builder->setAlias(RetryStrategyInterface::class, ExponentialBackoffRetry::class)
                ->setPublic(false);
        }

        $builder->autowire(SymfonyConcurrentFetcher::class)
            ->setAutowired(true)->setAutoconfigured(true)->setPublic(true);
        $builder->autowire(ExponentialBackoffRetry::class)->setAutowired(true)
            ->setAutoconfigured(true)->setPublic(true);

        // Command (public optional when using #[AsCommand], but fine to keep public)
        $builder->autowire(FetchDownloadCommand::class)
            ->setAutowired(true)->setAutoconfigured(true)->setPublic(true);
    }
}
