<?php

namespace App\Support\CommonMarkExtensions;

use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\ExtensionInterface;

class MathExpressionExtension implements ExtensionInterface
{
    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment->addInlineParser(new MathExpressionParser);
        $environment->addRenderer(MathExpression::class, new MathExpressionRenderer);
    }
}
