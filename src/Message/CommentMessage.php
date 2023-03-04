<?php

namespace App\Message;

class CommentMessage
{
    public function __construct(
        public readonly int $id,
        public readonly string $reviewUrl,
        public readonly array $context = [],
    ) {
    }
}
