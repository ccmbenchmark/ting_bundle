<?php

namespace CCMBenchmark\TingBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{

    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('ting');

        $rootNode
            ->children()
                ->arrayNode('repositories')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('namespace')
                                ->isRequired()
                            ->end()
                            ->scalarNode('directory')
                                ->isRequired()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('connections')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('namespace')
                                ->isRequired()
                            ->end()
                            ->arrayNode('master')
                                ->children()
                                    ->scalarNode('host')
                                        ->isRequired()
                                    ->end()
                                    ->scalarNode('user')
                                        ->isRequired()
                                    ->end()
                                    ->scalarNode('password')
                                        ->isRequired()
                                    ->end()
                                    ->integerNode('port')
                                        ->isRequired()
                                    ->end()
                                ->end()
                            ->end()
                            ->arrayNode('slaves')
                                ->prototype('array')
                                    ->children()
                                        ->scalarNode('host')
                                            ->isRequired()
                                        ->end()
                                        ->scalarNode('user')
                                            ->isRequired()
                                        ->end()
                                        ->scalarNode('password')
                                            ->isRequired()
                                        ->end()
                                        ->integerNode('port')
                                            ->isRequired()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
