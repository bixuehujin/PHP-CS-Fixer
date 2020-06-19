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

namespace PhpCsFixer\Tests\Fixer\TypeHint;

use PhpCsFixer\Tests\Test\AbstractFixerTestCase;

/**
 * @internal
 * @coversNothing
 */
final class MissingTypehintToMixedFixerTest extends AbstractFixerTestCase
{
    /**
     * @param string $expected
     * @param null|string $input
     *
     * @dataProvider provideFixCases
     */
    public function testFix($expected, $input = null)
    {
        $this->doTest($expected, $input);
    }

    public function provideFixCases()
    {
        return [
            [
                <<<'CODE'
<?php
class Demo {
    /**
     * @var mixed
     */
    public $a;
    /**
     * @var string property desc
     */
    public $b;
    /**
     * @var mixed
     */
    static public $c;
}
CODE,
                <<<'CODE'
<?php
class Demo {
    public $a;
    /**
     * @var string property desc
     */
    public $b;
    static public $c;
}
CODE,
            ],
            [
                <<<'CODE'
<?php
class Demo {
    /**
     * @param mixed $a
     */
    public function __construct($a)
    {
    }
    /**
     * @param mixed $a
     * @param int $b
     * @return float
     */
    static public function foo($a, int $b): float
    {
        return 1.1;
    }

    /**
     * @param mixed $a
     * @param int $b
     * @return mixed
     */
    public function bar($a, int $b)
    {
    }

    public function bar2(int $a, int $b): void
    {
    }

    /**
     * @param mixed $a
     * @param int|null $b
     * @return int|null
     */
    public function nullable($a, ?int $b): ?int
    {
        return 1;
    }

    /**
     * @param int ...$args
     * @return mixed
     */
    public function variadics1(int ...$args)
    {
    }

    /**
     * @param mixed ...$args
     * @return mixed
     */
    public function variadics2(...$args)
    {
    }
}
CODE,
                <<<'CODE'
<?php
class Demo {
    public function __construct($a)
    {
    }
    static public function foo($a, int $b): float
    {
        return 1.1;
    }

    public function bar($a, int $b)
    {
    }

    public function bar2(int $a, int $b): void
    {
    }

    public function nullable($a, ?int $b): ?int
    {
        return 1;
    }

    public function variadics1(int ...$args)
    {
    }

    public function variadics2(...$args)
    {
    }
}
CODE,
            ],
        ];
    }
}
