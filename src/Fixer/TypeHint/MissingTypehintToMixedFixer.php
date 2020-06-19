<?php

/*
 * This file is part of PHP CS Fixer.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpCsFixer\Fixer\TypeHint;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Analyzer\Analysis\ArgumentAnalysis;
use PhpCsFixer\Tokenizer\Analyzer\ArgumentsAnalyzer;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\Tokenizer\TokensAnalyzer;

class MissingTypehintToMixedFixer extends AbstractFixer
{
    protected $insertedTokens = 0;

    public function getDefinition()
    {
        return new FixerDefinition(
            'Adding mixed typehint for arguments and properties',
            [
                new CodeSample(
                    <<<'CODE'
class Demo {
    public $a;
    /**
     * @var string property desc
     */
    public $b;
}
CODE
                ),
            ]
        );
    }

    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isAnyTokenKindsFound([T_CLASS, T_FUNCTION, T_TRAIT]);
    }

    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        $analyzer = new TokensAnalyzer($tokens);
        $elements = $analyzer->getClassyElements();

        foreach ($elements as $index => $element) {
            if ('property' === $element['type']) {
                $this->fixProperty($index, $tokens);
            } elseif ('method' === $element['type']) {
                $this->fixMethod($index, $tokens);
            }
        }
    }

    protected function fixMethod($index, Tokens $tokens)
    {
        if ($this->hasDocBlock($tokens, $index)) {
            return;
        }

        $initIndex = $index;
        while (true) {
            $token = $tokens[$initIndex];
            if ($token->equals('(')) {
                $startPos = $initIndex;
            } elseif ($token->equals(')')) {
                $endPos = $initIndex;

                break;
            }
            ++$initIndex;
        }

        $analyzer = new ArgumentsAnalyzer();
        $arguments = $analyzer->getArguments($tokens, $startPos, $endPos);

        $commentList = [];

        foreach ($arguments as $start => $end) {
            /** @var ArgumentAnalysis $argument */
            $argument = $analyzer->getArgumentInfo($tokens, $start, $end);

            $typeInfo = $argument->getTypeAnalysis();
            if ($typeInfo) {
                $type = $typeInfo->getName();
            } else {
                $type = 'mixed';
            }

            $commentList[] = [
                'tag' => 'param',
                'name' => $argument->getName(),
                'type' => $type,
            ];
        }

        if (!$this->hasReturnTypeHint($tokens, $endPos)) {
            $commentList[] = [
                'tag' => 'return',
                'type' => 'mixed',
            ];
        } else {
            $nextIndex = $tokens->getNextMeaningfulToken($endPos);
            $nextIndex = $tokens->getNextMeaningfulToken($nextIndex);
            $commentList[] = [
                'tag' => 'return',
                'type' => $tokens[$nextIndex]->getContent(),
            ];
        }

        $comment = $this->buildFunctionDocBlock($commentList);

        $tokens->insertAt($index + $this->insertedTokens - 2, [
            new Token([T_DOC_COMMENT, $comment]),
            new Token([T_WHITESPACE, "\n    "]),
        ]);

        $this->insertedTokens += 2;
    }

    protected function buildFunctionDocBlock(array $list)
    {
        $comment = "/**\n";
        foreach ($list as $item) {
            if ('param' === $item['tag']) {
                $comment .= "     * @{$item['tag']} {$item['type']} {$item['name']}\n";
            } else {
                $comment .= "     * @{$item['tag']} {$item['type']}\n";
            }
        }

        return $comment.'     */';
    }

    protected function fixProperty($index, Tokens $tokens)
    {
        if ($this->hasDocBlock($tokens, $index)) {
            return;
        }

        $doc = "/**\n     * @var mixed\n     */";

        // TODO index - 2 should be more smart
        $tokens->insertAt($index + $this->insertedTokens - 2, [
            new Token([T_DOC_COMMENT, $doc]),
            new Token([T_WHITESPACE, "\n    "]),
        ]);
        $this->insertedTokens += 2;
    }

    /**
     * @param int $index
     *
     * @return bool
     */
    private function hasDocBlock(Tokens $tokens, $index)
    {
        $docBlockIndex = $this->getDocBlockIndex($tokens, $index);

        return $tokens[$docBlockIndex]->isGivenKind(T_DOC_COMMENT);
    }

    private function hasReturnTypeHint(Tokens $tokens, $index)
    {
//        $endFuncIndex = $tokens->getPrevTokenOfKind($index, [')']);
        $nextIndex = $tokens->getNextMeaningfulToken($index);

        return $tokens[$nextIndex]->isGivenKind(CT::T_TYPE_COLON);
    }

    /**
     * @param int $index
     *
     * @return int
     */
    private function getDocBlockIndex(Tokens $tokens, $index)
    {
        do {
            $index = $tokens->getPrevNonWhitespace($index);
        } while ($tokens[$index]->isGivenKind([
            T_PUBLIC,
            T_PROTECTED,
            T_PRIVATE,
            T_FINAL,
            T_ABSTRACT,
            T_COMMENT,
            T_VAR,
            T_STATIC,
            T_STRING,
            T_NS_SEPARATOR,
            CT::T_NULLABLE_TYPE,
        ]));

        return $index;
    }
}
