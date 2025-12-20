<?php
namespace App\DQL;

use Doctrine\ORM\Query\AST\Functions\FunctionNode,
    Doctrine\ORM\Query\TokenType;

class Lpad extends FunctionNode
{
    public $string = null;

    public $length = null;

    public $padstring = null;

    public function parse(\Doctrine\ORM\Query\Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);
        $this->string = $parser->ArithmeticPrimary();
        $parser->match(TokenType::T_COMMA);
        $this->length = $parser->ArithmeticPrimary();
        $parser->match(TokenType::T_COMMA);
        $this->padstring = $parser->ArithmeticPrimary();
        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker): string
    {
        return 'LPAD(' .
        $this->string->dispatch($sqlWalker) . ', ' .
        $this->length->dispatch($sqlWalker) . ', ' .
        $this->padstring->dispatch($sqlWalker) .
        ')';
    }
}