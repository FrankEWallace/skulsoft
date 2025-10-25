<?php

namespace App\Support\CommonMarkExtensions;

use League\CommonMark\Parser\Inline\InlineParserInterface;
use League\CommonMark\Parser\Inline\InlineParserMatch;
use League\CommonMark\Parser\InlineParserContext;

class MathExpressionParser implements InlineParserInterface
{
    public function getMatchDefinition(): InlineParserMatch
    {
        // Match both inline math `$...$` and block math `$$...$$` expressions
        return InlineParserMatch::regex('\${1,2}[^$]+\${1,2}');
    }

    public function parse(InlineParserContext $inlineContext): bool
    {
        // Get the matched text (e.g., `$E=mc^2$` or `$$\sum_{i=1}^n i$$`)
        $match = $inlineContext->getFullMatch();
        if ($match === null) {
            return false;
        }

        // Check if it's a block formula (starts and ends with $$)
        $isBlock = str_starts_with($match, '$$') && str_ends_with($match, '$$');

        // Remove the enclosing `$` or `$$` delimiters
        $mathContent = trim($match, '$');

        // Consume the matched text so it's not parsed again
        $inlineContext->getCursor()->advanceBy(strlen($match));

        // Add a new MathExpression node with block information
        $inlineContext->getContainer()->appendChild(new MathExpression($mathContent, $isBlock));

        return true;
    }
}
