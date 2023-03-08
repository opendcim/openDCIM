<?php

declare(strict_types=1);

namespace Rector\PHPOffice\ValueObject;

final class ConditionalSetValue
{
    public function __construct(
        private readonly string $oldMethod,
        private readonly string $newGetMethod,
        private readonly string $newSetMethod,
        private readonly int $argPosition,
        private readonly bool $hasRow
    ) {
    }

    public function getOldMethod(): string
    {
        return $this->oldMethod;
    }

    public function getArgPosition(): int
    {
        return $this->argPosition;
    }

    public function getNewGetMethod(): string
    {
        return $this->newGetMethod;
    }

    public function getNewSetMethod(): string
    {
        return $this->newSetMethod;
    }

    public function hasRow(): bool
    {
        return $this->hasRow;
    }
}
