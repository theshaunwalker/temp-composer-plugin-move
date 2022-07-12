<?php

declare(strict_types=1);

namespace Nibbletech\Composer\Plugins\CustomRules\Rules;

use Composer\Command\BaseDependencyCommand;
use Composer\Command\DependsCommand;
use Composer\Composer;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Package\Version\VersionParser;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\NullOutput;

class BlockDowngrades implements Rule, EventSubscriberInterface
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
     * @var PackageInterface[]
     */
    private $downgradePackages = [];

    public function __construct(
        Composer $composer,
        IOInterface $io
    ) {
        $this->composer = $composer;
        $this->io = $io;
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
        $this->io->writeError("pre update");


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
        $this->io->writeError("Post update");

        if (count($this->downgradePackages) > 0) {
            $this->io->writeError("Total downgrade packages: " . count($this->downgradePackages));

            foreach ($this->downgradePackages as $downgrade) {
                $this->io->writeError("Package: " . $downgrade->getName());
                $this->io->writeError("Why:");
                $this->runDependsCommand($downgrade);
                $this->io->writeError(PHP_EOL);
            }
            throw new \Exception("Downgrades detected.");
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

        $command = new DependsCommand();
        $command->setComposer($this->composer);

        $input = new ArrayInput(['package' => $package->getName()]);
        $output = new BufferedOutput();

        $command->run($input, $output);

        $commandOutput = $output->fetch();
        
        $this->io->writeRaw($commandOutput);
    }
}
