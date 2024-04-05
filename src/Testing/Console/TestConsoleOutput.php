<?php

declare(strict_types=1);

namespace Tempest\Console\Testing\Console;

use Tempest\Console\ConsoleOutput;
use Tempest\Console\ConsoleOutputType;
use Tempest\Console\ConsoleStyle;

final class TestConsoleOutput implements ConsoleOutput
{
    private array $lines = [];
    private array $errorLines = [];
    private array $infoLines = [];
    private array $successLines = [];
    public string $delimiter = PHP_EOL;

    public function delimiter(string $delimiter): ConsoleOutput
    {
        $clone = clone $this;

        $this->delimiter = $delimiter;

        return $clone;
    }

    public function write(string $line, ConsoleOutputType $type = ConsoleOutputType::DEFAULT): ConsoleOutput
    {
        $this->lines[] = $line;
    }

    public function writeln(string $line = '', ConsoleOutputType $type = ConsoleOutputType::DEFAULT): ConsoleOutput
    {
        $this->lines[] = $line;
    }

    public function info(string $line): ConsoleOutput
    {
        $this->writeln($line);
        $this->infoLines[] = $line;
    }

    public function error(string $line): ConsoleOutput
    {
        $this->writeln($line);
        $this->errorLines[] = $line;
    }

    public function success(string $line): ConsoleOutput
    {
        $this->writeln($line);
        $this->successLines[] = $line;
    }

    public function getLinesWithFormatting(): array
    {
        return $this->lines;
    }

    public function getLinesWithoutFormatting(): array
    {
        $pattern = array_map(
            fn (ConsoleStyle $consoleStyle) => ConsoleStyle::ESC->value . $consoleStyle->value,
            ConsoleStyle::cases(),
        );

        return array_map(
            fn (string $line) => str_replace($pattern, '', $line),
            $this->getLinesWithFormatting(),
        );
    }

    public function getTextWithFormatting(): string
    {
        return implode($this->delimiter, $this->getLinesWithFormatting());
    }

    public function getTextWithoutFormatting(): string
    {
        return implode($this->delimiter, $this->getLinesWithoutFormatting());
    }

    public function getInfoLines(): array
    {
        return $this->infoLines;
    }

    public function getErrorLines(): array
    {
        return $this->errorLines;
    }

    public function getSuccessLines(): array
    {
        return $this->successLines;
    }

    public function when(mixed $expression, callable $callback): ConsoleOutput
    {
        if ($expression) {
            return $callback($this);
        }
    }
}
