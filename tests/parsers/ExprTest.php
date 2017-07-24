<?php declare(strict_types=1);

namespace Yay;

/**
 * @group small
 */
class ExprTest extends \PHPUnit_Framework_TestCase {

    protected function parseExpr(string $source) /*: Result|null*/
    {
        $ts = TokenStream::fromSourceWithoutOpenTag($source);
        $ts->next();

        return expr()->parse($ts);
    }

    protected function assertDumpEquals(string $expected, Result $ast = null): void
    {
        $this->assertInstanceOf(Ast::class, $ast);

        $dump = function ($node) use (&$dump) {
            if (is_array($node)) {
                return '(' . implode(', ', array_map($dump, $node)) . ')';
            }

            if ($node instanceof Token) {
                return $node->dump();
            }

            return $node;
        };

        $actual = $dump($ast->unwrap());

        $this->assertEquals($expected, $actual);
    }

    function providerForTestPriority() {
        return [
            // cloning has higher priority than exponentiation
            ['clone $x ** 2', "((T_CLONE(clone), T_VARIABLE(\$x)), T_POW(**), T_LNUMBER(2))"],

            // exponentiation has higher priority than casting
            ['(bool) 1 ** 2', "(T_BOOL_CAST((bool)), (T_LNUMBER(1), T_POW(**), T_LNUMBER(2)))"],

            // multiplication has higher priority than addition
            ['1 + 2 * 3', "(T_LNUMBER(1), '+', (T_LNUMBER(2), '*', T_LNUMBER(3)))"],
        ];
    }

    /**
     * @dataProvider providerForTestPriority
     */
    function testPriority($source, $expected) {

        $result = $this->parseExpr($source);

        $this->assertDumpEquals($expected, $result);
    }

    function providerForTestAssociativity() {
        return [
            // exponentiation is right associative
            ['1 ** 2 ** 3', "(T_LNUMBER(1), T_POW(**), (T_LNUMBER(2), T_POW(**), T_LNUMBER(3)))"],

            // pre-increment and post-increment are right associative
            ['--$x++', "(T_DEC(--), (T_VARIABLE(\$x), T_INC(++)))"],

            // addition is left associative
            ['1 + 2 + 3', "((T_LNUMBER(1), '+', T_LNUMBER(2)), '+', T_LNUMBER(3))"],
        ];
    }

    /**
     * @dataProvider providerForTestAssociativity
     */
    function testAssociativity($source, $expected) {
        $result = $this->parseExpr($source);

        $this->assertDumpEquals($expected, $result);
    }
}
