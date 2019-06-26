<?php

namespace BenTools\Shh\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

final class ShhExtension extends Extension
{

    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $container->setParameter('env(SHH_PASSPHRASE)', null);
        $config = $this->processConfiguration($configuration, $configs);
        $container->setParameter('shh.private_key_file', $config['private_key_file']);
        $container->setParameter('shh.public_key_file', $config['public_key_file']);
        $container->setParameter('shh.passphrase', $config['passphrase']);

        //$loader = new PhpFileLoader($container, new FileLocator(\dirname(__DIR__) . '/Resources/config'));
        //$loader->load('services.php');

        $loader = new XmlFileLoader($container, new FileLocator(\dirname(__DIR__).'/Resources/config'));
        $loader->load('services.xml');
    }
}
