<?php

namespace App\ValueObject;

use InvalidArgumentException;

class Email
{
    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function fromString(string $email): self
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email format');
        }

        if (strlen($email) > 255) {
            throw new InvalidArgumentException('Email too long');
        }

        return new self($email);
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equals(Email $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
