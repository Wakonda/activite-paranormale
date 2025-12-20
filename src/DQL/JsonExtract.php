<?php
namespace App\DQL;

use Doctrine\ORM\Query\AST\Functions\FunctionNode,
    Doctrine\ORM\Query\TokenType;

class JsonExtract extends FunctionNode
{
    protected $parsedArguments = [];
	
    public function parse(\Doctrine\ORM\Query\Parser $parser): void
    {
        $lexer = $parser->getLexer();
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);
		
        $this->parsedArguments[] = $parser->StringPrimary();
		
		$parser->match(TokenType::T_COMMA);
		
		$this->parsedArguments[] = $parser->StringPrimary();

        if(TokenType::T_COMMA === $lexer->lookahead['type']){
            $parser->match(TokenType::T_COMMA);
            $this->parsedArguments[] = $parser->StringPrimary();
        }

		$parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }


    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker): string
    {
		if(isset($this->parsedArguments[2]))
        return sprintf('JSON_EXTRACT(%s, %s, %s)',
            $sqlWalker->walkStringPrimary($this->parsedArguments[0]),
            $sqlWalker->walkStringPrimary($this->parsedArguments[1]),
			$this->parsedArguments[2] !== null ? $sqlWalker->walkStringPrimary($this->parsedArguments[2]) : 'NULL',
		);
		else
        return sprintf('JSON_EXTRACT(%s, %s)',
            $sqlWalker->walkStringPrimary($this->parsedArguments[0]),
            $sqlWalker->walkStringPrimary($this->parsedArguments[1])
		);
    }
}