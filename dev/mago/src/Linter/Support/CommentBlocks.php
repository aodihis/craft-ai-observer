<?php

declare(strict_types=1);

namespace CraftAiObserver\Dev\Mago\Linter\Support;

use Mago\Sdk\Syntax\NodeKind;
use Mago\Sdk\Syntax\SourceFile;

/**
 * Splits a file's comments into blocks of consecutive comment lines.
 */
final readonly class CommentBlocks
{
    private function __construct()
    {
    }

    /**
     * @return list<CommentBlock>
     */
    public static function fromFile(SourceFile $file): array
    {
        $lines = new SourceLines($file->contents);
        $blocks = [];
        $current = [];
        $previousEndLine = null;

        foreach ($file->getTrivia() as $comment) {
            $isGap = $previousEndLine !== null && $lines->startLine($comment->span) > ( $previousEndLine + 1 );

            if ($isGap && $current !== []) {
                $blocks[] = new CommentBlock($file, $current);
                $current = [];
            }

            $current[] = $comment;
            $previousEndLine = $lines->endLine($comment->span);
        }

        if ($current !== []) {
            $blocks[] = new CommentBlock($file, $current);
        }

        return $blocks;
    }

    /**
     * @param list<NodeKind> $kinds
     *
     * @return array<int, true> first line of every declaration of those kinds
     */
    public static function declarationLines(SourceFile $file, array $kinds): array
    {
        $lines = new SourceLines($file->contents);
        $declared = [];

        foreach ($kinds as $kind) {
            foreach ($file->getNodes($kind) as $node) {
                $declared[$lines->startLine($node->span)] = true;
            }
        }

        return $declared;
    }
}
