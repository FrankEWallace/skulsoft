<?php

namespace App\Support\CommonMarkExtensions;

use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;

class MathExpressionRenderer implements NodeRendererInterface
{
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): HtmlElement
    {
        if (! ($node instanceof MathExpression)) {
            throw new \InvalidArgumentException('Invalid node type: '.get_class($node));
        }

        $expression = htmlspecialchars($node->getExpression(), ENT_QUOTES, 'UTF-8');
        $attributes = [
            'class' => 'math',
            'data-katex' => $expression,
        ];

        return new HtmlElement('span', $attributes, $expression);
    }

    public function getHtmlTagName(Node $node): ?string
    {
        return 'span';
    }
}
