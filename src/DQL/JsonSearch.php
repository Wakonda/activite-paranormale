<?php
namespace App\DQL;

use Doctrine\ORM\Query\AST\Functions\FunctionNode,
    Doctrine\ORM\Query\Lexer;

class JsonSearch extends FunctionNode
{
    protected $parsedArguments = [];
	
    public function parse(\Doctrine\ORM\Query\Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
		
        $this->parsedArguments[] = $parser->StringPrimary();
		
		$parser->match(Lexer::T_COMMA);
		
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

		$parser->match(Lexer::T_COMMA);

		$this->parsedArguments[] = $parser->StringPrimary();

		$parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }


    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker)
    {
        return sprintf('JSON_SEARCH(%s, %s, %s, %s)',
            $sqlWalker->walkStringPrimary($this->parsedArguments[0]),
            $sqlWalker->walkStringPrimary($this->parsedArguments[1]),
            $sqlWalker->walkStringPrimary($this->parsedArguments[2]),
			$this->parsedArguments[3] !== null ? $sqlWalker->walkStringPrimary($this->parsedArguments[3]) : 'NULL',
            $sqlWalker->walkStringPrimary($this->parsedArguments[4])
		);
    }
}