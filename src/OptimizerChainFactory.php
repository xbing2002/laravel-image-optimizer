<?php

namespace Spatie\LaravelImageOptimizer;

use Psr\Log\LoggerInterface;
use Spatie\ImageOptimizer\Optimizer;
use Spatie\ImageOptimizer\DummyLogger;
use Spatie\ImageOptimizer\OptimizerChain;
use Spatie\LaravelImageOptimizer\Exceptions\InvalidConfiguration;

class OptimizerChainFactory
{
    public static function create(array $config)
    {
        return (new OptimizerChain())
            ->useLogger(static::getLogger($config))
            ->setTimeout(intval($config['timeout']))
            ->setOptimizers(static::getOptimizers($config));
    }

    protected static function getLogger($config)
    {
        $configuredLogger = $config['log_optimizer_activity'];

        if ($configuredLogger === true) {
            return app('log');
        }

        if ($configuredLogger === false) {
            return new DummyLogger();
        }

        if (! $configuredLogger instanceof LoggerInterface) {
            throw InvalidConfiguration::notAnLogger($configuredLogger);
        }

        return new $configuredLogger;
    }

    protected static function getOptimizers(array $config)
    {
        return collect($config['optimizers'])
          ->mapWithKeys(function (array $options, $optimizerClass) use ($config) {
              if (! is_a($optimizerClass, Optimizer::class, true)) {
                  throw InvalidConfiguration::notAnOptimizer($optimizerClass);
              }

              // Initialize optimizer class
              $newOptimizerClass = new $optimizerClass();

              if (static::getBinaryPath($config)) {
                  $newOptimizerClass->setBinaryPath(self::getBinaryPath($config));
              }

              $newOptimizerClass->setOptions($options);

              return [$optimizerClass => $newOptimizerClass];
          })
          ->toArray();
    }

    public static function getBinaryPath(array $config)
    {
        return $config['binary_path'] ? $config['binary_path']:'';
    }
}
