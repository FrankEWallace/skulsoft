<?php

namespace App\Support\CommonMarkExtensions;

use League\CommonMark\Node\Inline\AbstractInline;

class MathExpression extends AbstractInline
{
    private string $expression;

    private bool $isBlock;

    public function __construct(string $expression, bool $isBlock = false)
    {
        parent::__construct();
        $this->expression = $expression;
        $this->isBlock = $isBlock;
    }

    public function getExpression(): string
    {
        return $this->expression;
    }

    public function isBlock(): bool
    {
        return $this->isBlock;
    }
}
