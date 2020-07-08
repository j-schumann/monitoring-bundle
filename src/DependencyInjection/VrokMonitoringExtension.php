<?php

declare(strict_types=1);

namespace Vrok\MonitoringBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

class VrokMonitoringExtension extends ConfigurableExtension
{
    protected function loadInternal(array $mergedConfig, ContainerBuilder $container)
    {
        $container->setParameter('vrok_monitoring.monitor_address', $config['monitor_address']);
        $container->setParameter('vrok_monitoring.app_name', $config['app_name']);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');
    }
}
