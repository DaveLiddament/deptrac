<?php

declare(strict_types=1);

namespace SensioLabs\Deptrac;

use SensioLabs\Deptrac\Configuration\Configuration;
use SensioLabs\Deptrac\Dependency\Result;
use SensioLabs\Deptrac\RulesetEngine\Context;

class RulesetEngine
{
    public function process(
        Result $dependencyResult,
        ClassNameLayerResolverInterface $classNameLayerResolver,
        Configuration $configuration
    ): Context {
        $context = new Context();

        $configurationRuleset = $configuration->getRuleset();
        $configurationSkippedViolation = $configuration->getSkipViolations();

        foreach ($dependencyResult->getDependenciesAndInheritDependencies() as $dependency) {
            $layerNames = $classNameLayerResolver->getLayersByClassName($dependency->getClassA());

            foreach ($layerNames as $layerName) {
                $allowedDependencies = $configurationRuleset->getAllowedDependencies($layerName);

                $layersNamesClassB = $classNameLayerResolver->getLayersByClassName($dependency->getClassB());

                if (0 === count($layersNamesClassB)) {
                    $context->uncovered($dependency, $layerName);
                    continue;
                }

                foreach ($layersNamesClassB as $layerNameOfDependency) {
                    if ($layerName === $layerNameOfDependency) {
                        continue;
                    }

                    if (in_array($layerNameOfDependency, $allowedDependencies, true)) {
                        $context->allowed($dependency, $layerName, $layerNameOfDependency);
                        continue;
                    }

                    if ($configurationSkippedViolation->isViolationSkipped($dependency->getClassA(), $dependency->getClassB())) {
                        $context->skippedViolation($dependency, $layerName, $layerNameOfDependency);
                        continue;
                    }

                    $context->violation($dependency, $layerName, $layerNameOfDependency);
                }
            }
        }

        return $context;
    }
}
