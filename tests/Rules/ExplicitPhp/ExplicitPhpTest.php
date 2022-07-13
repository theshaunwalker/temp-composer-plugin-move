<?php

declare(strict_types=1);

namespace Rules\ExplicitPhp;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Package\BasePackage;
use Composer\Plugin\PrePoolCreateEvent;
use Composer\Repository\ArrayRepository;
use Composer\Repository\RootPackageRepository;
use Nibbletech\Composer\Plugins\CustomRules\ComposerRunnerBridge;
use Nibbletech\Composer\Plugins\CustomRules\Rules\ExplicitPhp\ExplicitPhp;
use Nibbletech\Composer\Plugins\CustomRules\Rules\ExplicitPhp\ExplicitPhpConfig;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @coversDefaultClass \Nibbletech\Composer\Plugins\CustomRules\Rules\ExplicitPhp\ExplicitPhp
 */
class ExplicitPhpTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy&PrePoolCreateEvent
     */
    private $prePoolEvent;
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
        $this->prePoolEvent = $this->prophesize(PrePoolCreateEvent::class);

        $this->composer = $this->prophesize(Composer::class);
        $this->composerIo = $this->prophesize(IOInterface::class);
    }

    private function getRule(ExplicitPhpConfig $config = null): ExplicitPhp
    {
        return new ExplicitPhp(
            new ComposerRunnerBridge(
                $this->composer->reveal(),
                $this->composerIo->reveal()
            ),
            $config
        );
    }

    /**
     * @covers ::prePoolCreate
     */
    public function test_it_ignores_root_package(): void
    {
        $phpPackage = $this->prophesize(BasePackage::class);
        $phpPackage->getName()->willReturn('foo/bar');
        $phpPackage->isPlatform()->willReturn(false);
        $phpPackage->getRepository()->willReturn(
            $this->prophesize(RootPackageRepository::class)->reveal()
        );

        $originalPackages = [
            $phpPackage
        ];

        $this->prePoolEvent->getPackages()->willReturn($originalPackages);

        $this->prePoolEvent->setPackages($originalPackages)->shouldBeCalled();

        $rule = $this->getRule();
        
        $rule->prePoolCreate($this->prePoolEvent->reveal());
    }

    /**
     * @covers ::prePoolCreate
     */
    public function test_it_ignores_platform_packages(): void
    {
        $phpPackage = $this->prophesize(BasePackage::class);
        $phpPackage->getName()->willReturn('php');
        $phpPackage->isPlatform()->willReturn(true);
        $phpPackage->getRepository()->willReturn(
            $this->prophesize(ArrayRepository::class)->reveal()
        );

        $originalPackages = [
            $phpPackage
        ];

        $this->prePoolEvent->getPackages()->willReturn($originalPackages);

        $this->prePoolEvent->setPackages($originalPackages)->shouldBeCalled();


        $rule = $this->getRule();

        $rule->prePoolCreate($this->prePoolEvent->reveal());
    }

    /**
     * @covers ::prePoolCreate
     */
    public function test_ignores_packages_missing_php_version(): void
    {
        $badPackage = $this->prophesize(BasePackage::class);
        $badPackage->getName()->willReturn('foo/bar');
        $badPackage->isPlatform()->willReturn(false);
        $badPackage->getRequires()->willReturn([]);
        $badPackage->getPrettyName()->willReturn('pretty name');
        $badPackage->getRepository()->willReturn(
            $this->prophesize(ArrayRepository::class)->reveal()
        );

        $originalPackages = [
            $badPackage
        ];

        $this->prePoolEvent->getPackages()->willReturn($originalPackages);

        $rule = $this->getRule();

        $this->composerIo->isVerbose()->willReturn(false);
        $this->composerIo->debug('Found packages missing PHP in their requires:')->shouldBeCalled();
        $this->composerIo->debug('pretty name')->shouldBeCalled();

        $this->prePoolEvent->setPackages([])->shouldBeCalled();

        $rule->prePoolCreate($this->prePoolEvent->reveal());
    }
}
