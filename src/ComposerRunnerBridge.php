<?php

declare(strict_types=1);

namespace Nibbletech\Composer\Plugins\CustomRules;

use Composer\Command\DependsCommand;
use Composer\Composer;
use Composer\IO\IOInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class ComposerRunnerBridge
{
    /**
     * @var Composer
     */
    private $composer;
    /**
     * @var IOInterface
     */
    private $io;

    public function __construct(
        Composer $composer,
        IOInterface $io
    ) {
        $this->composer = $composer;
        $this->io = $io;
    }

    public function getComposer(): Composer
    {
        return $this->composer;
    }

    public function getIo(): IOInterface
    {
        return $this->io;
    }

    public function runDepends(
        ArrayInput $input
    ): string {
        $command = new DependsCommand();
        $command->setComposer($this->composer);

        $output = new BufferedOutput();

        $command->run($input, $output);

        return $output->fetch();

    }
}
