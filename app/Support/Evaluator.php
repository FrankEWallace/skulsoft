<?php

namespace App\Support;

trait Evaluator
{
    public function evaluate($expression)
    {
        $evaluator = new MathExpression;
        try {
            return $evaluator->execute($expression);
        } catch (\Exception $e) {
            return 'invalid';
        }
    }
}
