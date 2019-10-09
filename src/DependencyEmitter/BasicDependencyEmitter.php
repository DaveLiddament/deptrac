<?php

declare(strict_types=1);

namespace SensioLabs\Deptrac\DependencyEmitter;

use SensioLabs\Deptrac\AstRunner\AstMap;
use SensioLabs\Deptrac\AstRunner\AstMap\AstDependency;
use SensioLabs\Deptrac\Dependency\Result;
use SensioLabs\Deptrac\Dependency\Dependency;

class BasicDependencyEmitter implements DependencyEmitterInterface
{
    public function getName(): string
    {
        return 'BasicDependencyEmitter';
    }

    public function applyDependencies(AstMap $astMap, Result $dependencyResult): void
    {
        foreach ($astMap->getAstFileReferences() as $fileReference) {
            $uses = $fileReference->getDependencies();

            foreach ($fileReference->getAstClassReferences() as $astClassReference) {
                /** @var AstDependency[] $dependencies */
                $dependencies = array_merge($uses, $astClassReference->getDependencies());

                foreach ($dependencies as $emittedDependency) {
                    $dependencyResult->addDependency(
                        new Dependency(
                            $astClassReference->getFileReference()->getFilepath(),
                            $astClassReference->getClassName(),
                            $emittedDependency->getLine(),
                            $emittedDependency->getClass()
                        )
                    );
                }
            }
        }
    }
}
