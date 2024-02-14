<?php

/** @noinspection PhpIllegalPsrClassPathInspection */

declare(strict_types=1);

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;

class AppKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): array
    {
        $bundles = [
            new FrameworkBundle(),
            new MonologBundle(),
            new Vrok\MonitoringBundle\VrokMonitoringBundle(),
        ];

        return $bundles;
    }

    public function getProjectDir(): string
    {
        return __DIR__;
    }

    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $container->setParameter('kernel.project_dir', __DIR__);

        $loader->load(__DIR__.'/config/config.yaml');
    }
}
