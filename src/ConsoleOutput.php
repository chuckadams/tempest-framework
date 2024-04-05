<?php

declare(strict_types=1);

namespace Tempest\Console;

interface ConsoleOutput
{
    public function delimiter(string $delimiter): ConsoleOutput;

    public function write(string $line, ConsoleOutputType $type = ConsoleOutputType::DEFAULT): ConsoleOutput;

    public function writeln(string $line = '', ConsoleOutputType $type = ConsoleOutputType::DEFAULT): ConsoleOutput;

    public function info(string $line): ConsoleOutput;

    public function error(string $line): ConsoleOutput;

    public function success(string $line): ConsoleOutput;

    public function when(mixed $expression, callable $callback): ConsoleOutput;
}
