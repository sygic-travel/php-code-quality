<?php

namespace Symfony\CS\Fixer\Contrib;

use Symfony\CS\Fixer\PSR2\IndentationFixer;
use Symfony\CS\Tokenizer\Tokens;

class IndentWithTabsFixer extends IndentationFixer
{
	/**
	 * {@inheritdoc}
	 */
	public function fix(\SplFileInfo $file, $content)
	{
		$content = parent::fix($file, $content);

		$tokens = Tokens::fromCode($content);
		foreach ($tokens as $index => $token) {
			if ($token->isWhitespace() || $token->isComment()) {
				$lines = preg_split('/(\R)/', $token->getContent(), -1, PREG_SPLIT_DELIM_CAPTURE);
				foreach ($lines as &$line) {
					$line = preg_replace_callback('/^( {4,})/', function ($matches) {
						return str_replace('    ', "\t", $matches[0]);
					}, $line);
				}
				$tokens[$index]->setContent(implode('', $lines));
			}
		}

		return $tokens->generateCode();
	}

	/**
	 * {@inheritdoc}
	 */
	public function getDescription()
	{
		return 'Code MUST use tabs for indenting, and MUST NOT use spaces.';
	}

	/**
	 * {@inheritdoc}
	 */
	public function getPriority()
	{
		// will be run almost last (just before PSR2 EofEndingFixer)
		return -49;
	}
}
