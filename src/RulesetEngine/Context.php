<?php

declare(strict_types=1);

namespace SensioLabs\Deptrac\RulesetEngine;

use SensioLabs\Deptrac\Dependency\DependencyInterface;

final class Context
{
    /**
     * @var Rule[]
     */
    private $rules;

    public function __construct()
    {
        $this->rules = [];
    }

    public function add(Rule $rule): void
    {
        $this->rules[] = $rule;
    }

    public function allowed(DependencyInterface $dependency, string $layerA, string $layerB): void
    {
        $this->rules[] = new Allowed($dependency, $layerA, $layerB);
    }

    public function skippedViolation(DependencyInterface $dependency, string $layerA, string $layerB): void
    {
        $this->rules[] = new SkippedViolation($dependency, $layerA, $layerB);
    }

    public function violation(DependencyInterface $dependency, string $layerA, string $layerB): void
    {
        $this->rules[] = new Violation($dependency, $layerA, $layerB);
    }

    public function uncovered(DependencyInterface $dependency, string $layer): void
    {
        $this->rules[] = new Uncovered($dependency, $layer);
    }

    /**
     * @return Rule[]
     */
    public function all(): array
    {
        return $this->rules;
    }

    /**
     * @return Violation[]
     */
    public function violations(): array
    {
        return array_filter($this->rules, static function (Rule $rule) {
            return $rule instanceof Violation;
        });
    }

    public function hasViolations(): bool
    {
        return count($this->violations()) > 0;
    }

    /**
     * @return SkippedViolation[]
     */
    public function skippedViolations(): array
    {
        return array_filter($this->rules, static function (Rule $rule) {
            return $rule instanceof SkippedViolation;
        });
    }
}
