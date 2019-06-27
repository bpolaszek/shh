<?php

namespace BenTools\Shh\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    /**
     * @inheritDoc
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('shh');

        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('private_key_file')->defaultValue('%kernel.project_dir%/.keys/private.pem')->end()
                ->scalarNode('public_key_file')->defaultValue('%kernel.project_dir%/.keys/public.pem')->end()
                ->scalarNode('passphrase')->defaultValue('%env(SHH_PASSPHRASE)%')->end()
            ->end();

        return $treeBuilder;
    }
}
