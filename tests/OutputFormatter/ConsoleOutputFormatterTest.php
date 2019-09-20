<?php

declare(strict_types=1);

namespace Tests\SensioLabs\Deptrac\OutputFormatter;

use PHPUnit\Framework\TestCase;
use SensioLabs\Deptrac\AstRunner\AstMap\AstInherit;
use SensioLabs\Deptrac\Dependency\Dependency;
use SensioLabs\Deptrac\Dependency\InheritDependency;
use SensioLabs\Deptrac\OutputFormatter\ConsoleOutputFormatter;
use SensioLabs\Deptrac\OutputFormatter\OutputFormatterInput;
use SensioLabs\Deptrac\RulesetEngine\Context;
use SensioLabs\Deptrac\RulesetEngine\SkippedViolation;
use SensioLabs\Deptrac\RulesetEngine\Violation;
use Symfony\Component\Console\Output\BufferedOutput;

class ConsoleOutputFormatterTest extends TestCase
{
    public function testGetName(): void
    {
        static::assertEquals('console', (new ConsoleOutputFormatter())->getName());
    }

    public function basicDataProvider(): iterable
    {
        yield [
            [
                new Violation(
                    new InheritDependency(
                        'ClassA',
                        'ClassB',
                        new Dependency('OriginalA', 12, 'OriginalB'),
                        AstInherit::newExtends('ClassInheritA', 3)->withPath([
                            AstInherit::newExtends('ClassInheritB', 4),
                            AstInherit::newExtends('ClassInheritC', 5),
                            AstInherit::newExtends('ClassInheritD', 6),
                        ])
                    ),
                    'LayerA',
                    'LayerB'
                ),
            ],
            '
                ClassA must not depend on ClassB (LayerA on LayerB)
                ClassInheritD::6 ->
                ClassInheritC::5 ->
                ClassInheritB::4 ->
                ClassInheritA::3 ->
                OriginalB::12

                Found 1 Violations
            ',
        ];

        yield [
            [
                new Violation(
                    new Dependency('OriginalA', 12, 'OriginalB'),
                    'LayerA',
                    'LayerB'
                ),
            ],
            '
                OriginalA::12 must not depend on OriginalB (LayerA on LayerB)

                Found 1 Violations
            ',
        ];

        yield [
            [],
            '

                Found 0 Violations
            ',
        ];

        yield [
            [
                new SkippedViolation(
                    new Dependency('OriginalA', 12, 'OriginalB'),
                    'LayerA',
                    'LayerB'
                ),
            ],
            '[SKIPPED] OriginalA::12 must not depend on OriginalB (LayerA on LayerB)
            Found 0 Violations and 1 Violations skipped
            ',
        ];
    }

    /**
     * @dataProvider basicDataProvider
     */
    public function testBasic(array $rules, string $expectedOutput): void
    {
        $output = new BufferedOutput();

        $context = new Context();
        foreach ($rules as $rule) {
            $context->add($rule);
        }

        $formatter = new ConsoleOutputFormatter();
        $formatter->finish(
            $context,
            $output,
            new OutputFormatterInput([])
        );

        $o = $output->fetch();
        static::assertEquals(
            $this->normalize($expectedOutput),
            $this->normalize($o)
        );
    }

    public function testGetOptions(): void
    {
        static::assertCount(0, (new ConsoleOutputFormatter())->configureOptions());
    }

    private function normalize($str)
    {
        return str_replace(["\r", "\t", "\n", ' '], '', $str);
    }
}
