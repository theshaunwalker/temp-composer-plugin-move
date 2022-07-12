<?php

declare(strict_types=1);

namespace Nibbletech\Composer\Plugins\CustomRules;

use Composer\Composer;
use Composer\IO\IOInterface;
use Nibbletech\Composer\Plugins\CustomRules\Rules\Rule;

class ComposerRulesConfig
{
    /**
     * @var array<string, Rule>
     */
    private $enabledRules = [];
    
    public function enableRule(Rule $rule): void
    {
        $this->enabledRules[get_class($rule)] = $rule;
    }

    private function init(): void
    {

    }

    /**
     * @return array<string, Rule>
     */
    public function getEnabledRules(): array
    {
        return $this->enabledRules;
    }
}
