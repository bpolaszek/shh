<?php

namespace BenTools\Shh;

use Symfony\Component\DependencyInjection\EnvVarProcessorInterface;

final class ShhEnvVarProcessor implements EnvVarProcessorInterface
{
    /**
     * @var Shh
     */
    private $shh;

    /**
     * ShhEnvVarProcessor constructor.
     *
     * @param Shh $shh
     */
    public function __construct(Shh $shh)
    {
        $this->shh = $shh;
    }

    /**
     * @inheritDoc
     */
    public function getEnv($prefix, $name, \Closure $getEnv)
    {
        $env = $getEnv($name);

        return $this->shh->decrypt($env);
    }

    /**
     * @inheritDoc
     */
    public static function getProvidedTypes()
    {
        return [
            'shh' => 'string',
        ];
    }
}
