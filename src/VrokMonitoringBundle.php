<?php

declare(strict_types=1);

namespace Vrok\MonitoringBundle;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class VrokMonitoringBundle extends AbstractBundle
{
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->scalarNode('monitor_address')
                    ->isRequired()
                ->end()
                ->scalarNode('app_name')
                    ->defaultValue('Symfony App')
                ->end()
            ->end()
        ;
    }

    public function loadExtension(
        array $config,
        ContainerConfigurator $container,
        ContainerBuilder $builder
    ): void {
        $container->import(__DIR__.'/../config/services.yaml');
        $container->parameters()->set('vrok_monitoring.monitor_address', $config['monitor_address']);
        $container->parameters()->set('vrok_monitoring.app_name', $config['app_name']);
    }
}
