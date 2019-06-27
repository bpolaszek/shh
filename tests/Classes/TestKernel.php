<?php

namespace BenTools\Shh\Tests\Classes;

use BenTools\Shh\ShhBundle;
use Nyholm\BundleTest\CompilerPass\PublicServicePass;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\RouteCollectionBuilder;

final class TestKernel extends Kernel
{
    use MicroKernelTrait;

    protected $cacheDir;
    protected $logDir;

    public function __construct(string $environment, bool $debug)
    {
        parent::__construct($environment, $debug);
        $uniqid = uniqid('webpush_test', true);
        $this->cacheDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $uniqid . DIRECTORY_SEPARATOR . 'cache';
        $this->logDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $uniqid . DIRECTORY_SEPARATOR . 'logs';
    }

    public function registerBundles()
    {
        return [
            new FrameworkBundle(),
            new ShhBundle(),
        ];
    }

    protected function configureRoutes(RouteCollectionBuilder $routes)
    {
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader)
    {
        $c->setParameter('kernel.secret', '%env(APP_SECRET)%');
        $c->setParameter('some_encrypted_secret', '%env(shh:A_BIG_SECRET)%');
        $c->loadFromExtension('shh', [
            'private_key_file' => dirname(__DIR__). '/.keys/private.pem',
            'public_key_file' => dirname(__DIR__). '/.keys/public.pem',
        ]);
        $c->addCompilerPass(new PublicServicePass());
    }

    public function getCacheDir()
    {
        return $this->cacheDir;
    }

    public function getLogDir()
    {
        return $this->logDir;
    }
}
