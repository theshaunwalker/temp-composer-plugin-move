<?php

declare(strict_types=1);

namespace Nibbletech\Composer\Plugins\CustomRules;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Package\BasePackage;
use Composer\Plugin\PluginInterface;
use Composer\Plugin\PrePoolCreateEvent;

/**
 * @psalm-suppress MissingConstructor
 */
class ComposerRules implements PluginInterface
{
    /**
     * @var IOInterface
     */
    private $io;

    /**
     * @var ComposerRulesConfig
     */
    private $config;

    public function activate(Composer $composer, IOInterface $io)
    {
        $this->io = $io;
        $configLoader = new  ConfigLoader($composer, $io);
        $this->config = $configLoader->load();

        $this->init($composer, $io);
    }

    public function deactivate(Composer $composer, IOInterface $io)
    {
        // TODO: Implement deactivate() method.
    }

    public function uninstall(Composer $composer, IOInterface $io)
    {
        // nothing needed
    }

    public function init(Composer $composer, IOInterface $io): void
    {
        /**
         * Register rules with composer event dispatcher.
         */
        foreach ($this->config->getEnabledRules() as $enabledRule) {
            if ($enabledRule instanceof EventSubscriberInterface) {
                $composer->getEventDispatcher()->addSubscriber($enabledRule);
            }
        }
    }
}
