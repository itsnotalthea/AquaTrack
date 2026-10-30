<?php

namespace App\Services;

use RuntimeException;

class InsufficientStockException extends RuntimeException
{
    public function __construct(
        public string $itemName,
        public int $requested,
        public int $available,
    ) {
        parent::__construct(
            $available > 0
                ? "Only {$available} of {$itemName} available, but {$requested} were requested."
                : "{$itemName} is out of stock."
        );
    }
}
