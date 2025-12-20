<?php
namespace App\DQL;

use Doctrine\ORM\Query\AST\Functions\FunctionNode,
    Doctrine\ORM\Query\TokenType;
/**
 * @author Andrew Mackrodt <andrew@ajmm.org>
 */
class IfElse extends FunctionNode
{
    private $expr = array();
    public function parse(\Doctrine\ORM\Query\Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);
        $this->expr[] = $parser->ConditionalExpression();
        for ($i = 0; $i < 2; $i++)
        {
            $parser->match(TokenType::T_COMMA);
            $this->expr[] = $parser->ArithmeticExpression();
        }
        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker): string
    {
        return sprintf('IF(%s, %s, %s)',
            $sqlWalker->walkConditionalExpression($this->expr[0]),
            $sqlWalker->walkArithmeticPrimary($this->expr[1]),
            $sqlWalker->walkArithmeticPrimary($this->expr[2]));
    }
}