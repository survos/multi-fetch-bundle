<?php

namespace Survos\MultiFetchBundle;

use Survos\CoreBundle\Request\ParameterResolver;
use Survos\CoreBundle\Service\ChunkDownloader;
use Survos\CoreBundle\Service\SurvosUtils;
use Survos\CoreBundle\Twig\TwigExtension;
use Survos\MultiFetchBundle\Command\FetchDownloadCommand;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SurvosMultiFetchBundle extends AbstractBundle
{
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {

        $builder
            ->autowire(FetchDownloadCommand::class)
            ->setPublic(true)
            ->setAutoconfigured(true)
            ->setAutowired(true);

    }

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
            // enable the twig extension?
            ->booleanNode('enabled')->defaultTrue()->end()
            ->end();
    }
}
