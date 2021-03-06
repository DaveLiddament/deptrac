<?php

declare(strict_types=1);

namespace Tests\SensioLabs\Deptrac\Dependency;

use PHPUnit\Framework\TestCase;
use SensioLabs\Deptrac\AstRunner\AstMap;
use SensioLabs\Deptrac\AstRunner\AstMap\AstClassReference;
use SensioLabs\Deptrac\AstRunner\AstMap\AstInherit;
use SensioLabs\Deptrac\Dependency\InheritanceFlatter;
use SensioLabs\Deptrac\Dependency\Result;
use SensioLabs\Deptrac\Dependency\Dependency;
use SensioLabs\Deptrac\Dependency\InheritDependency;

class InheritanceFlatterTest extends TestCase
{
    private function getAstReference($className)
    {
        $astClass = $this->prophesize(AstClassReference::class);
        $astClass->getClassName()->willReturn($className);

        return $astClass->reveal();
    }

    private function getDependency($className)
    {
        $dep = $this->prophesize(Dependency::class);
        $dep->getClassA()->willReturn($className);
        $dep->getClassB()->willReturn($className.'_b');

        return $dep->reveal();
    }

    public function testFlattenDependencies(): void
    {
        $astMap = $this->prophesize(AstMap::class);

        $astMap->getAstClassReferences()->willReturn([
            $this->getAstReference('classA'),
            $this->getAstReference('classB'),
            $this->getAstReference('classBaum'),
            $this->getAstReference('classWeihnachtsbaum'),
            $this->getAstReference('classGeschmückterWeihnachtsbaum'),
        ]);

        $dependencyResult = new Result();
        $dependencyResult->addDependency($classADep = $this->getDependency('classA'));
        $dependencyResult->addDependency($classBDep = $this->getDependency('classB'));
        $dependencyResult->addDependency($classBaumDep = $this->getDependency('classBaum'));
        $dependencyResult->addDependency($classWeihnachtsbaumsDep = $this->getDependency('classWeihnachtsbaumsA'));

        $astMap->getClassInherits('classA')->willReturn([]);
        $astMap->getClassInherits('classB')->willReturn([]);
        $astMap->getClassInherits('classBaum')->willReturn([]);
        $astMap->getClassInherits('classWeihnachtsbaum')->willReturn([
            AstInherit::newTraitUse('classBaum', 3),
        ]);
        $astMap->getClassInherits('classGeschmückterWeihnachtsbaum')->willReturn([
            AstMap\AstInherit::newExtends('classBaum', 3)->withPath([
                AstInherit::newTraitUse('classWeihnachtsbaum', 3),
            ]),
        ]);

        (new InheritanceFlatter())->flattenDependencies($astMap->reveal(), $dependencyResult);

        $inheritDeps = array_filter(
            $dependencyResult->getDependenciesAndInheritDependencies(),
            static function ($v) {
                return $v instanceof InheritDependency;
            }
        );

        static::assertCount(2, $inheritDeps);
    }
}
