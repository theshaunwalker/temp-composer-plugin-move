<?php

declare(strict_types=1);

namespace Nibbletech\Composer\Plugins\CustomRules\Rules;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Package\BasePackage;
use Composer\Plugin\PrePoolCreateEvent;
use Composer\Repository\RootPackageRepository;

/**
 * Mandate packages explicitly define their PHP requirement
 */
class ExplicitPhp implements Rule, EventSubscriberInterface
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
     * @var array
     */
    private $config;

    public function __construct(
        Composer $composer,
        IOInterface $io,
        array $config = []
    ) {
        $this->composer = $composer;
        $this->io       = $io;
        $this->config   = $config;
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

    public function errorOnMissingPhpRequire(PrePoolCreateEvent $event): void
    {
        $packages = $event->getPackages();

        $goodPackages = [];

        $missingPhpPackages = [];
        foreach ($packages as $package) {
            /**
             * Are we ignoring this package?
             */
            if (
                /**
                 * Ignore root packages.
                 */
                $package->getRepository() instanceof RootPackageRepository
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

            $this->io->error("The following packages are missing PHP requires:");

            foreach ($missingPhpPackages as $missingPhpPackage) {
                $this->io->writeError($missingPhpPackage->getPrettyName());
            }

            die();
        }

        $event->setPackages($goodPackages);
    }

    /**
     * Simplistic exists check.
     * Not doing any detailed version inspection.
     * Blindly trusting composer for now to exclude completely invalid php values.
     * (Don't know how much it actually handles bad php values)
     */
    public function hasPhpInRequires(BasePackage $basePackage): bool
    {
        $outputDebug = $this->io->isVerbose();
        
        if ($outputDebug) $this->io->write($basePackage->getPrettyString());
        foreach ($basePackage->getRequires() as $require) {
            if ($require->getTarget() == "php") {
                if ($outputDebug) $this->io->write("has php");
                return true;
            }
        }

        if ($outputDebug) $this->io->error("missing php");
        return false;
    }
}
