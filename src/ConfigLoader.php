<?php

declare(strict_types=1);

namespace Nibbletech\Composer\Plugins\CustomRules;

use Composer\Composer;
use Composer\IO\IOInterface;

class ConfigLoader
{
    const DEFAULT_CONFIG_FILENAME = "composer_rules.php";
    /**
     * @var Composer
     */
    private $composer;
    /**
     * @var IOInterface
     */
    private $io;

    public function __construct(
        Composer $composer,
        IOInterface $io
    ) {
        $this->composer = $composer;
        $this->io       = $io;
    }

    public function load(): ComposerRulesConfig
    {
        $config = $this->loadConfigFile();

        return $config;
    }

    private function loadConfigFile(): ComposerRulesConfig
    {
        $vendorDir = (string) $this->composer->getConfig()->get('vendor-dir');

        $filename = self::DEFAULT_CONFIG_FILENAME;
        $path     = realpath($vendorDir . "/..") . "/" . $filename;

        $this->io->info("Using config file " . $path);

        $configObject = new ComposerRulesConfig(
            $this->composer,
            $this->io
        );

        // No config file so just return the default
        /** @psalm-suppress MissingFile */
        if (file_exists($path) === false) {
            return $configObject;
        }

        $configCallable = include($path);

        if (is_callable($configCallable) === false) {
            $this->io->error("Composer Rules plugin config invalid. Ensure it is returning a callable. Falling back to default values.");
            return $configObject;
        }

        $configCallable($configObject);

        return $configObject;
    }
}
