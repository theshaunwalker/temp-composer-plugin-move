<?php

use Nibbletech\Composer\Plugins\CustomRules\ComposerRulesConfig;
use Nibbletech\Composer\Plugins\CustomRules\Rules\BlockDowngrades\BlockDowngrades;
use Nibbletech\Composer\Plugins\CustomRules\Rules\ExplicitPhp\ExplicitPhp;
use Nibbletech\Composer\Plugins\CustomRules\Rules\ExplicitPhp\ExplicitPhpConfig;

return function (ComposerRulesConfig  $config) {

    $config->enableRule(
        ExplicitPhp::class,
            (new ExplicitPhpConfig())
                ->addIgnores('phenx*')
                ->addPackagesToCheck('mycompany/*')
    );
    $config->enableRule(
        BlockDowngrades::class
    );

};