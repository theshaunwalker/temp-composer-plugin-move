<?php

declare(strict_types=1);

namespace Nibbletech\Composer\Plugins\CustomRules\Rules\ExplicitPhp;

class ExplicitPhpConfig
{
    /**
     * What packages to ignore when applying this rule.
     * Allows basic single wildcarding like "foo*" to match "foo/bar" and "foodie/bars"
     *
     * @var string[]
     */
    private $ignoredPackages = [];
    /**
     * Which packages to check with this rule.
     * Default of empty array means "check everything"
     * Allows basic single wildcarding like "foo*" to match "foo/bar" and "foodie/bars"
     *
     * @var string[]
     */
    private $packagesToCheck = [];

    /**
     * @param string[] $ignores
     */
    public function addIgnores(array $ignores): self
    {
        foreach ($ignores as $ignore) {
            $this->ignoredPackages[] = $ignore;
        }

        $this->ignoredPackages = array_unique($this->ignoredPackages);

        return $this;
    }

    public function isIgnoredPackage(string $packageName): bool
    {
        return $this->isNeedleInWildcardArray($packageName, $this->ignoredPackages);
    }

    /**
     * @param string   $needle
     * @param string[] $haystack
     *
     * @return bool
     */
    public function isNeedleInWildcardArray(string $needle, array $haystack): bool
    {
        foreach ($haystack as $item) {
            if (strpos($item, '*') !== false) {
                if ($this->wildcardMatches($item, $needle)) {
                    return true;
                }
            } else {
                if ($needle === $item) {
                    return true;
                }
            }
        }

        return false;
    }

    private function wildcardMatches(string $wildcard, string $subject): bool
    {
        $wildcardString = explode('*', $wildcard)[0];

        return strpos($subject, $wildcardString) === 0;
    }

    /**
     * @param string[] $packages
     */
    public function addPackagesToCheck(array $packages): self
    {
        foreach ($packages as $package) {
            $this->packagesToCheck[] = $package;
        }

        $this->packagesToCheck = array_unique($this->packagesToCheck);

        return $this;
    }

    public function isPackageToCheck(string $packageName): bool
    {
        if (empty($this->packagesToCheck)) {
            return true;
        }

        return $this->isNeedleInWildcardArray($packageName, $this->packagesToCheck);
    }
}
