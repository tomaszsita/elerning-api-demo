<?php

namespace App\ValueObject;

use InvalidArgumentException;

class CourseTitle
{
    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function fromString(string $title): self
    {
        if (empty(trim($title))) {
            throw new InvalidArgumentException('Course title cannot be empty');
        }

        if (strlen($title) > 255) {
            throw new InvalidArgumentException('Course title too long');
        }

        return new self(trim($title));
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equals(CourseTitle $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
