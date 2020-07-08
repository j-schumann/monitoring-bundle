<?php

declare(strict_types=1);

namespace Vrok\MonitoringBundle\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Vrok\MonitoringBundle\DependencyInjection\Configuration;

class ConfigurationTest extends TestCase
{
    public function testReturnsTreeBuilder()
    {
        $config = new Configuration();

        $res = $config->getConfigTreeBuilder();
        self::assertInstanceOf(TreeBuilder::class, $res);

        $tree = $res->buildTree();
        $this->assertSame('vrok_monitoring', $tree->getName());

        $address = $res->getRootNode()->find('monitor_address');
        self::assertNotNull($address);
    }
}
