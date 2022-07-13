<?php

declare(strict_types=1);

namespace Rules\BlockDowngrades;

use Composer\Composer;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\Installer\PackageEvent;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Nibbletech\Composer\Plugins\CustomRules\ComposerRunnerBridge;
use Nibbletech\Composer\Plugins\CustomRules\Rules\BlockDowngrades\BlockDowngrades;
use Nibbletech\Composer\Plugins\CustomRules\Rules\RuleError;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Console\Input\ArrayInput;

/**
 * @coversDefaultClass \Nibbletech\Composer\Plugins\CustomRules\Rules\BlockDowngrades\BlockDowngrades
 */
class BlockDowngradesTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy&PackageEvent
     */
    private $preUpdateEvent;
    /**
     * @var ObjectProphecy&PackageEvent
     */
    private $postUpdateEvent;
    /**
     * @var ObjectProphecy&ComposerRunnerBridge
     */
    private $composerRunnerBridge;
    /**
     * @var ObjectProphecy&Composer
     */
    private $composer;
    /**
     * @var ObjectProphecy&IOInterface
     */
    private $composerIo;

    protected function setUp(): void
    {
        $packageEvent = $this->prophesize(PackageEvent::class);
        $packageEvent->getOperation()->willReturn(
            $this->prophesize(UpdateOperation::class)->reveal()
        );
        $this->preUpdateEvent  = clone $packageEvent;
        $this->postUpdateEvent = clone $packageEvent;

        $this->composer   = $this->prophesize(Composer::class);
        $this->composerIo = $this->prophesize(IOInterface::class);
        
        $this->composerRunnerBridge = $this->prophesize(ComposerRunnerBridge::class);
        $this->composerRunnerBridge->getComposer()->willReturn($this->composer);
        $this->composerRunnerBridge->getIo()->willReturn($this->composerIo);
    }

    private function getRule(): BlockDowngrades
    {
        return new BlockDowngrades(
            $this->composerRunnerBridge->reveal()
        );
    }

    /**
     * @param ObjectProphecy&UpdateOperation $updateOperation
     * @param bool                           $isDowngrade
     *
     * @return void
     */
    private function mockIsDowngrade(
        ObjectProphecy $updateOperation,
        ObjectProphecy $targetPackage,
        bool $isDowngrade
    ): void {
        $initialPackage = $this->prophesize(PackageInterface::class);

        $initialPackage->getVersion()->willReturn('2.0.0');

        if ($isDowngrade) {
            $targetPackage->getVersion()->willReturn('1.0.0');
        } else {
            $targetPackage->getVersion()->willReturn('3.0.0');
        }

        $updateOperation->getInitialPackage()->willReturn($initialPackage);
        $updateOperation->getTargetPackage()->willReturn($targetPackage);
    }

    /**
     * @group new
     * @covers ::prePoolCreate
     */
    public function test_it_throws_error_on_downgrades(): void
    {
        $downgradePackage = $this->prophesize(PackageInterface::class);
        $downgradePackage->getName()->willReturn('foo/bar');

        $operation = $this->prophesize(UpdateOperation::class);
        $this->preUpdateEvent->getOperation()->willReturn($operation);

        $this->mockIsDowngrade($operation, $downgradePackage, true);

        $this->composerRunnerBridge->runDepends(
            new ArrayInput(['package' => 'foo/bar'])
        )->willReturn('depends output');

        $this->expectExceptionObject(new RuleError("Downgrades detected."));

        $this->composerIo->error("Total downgraded packages: 1")
            ->shouldBeCalled();
        $this->composerIo->error(PHP_EOL)
            ->shouldBeCalled();
        $this->composerIo->error("Why: foo/bar")
            ->shouldBeCalled();
        $this->composerIo->error("depends output")
            ->shouldBeCalled();
        $this->composerIo->error(PHP_EOL)
            ->shouldBeCalled();
        $rule = $this->getRule();

        $rule->preUpdate($this->preUpdateEvent->reveal());
        $rule->postUpdate($this->postUpdateEvent->reveal());
    }

}
