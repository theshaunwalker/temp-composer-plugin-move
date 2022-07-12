<?php

declare(strict_types=1);

namespace Nibbletech\Composer\Plugins\CustomRules;

use Composer\Composer;
use Composer\IO\IOInterface;
use Nibbletech\Composer\Plugins\CustomRules\Rules\Rule;

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
        $configObject = new ComposerRulesConfig();

        $config = $this->loadConfigFile();

        foreach ($config['rules'] as $rule => $ruleConfig) {
            /** @var Rule */
            $configObject->enableRule(
                new $rule(
                    $this->composer,
                    $this->io,
                    $ruleConfig
                )
            );
        }

        return $configObject;
    }

    /**
     * @psalm-suppress MixedReturnTypeCoercion
     *
     * @return array{rules:array<interface-string<Rule>, array>}
     */
    private function loadConfigFile(): array
    {
        $vendorDir = (string) $this->composer->getConfig()->get('vendor-dir');

        $filename = self::DEFAULT_CONFIG_FILENAME;
        $path     = realpath($vendorDir . "/..") . "/" . $filename;

        $this->io->info("Using config file " . $path);

        if (file_exists($path)) {
            /** @psalm-suppress MissingFile */
            $config = include($filename);
        } else {
            $this->io->error("Composer Rules plugin: Missing config file, using default config. Please create it at " . $path);
            $config = [
                'rules' => []
            ];
        }

        if (is_array($config) === false) {
            $this->io->error("Configuration file is invalid. Ensure config file is returning an array.");
            die();
        }

        $config = $this->cleanConfigFileContents($config);

        return $config;
    }

    /**
     * @param array $config
     *
     * @return array{rules:array<interface-string<Rule>, array>}
     */
    private function cleanConfigFileContents(array $config): array
    {
        $cleanedConfig = [];

        $cleanedRules = [];
        /** @psalm-suppress MixedAssignment */
        foreach ($config['rules'] ?? [] as $rule => $ruleConfig) {
            $rule = (string) $rule;
            if (is_array($ruleConfig) === false) {
                $rule       = (string) $ruleConfig;
                $ruleConfig = [];
            }

            if (in_array(Rule::class, class_implements($rule)) === false) {
                continue;
            }

            /** @var interface-string<Rule> $rule */
            $cleanedRules[$rule] = $ruleConfig;
        }

        $cleanedConfig['rules'] = $cleanedRules;

        return $cleanedConfig;
    }
}
