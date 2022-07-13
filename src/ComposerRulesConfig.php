<?php

declare(strict_types=1);

namespace Nibbletech\Composer\Plugins\CustomRules;

use Composer\Composer;
use Composer\IO\IOInterface;
use Nibbletech\Composer\Plugins\CustomRules\Rules\Rule;

class ComposerRulesConfig
{
    /**
     * @var Composer
     */
    private $composer;
    /**
     * @var IOInterface
     */
    private $io;
    /**
     * @var array<string, Rule>
     */
    private $enabledRules = [];

    public function __construct(
        Composer $composer,
        IOInterface $io
    ) {
        $this->composer = $composer;
        $this->io = $io;
    }

    /**
     * @param interface-string<Rule> $ruleClass
     * @param object|null $ruleConfig
     *
     * @return void
     */
    public function enableRule($ruleClass, $ruleConfig = null): void
    {
        $bridge = new ComposerRunnerBridge(
            $this->composer,
            $this->io
        );

        $rule = new $ruleClass(
            $bridge,
            $ruleConfig
        );

        $this->enabledRules[get_class($rule)] = $rule;
    }

    /**
     * @return array<string, Rule>
     */
    public function getEnabledRules(): array
    {
        return $this->enabledRules;
    }
}
