<?php

namespace BenTools\Shh\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\HttpKernel\Kernel;

final class Configuration implements ConfigurationInterface
{
    /**
     * @inheritDoc
     */
    public function getConfigTreeBuilder()
    {
        if (Kernel::MAJOR_VERSION < 4) {
            $treeBuilder = new TreeBuilder();
            $rootNode = $treeBuilder->root('shh');
        } else {
            $treeBuilder = new TreeBuilder('shh');
            $rootNode = $treeBuilder->getRootNode();
        }

        $rootNode
            ->children()
                ->scalarNode('private_key_file')->defaultValue('%kernel.project_dir%/.keys/private.pem')->end()
                ->scalarNode('public_key_file')->defaultValue('%kernel.project_dir%/.keys/public.pem')->end()
                ->scalarNode('passphrase')->defaultValue('%env(SHH_PASSPHRASE)%')->end()
            ->end();

        return $treeBuilder;
    }
}
