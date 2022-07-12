<?php

return [
    /**
     * Array of rules to use defined as
     * Class Name => Config Array
     */
    'rules' => [
        \Nibbletech\Composer\Plugins\CustomRules\Rules\ExplicitPhp::class,
        \Nibbletech\Composer\Plugins\CustomRules\Rules\BlockDowngrades::class,
    ]

];