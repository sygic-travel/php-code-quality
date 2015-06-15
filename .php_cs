<?php

$fixers = [
	# turn off some PSR-2 rules
	'-indentation',

	# turn off some Symfony rules
	'-concat_without_spaces',
	'-phpdoc_separation',
	'-return',

	# add some Contrib rules
	'concat_with_spaces',
	'header_comment',
	'multiline_spaces_before_semicolon',
	'newline_after_open_tag',
	'ordered_use',
	'phpdoc_order',
	'short_array_syntax',

	# add custom Contrib rules
	'indent_with_tabs',
];

// remove file headers
Symfony\CS\Fixer\Contrib\HeaderCommentFixer::setHeader('');

// setup files to check
$finder = Symfony\CS\Finder\DefaultFinder::create()
	->in(__DIR__ . '/src')
	->in(__DIR__ . '/src-cs')
	->append([
		__DIR__ . '/.php_cs',
		__DIR__ . '/bin/php-code-quality',
	]);

// use default SYMFONY_LEVEL and extra fixers:
return Symfony\CS\Config\Config::create()
	->level(Symfony\CS\FixerInterface::SYMFONY_LEVEL)
	->addCustomFixer(new Symfony\CS\Fixer\Contrib\IndentWithTabsFixer())
	->fixers($fixers)
	->finder($finder);
