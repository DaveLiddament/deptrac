<?php

declare(strict_types=1);

namespace SensioLabs\Deptrac\OutputFormatter;

use SensioLabs\Deptrac\AstRunner\AstMap\AstInherit;
use SensioLabs\Deptrac\Dependency\InheritDependency;
use SensioLabs\Deptrac\RulesetEngine\Context;
use SensioLabs\Deptrac\RulesetEngine\Rule;
use SensioLabs\Deptrac\RulesetEngine\SkippedViolation;
use SensioLabs\Deptrac\RulesetEngine\Violation;
use Symfony\Component\Console\Output\OutputInterface;

final class ConsoleOutputFormatter implements OutputFormatterInterface
{
    public function getName(): string
    {
        return 'console';
    }

    public function configureOptions(): array
    {
        return [];
    }

    public function enabledByDefault(): bool
    {
        return true;
    }

    public function finish(
        Context $context,
        OutputInterface $output,
        OutputFormatterInput $outputFormatterInput
    ): void {
        foreach ($context->all() as $rule) {
            if (!$rule instanceof Violation && !$rule instanceof SkippedViolation) {
                continue;
            }

            if ($rule->getDependency() instanceof InheritDependency) {
                $this->handleInheritDependency($rule, $output);
                continue;
            }

            $this->handleDependency($rule, $output);
        }

        $violationCount = \count($context->violations());
        $skippedViolationCount = \count($context->skippedViolations());

        $output->writeln(
            sprintf(
                'Found <error>%s Violations</error>'.($skippedViolationCount ? ' and %s Violations skipped' : ''),
                $violationCount,
                $skippedViolationCount
            )
        );
    }

    /**
     * @param Violation|SkippedViolation $rule
     */
    private function handleInheritDependency(Rule $rule, OutputInterface $output): void
    {
        /** @var InheritDependency $dependency */
        $dependency = $rule->getDependency();

        $output->writeln(
            sprintf(
                "%s<info>%s</info> must not depend on <info>%s</info> (%s on %s) \n%s",
                $rule instanceof SkippedViolation ? '[SKIPPED] ' : '',
                $dependency->getClassA(),
                $dependency->getClassB(),
                $rule->getLayerA(),
                $rule->getLayerB(),
                $this->formatPath($dependency->getPath(), $dependency)
            )
        );
    }

    /**
     * @param Violation|SkippedViolation $rule
     */
    private function handleDependency(Rule $rule, OutputInterface $output): void
    {
        $dependency = $rule->getDependency();

        $output->writeln(
            sprintf(
                '%s<info>%s</info>::%s must not depend on <info>%s</info> (%s on %s)',
                $rule instanceof SkippedViolation ? '[SKIPPED] ' : '',
                $dependency->getClassA(),
                $dependency->getClassALine(),
                $dependency->getClassB(),
                $rule->getLayerA(),
                $rule->getLayerB()
            )
        );
    }

    private function formatPath(AstInherit $astInherit, InheritDependency $dependency): string
    {
        $buffer = [];
        foreach ($astInherit->getPath() as $p) {
            array_unshift($buffer, sprintf("\t%s::%d", $p->getClassName(), $p->getLine()));
        }

        $buffer[] = sprintf("\t%s::%d", $astInherit->getClassName(), $astInherit->getLine());
        $buffer[] = sprintf(
            "\t%s::%d",
            $dependency->getOriginalDependency()->getClassB(),
            $dependency->getOriginalDependency()->getClassALine()
        );

        return implode(" -> \n", $buffer);
    }
}
