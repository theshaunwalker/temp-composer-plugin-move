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

        $missingPhpPackages = [];
        foreach ($packages as $package) {
            /**
             * Ignore root package.
             */
            if ($package->getRepository() instanceof RootPackageRepository) {
                continue;
            }
            /**
             * We only care about userland packages. Ignore all platform packages.
             */
            if ($package->isPlatform()) {
                continue;
            }

            $this->io->write("================");
            $this->io->write("Refiner : Package name " . $package->getName());
            $this->io->write("Refiner : Package string " . $package->getPrettyString());
            $this->io->write("Refiner : Package version " . $package->getVersion());
            $this->io->write("================");

            if ($this->hasPhpInRequires($package) === false) {
                $missingPhpPackages[] = $package;
            }
        }

        if (!empty($missingPhpPackages)) {
            $this->io->writeError("The following packages are missing PHP requires:");

            foreach ($missingPhpPackages as $missingPhpPackage) {
                $this->io->writeError($missingPhpPackage->getPrettyName());
            }

            die();
        }
    }

    /**
     * Simplistic exists check.
     * Not doing any detailed version inspection.
     * Blindly trusting composer for now to exclude completely invalid php values.
     * (Don't know how much it actually handles bad php values)
     */
    public function hasPhpInRequires(BasePackage $basePackage): bool
    {
        foreach ($basePackage->getRequires() as $require) {
            if ($require->getTarget() == "php") {
                return true;
            }
        }

        return false;
    }
}
