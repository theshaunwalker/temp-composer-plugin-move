<?php

declare(strict_types=1);

namespace Nibbletech\Composer\Plugins\CustomRules\Rules\ExplicitPhp;

use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Package\BasePackage;
use Composer\Plugin\PrePoolCreateEvent;
use Composer\Repository\RootPackageRepository;
use Nibbletech\Composer\Plugins\CustomRules\ComposerRunnerBridge;
use Nibbletech\Composer\Plugins\CustomRules\Rules\Rule;

/**
 * Mandate packages explicitly define their PHP requirement
 */
class ExplicitPhp implements Rule, EventSubscriberInterface
{
    /**
     * @var ComposerRunnerBridge
     */
    private $composerBridge;
    /**
     * @var ExplicitPhpConfig
     */
    private $config;

    public function __construct(
        ComposerRunnerBridge $composerBridge,
        ExplicitPhpConfig $config = null
    ) {
        $this->composerBridge = $composerBridge;
        $this->config         = $config ?? new ExplicitPhpConfig();
    }

    public static function getSubscribedEvents()
    {
        return [
            'pre-pool-create' => 'prePoolCreate'
        ];
    }

    public function prePoolCreate(PrePoolCreateEvent $event): void
    {
        $this->errorOnMissingPhpRequire($event);
    }

    private function errorOnMissingPhpRequire(PrePoolCreateEvent $event): void
    {
        $packages = $event->getPackages();

        $goodPackages = [];

        $missingPhpPackages = [];
        foreach ($packages as $package) {
            /**
             * Are we ignoring this package?
             */
            if (
                $this->config->isPackageToCheck($package->getName()) === false
                || $this->config->isIgnoredPackage($package->getName())
                /**
                 * Ignore root packages.
                 */
                || $package->getRepository() instanceof RootPackageRepository
                /**
                 * Ignore platform packages. We only care about userland.
                 */
                || $package->isPlatform()
            ) {
                $goodPackages[] = $package;
                continue;
            }

            if ($this->hasPhpInRequires($package) === false) {
                $missingPhpPackages[] = $package;
            } else {
                $goodPackages[] = $package;
            }
        }


        if (!empty($missingPhpPackages)) {

            $this->composerBridge->getIo()->debug("Found packages missing PHP in their requires:");

            foreach ($missingPhpPackages as $missingPhpPackage) {
                $this->composerBridge->getIo()->debug($missingPhpPackage->getPrettyName());
            }
        }

        $event->setPackages(
            $goodPackages
        );
    }

    /**
     * Simplistic exists check.
     * Not doing any detailed version inspection.
     * Blindly trusting composer for now to exclude completely invalid php values.
     * (Don't know how much it actually handles bad php values)
     */
    private function hasPhpInRequires(BasePackage $package): bool
    {
        $outputDebug = $this->composerBridge->getIo()->isVerbose();
        
        if ($outputDebug) $this->composerBridge->getIo()->write($package->getPrettyString());

        foreach ($package->getRequires() as $require) {
            if ($require->getTarget() == "php") {
                if ($outputDebug) $this->composerBridge->getIo()->write("has php");
                return true;
            }
        }

        if ($outputDebug) $this->composerBridge->getIo()->error("missing php");
        return false;
    }
}
