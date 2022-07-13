<?php

declare(strict_types=1);

namespace Nibbletech\Composer\Plugins\CustomRules\Rules\BlockDowngrades;

use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\Package\PackageInterface;
use Composer\Package\Version\VersionParser;
use Nibbletech\Composer\Plugins\CustomRules\ComposerRunnerBridge;
use Nibbletech\Composer\Plugins\CustomRules\Rules\Rule;
use Nibbletech\Composer\Plugins\CustomRules\Rules\RuleError;
use Symfony\Component\Console\Input\ArrayInput;

class BlockDowngrades implements Rule, EventSubscriberInterface
{
    /**
     * @var ComposerRunnerBridge
     */
    private $composerBridge;
    /**
     * @var PackageInterface[]
     */
    private $downgradePackages = [];


    /**
     * @psalm-suppress MissingParamType
     */
    public function __construct(
        ComposerRunnerBridge $composerBridge,
        $config = null
    ) {
        $this->composerBridge = $composerBridge;
    }

    public static function getSubscribedEvents()
    {
        return [
            'pre-package-update' => 'preUpdate',
            'post-package-update' => 'postUpdate',
        ];
    }

    public function preUpdate(PackageEvent $event): void
    {
        $operation = $event->getOperation();
        if ($operation instanceof UpdateOperation === false) {
            return;
        }

        if ($this->isADowngrade($operation)) {
            $this->downgradePackages[] = $operation->getTargetPackage();
//            throw new \Exception("Will be a downgrade ");
        }


//        die();
    }
    
    public function postUpdate(PackageEvent $event): void
    {
        if (count($this->downgradePackages) > 0) {
            $this->composerBridge->getIo()->error("Total downgraded packages: " . count($this->downgradePackages));

            foreach ($this->downgradePackages as $downgrade) {
                $this->composerBridge->getIo()->error(PHP_EOL);
                $this->composerBridge->getIo()->error("Why: " . $downgrade->getName());
                $this->runDependsCommand($downgrade);
            }
            throw new RuleError("Downgrades detected.");
        }

    }

    private function isADowngrade(UpdateOperation $operation): bool
    {
        $initialPackage = $operation->getInitialPackage();
        $targetPackage = $operation->getTargetPackage();

        return ! VersionParser::isUpgrade($initialPackage->getVersion(), $targetPackage->getVersion());
    }
    
    private function runDependsCommand(PackageInterface $package): void
    {
        $input = new ArrayInput(['package' => $package->getName()]);

        $commandOutput = $this->composerBridge->runDepends($input);
        
        $this->composerBridge->getIo()->error($commandOutput);
    }
}
