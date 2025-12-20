<?php

namespace App\DQL;

use Doctrine\ORM\Query\TokenType;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;

/**
 * @author Ahwalian Masykur <ahwalian@gmail.com>
 */
class Extract extends FunctionNode
{
    public $date = null;

    public $unit = null;

    public function parse(\Doctrine\ORM\Query\Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);

        $parser->match(TokenType::T_IDENTIFIER);
        $lexer = $parser->getLexer();
        $this->unit = $lexer->token->value;

        $parser->match(TokenType::T_IDENTIFIER);
        $this->date = $parser->ArithmeticPrimary();

        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker): string
    {
        $unit = strtoupper($this->unit);
        // if (!in_array($unit, self::$allowedUnits)) {
            // throw QueryException::semanticalError('EXTRACT() does not support unit "' . $unit . '".');
        // }

        return 'EXTRACT(' . $unit . ' FROM '. $this->date->dispatch($sqlWalker) . ')';
    }
}