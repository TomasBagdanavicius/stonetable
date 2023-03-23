<?php

/**
 * A simple source file.
 */

declare(strict_types=1);

class Rectangle {
    function __construct(
        public readonly int|float $width,
        public readonly int|float $height
    ) {
    }
    public function calcArea(): int|float
    {
        return $this->width * $this->height;
    }
}
