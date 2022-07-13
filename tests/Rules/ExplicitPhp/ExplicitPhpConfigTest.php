<?php

declare(strict_types=1);

namespace Nibbletech\Composer\Plugins\CustomRules\Rules\ExplicitPhp;

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Nibbletech\Composer\Plugins\CustomRules\Rules\ExplicitPhp\ExplicitPhpConfig
 */
class ExplicitPhpConfigTest extends TestCase
{
    /**
     * @covers ::addIgnores
     * @covers ::isIgnoredPackage
     */
    public function test_it_ignores_packages(): void
    {
        $config = new ExplicitPhpConfig();
        $config->addIgnores([
            'foo/bar',
        ]);

        $this->assertTrue(
            $config->isIgnoredPackage('foo/bar')
        );
        $this->assertFalse(
            $config->isIgnoredPackage('foo/whatever')
        );
    }

    /**
     * @covers ::addIgnores
     * @covers ::isIgnoredPackage
     */
    public function test_it_ignores_wildcard_package(): void
    {
        $config = new ExplicitPhpConfig();
        $config->addIgnores([
            'bar*'
        ]);

        $this->assertFalse(
            $config->isIgnoredPackage('foo/bar')
        );
        $this->assertTrue(
            $config->isIgnoredPackage('bar/foo')
        );
        $this->assertTrue(
            $config->isIgnoredPackage('barry/foo')
        );
    }

    /**
     * @covers ::isPackageToCheck
     */
    public function test_check_package_is_true_when_empty(): void
    {
        $config = new ExplicitPhpConfig();

        $this->assertTrue(
            $config->isPackageToCheck('foo/bar')
        );
        $this->assertTrue(
            $config->isPackageToCheck('foo/whatever')
        );
    }

    /**
     * @covers ::addPackagesToCheck
     * @covers ::isPackageToCheck
     */
    public function test_check_package_with_explicit_strings(): void
    {
        $config = new ExplicitPhpConfig();
        $config->addPackagesToCheck([
            'foo/bar',
        ]);

        $this->assertTrue(
            $config->isPackageToCheck('foo/bar')
        );
        $this->assertFalse(
            $config->isPackageToCheck('foo/whatever')
        );
    }

    /**
     * @covers ::addPackagesToCheck
     * @covers ::isPackageToCheck
     */
    public function test_check_package_with_wildcard(): void
    {
        $config = new ExplicitPhpConfig();
        $config->addPackagesToCheck([
            'bar*'
        ]);

        $this->assertFalse(
            $config->isPackageToCheck('foo/bar')
        );
        $this->assertTrue(
            $config->isPackageToCheck('bar/foo')
        );
        $this->assertTrue(
            $config->isPackageToCheck('barry/foo')
        );
    }
}
