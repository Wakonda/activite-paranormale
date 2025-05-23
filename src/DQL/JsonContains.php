<?php
namespace App\DQL;

use Doctrine\ORM\Query\AST\Functions\FunctionNode,
    Doctrine\ORM\Query\Lexer;

class JsonContains extends FunctionNode
{
    protected $parsedArguments = [];
	
    public function parse(\Doctrine\ORM\Query\Parser $parser): void
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
		
        $this->parsedArguments[] = $parser->StringPrimary();
		
		$parser->match(Lexer::T_COMMA);
		
		$this->parsedArguments[] = $parser->StringPrimary();
		
		$parser->match(Lexer::T_COMMA);

        if ($parser->getLexer()->isNextToken(Lexer::T_NULL)) {
            $parser->match(Lexer::T_NULL);
            $this->parsedArguments[] = null;
        } else {
            $this->parsedArguments[] = $parser->StringPrimary();
        }

		$parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker): string
    {
        return sprintf('JSON_CONTAINS(%s, %s, %s)',
            $sqlWalker->walkStringPrimary($this->parsedArguments[0]),
            $sqlWalker->walkStringPrimary($this->parsedArguments[1]),
			$this->parsedArguments[2] !== null ? $sqlWalker->walkStringPrimary($this->parsedArguments[2]) : 'NULL'
		);
    }
}