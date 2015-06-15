# Tripomatic\PhpCodeQuality
A set of PHP code quality tools for VCS.

Tripomatic\PhpCodeQuality allows for automatic PHP code checking while commiting changes in VCS. At the moment [Git](https://git-scm.com) and [Mercurial](https://mercurial.selenic.com) are supported. The tool can perform the following pre-commit checks:

1. If both `composer.json` and `composer.lock` are versioned it checks whether they are in sync.
2. All changed or added PHP files are checked for syntax errors with `php -l`.
3. All changed or added PHP files are chekced for coding style with [PHP-CS-Fixer](https://github.com/FriendsOfPHP/PHP-CS-Fixer). This check requires coding style definition file `.php_cs` in the repository root.

## Installation
Install Tripomatic\PhpCodeQuality using [Composer](https://getcomposer.org):
```Shell
$ composer require --dev tripomatic/php-code-quality
```

To setup automatic code checking before every commit add the following line to your pre-commit hook:
```
php vendor/bin/php-code-quality check-staged-files
```
The tool automatically detects the repository type (Git, Mercurial) and checks the changes to be commited.

> In Git these changes doesn't have to reflect the working tree so the actual changes are fetched to a temporary directory and the files are controlled there. The directory is automatically removed.

## Automatic setup with Composer
The pre-commit hooks can be also set-up automatically using [Composer's scripts](https://getcomposer.org/doc/articles/scripts.md). For instance you can add the following lines to your `composer.json`:
```json
"scripts": {
	"pre-install-cmd": "sh git/install-hooks.sh",
	"pre-update-cmd": "sh git/install-hooks.sh"
}
```
And create a `git/install-hooks.sh` file containing:
```bash
#!/bin/sh
ROOT="$(cd "$(dirname "$0")"/..; pwd -P)"

echo "Installing GIT hooks"
rm -rf ${ROOT}/.git/hooks
ln -s ${ROOT}/git/hooks ${ROOT}/.git/hooks
chmod +x ${ROOT}/.git/hooks/*
```
And finally, create a `git/hooks` directory and a `pre-commit` file in it containing:
```bash
#!/bin/sh
ROOT="$(cd "$(dirname "$0")"/../..; pwd -P)"

php ${ROOT}/vendor/bin/php-code-quality check-staged-files
```
The `git` directory can be added to the repository and that way the pre-commit code-checking hook will be automatically installed on any `composer install` and `composer update`.

## Other
In case you use tabs for indenting this package contains also an [`IndentWithTabsFixer`](src-cs/Fixer/Contrib/IndentWithTabsFixer.php) for [PHP-CS-Fixer](https://github.com/FriendsOfPHP/PHP-CS-Fixer). Installing in the `.php_cs` is easy:

```php
return Symfony\CS\Config\Config::create()
	->level(Symfony\CS\FixerInterface::SYMFONY_LEVEL)
	->addCustomFixer(new Symfony\CS\Fixer\Contrib\IndentWithTabsFixer())
	->fixers([
	    '-indentation', # turn off the PSR-2 rule
	    'indent_with_tabs',
	]);
```

A complete example can be the [`.php_cs`](.php_cs) for this repository.

## License
Tripomatic\PhpCodeQuality is licensed under [MIT](LICENSE).
