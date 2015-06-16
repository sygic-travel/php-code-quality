<?php

namespace Tripomatic\PhpCodeQuality\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\ProcessBuilder;
use Tripomatic\PhpCodeQuality\Vcs\AdapterInterface;
use Tripomatic\PhpCodeQuality\Vcs\GitAdapter;
use Tripomatic\PhpCodeQuality\Vcs\MercurialAdapter;

class CheckStagedFilesCommand extends Command
{
	const REPOSITORY_GIT = 'git';
	const REPOSITORY_MERCURIAL = 'mercurial';

	/** @var AdapterInterface */
	private $adapter;

	protected function configure()
	{
		$this
			->setName('check-staged-files')
			->setDescription('Checks files that are staged for a commit in VCS (Git, Mercurial)');
	}

	/**
	 * @param InputInterface  $input
	 * @param OutputInterface $output
	 * @return int|null
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$output->writeln('<options=bold>PHP Code Quality:</options=bold>');

		// detect repository type
		list($repositoryType, $binaryPath) = $this->detectRepositoryType();
		if ($repositoryType === self::REPOSITORY_GIT) {
			$this->adapter = $adapter = new GitAdapter($binaryPath);
		} elseif ($repositoryType === self::REPOSITORY_MERCURIAL) {
			$this->adapter = $adapter = new MercurialAdapter($binaryPath);
		} else {
			$output->writeln('<error>This directory does not seem to be a GIT or Mercurial repository.</error>');
			return 1;
		}

		// composer
		$composerErrors = $this->checkComposer();
		$this->printResult($output, 'Composer sync check... ', 'Errors:', $composerErrors);

		// syntax
		$syntaxErrors = $this->checkSyntax();
		$this->printResult($output, 'Syntax check... ', 'Syntax errors in files:', $syntaxErrors);
		if ($syntaxErrors !== []) {
			$output->writeln('To inspect the problems use:');
			foreach ($syntaxErrors as $error) {
				$arg = escapeshellarg($error);
				$output->writeln("    <info>$ php -l $arg</info>");
			}
		}

		// coding style
		if (!file_exists($this->adapter->getWorkingDirectory() . '/.php_cs')) {
			$output->writeln("<error>Your project does not contain PHP-CS-Fixer configuration file '.php_cs'.</error>");
			return 1;
		}
		$codingStyleErrors = $this->checkCodingStyle($syntaxErrors);
		$this->printResult($output, 'Coding style check... ', 'Coding style violations in files:', $codingStyleErrors);
		if ($codingStyleErrors !== []) {
			$output->writeln("To fix the problems use:\n    <info>$ php vendor/bin/php-cs-fixer fix</info>");
		}

		if ($composerErrors !== [] || $syntaxErrors !== [] || $codingStyleErrors !== []) {
			return 1;
		}
		$output->writeln('');
	}

	/**
	 * @return string[]
	 */
	protected function checkComposer()
	{
		// if one of the files is not tracked there is nothing to check
		if (!$this->adapter->isTracked('composer.json') || !$this->adapter->isTracked('composer.lock')) {
			return [];
		}

		// both files are tracked - check if they are in sync
		$json = $this->adapter->getWorkingDirectory() . '/composer.json';
		$lock = $this->adapter->getWorkingDirectory() . '/composer.lock';

		$jsonHash = md5(file_get_contents($json));
		$lockHash = json_decode(file_get_contents($lock))->hash;

		if ($jsonHash !== $lockHash) {
			return ["Files 'composer.json' and 'composer.lock' are not in sync. Please run 'composer update'."];
		}

		return [];
	}

	/**
	 * @return string[]
	 */
	protected function checkSyntax()
	{
		$files = $this->adapter->getStagedFiles();

		$errors = [];
		foreach ($files as $name => $file) {
			$processBuilder = new ProcessBuilder(['php', '-l', $file]);
			$processBuilder->setWorkingDirectory($this->adapter->getWorkingDirectory());
			$process = $processBuilder->getProcess();
			$process->run();

			if (!$process->isSuccessful()) {
				$errors[] = $name;
			}
		}
		return $errors;
	}

	/**
	 * @return string[]
	 */
	protected function checkCodingStyle($badSyntaxFiles = [])
	{
		$staged = $this->adapter->getStagedFiles();

		// use PHP-CS-Fixer configuration from '.php_cs'
		$configFile = $this->adapter->getWorkingDirectory() . '/.php_cs';
		$config = include $configFile;
		if (!$config instanceof \Symfony\CS\Config\Config) {
			throw new \UnexpectedValueException("The config file '$configFile' does not return a 'Symfony\\CS\\Config\\Config' instance.");
		}

		// get list of files that are registered with PHP-CS-Fixer
		$registeredFiles = array_map(function (\SplFileInfo $splFileInfo) {
			return substr($splFileInfo->getRealPath(), strlen($this->adapter->getWorkingDirectory()) + 1);
		}, iterator_to_array($config->getFinder()));

		if (in_array('.php_cs', $staged)) {
			$staged = $registeredFiles;
		}

		$errors = [];
		foreach ($staged as $file) {

			// skip files not registered with PHP-CS-Fixer
			if (!in_array($file, $registeredFiles)) {
				continue;
			}

			// skip files with syntax errors (and mark them as invalid)
			if (in_array($file, $badSyntaxFiles)) {
				$errors[] = $file;
				continue;
			}

			// check coding style
			$command = $this->adapter->getRootDirectory() . '/vendor/bin/php-cs-fixer';
			$processBuilder = new ProcessBuilder([$command, 'fix', $file, '--dry-run', '--config-file=.php_cs']);
			$processBuilder->setWorkingDirectory($this->adapter->getWorkingDirectory());
			$process = $processBuilder->getProcess();
			$process->run();

			if (!$process->isSuccessful()) {
				$errors[] = $file;
			}
		}
		return $errors;
	}

	/**
	 * @return null|string
	 */
	private function detectRepositoryType()
	{
		$prefixes = ['', '/usr/local/bin/', '/usr/bin/', '/bin/', '/usr/bin/', '/sbin/'];

		// git
		foreach ($prefixes as $prefix) {
			$processBuilder = new ProcessBuilder([$prefix . 'git', 'rev-parse', '--git-dir']);
			$process = $processBuilder->getProcess();
			$process->run();

			if ($process->isSuccessful()) {
				return [self::REPOSITORY_GIT, $prefix . 'git'];
			}

			// mercurial
			$processBuilder = new ProcessBuilder([$prefix . 'hg', 'root']);
			$process = $processBuilder->getProcess();
			$process->run();

			if ($process->isSuccessful()) {
				return [self::REPOSITORY_MERCURIAL, $prefix . 'hg'];
			}
		}

		return [null, null];
	}

	/**
	 * @param string   $output
	 * @param string   $caption
	 * @param string   $errorCaption
	 * @param string[] $errors
	 */
	private function printResult($output, $caption, $errorCaption, $errors)
	{
		$output->write($caption);
		if ($errors === []) {
			$output->writeln('<info>✓</info>');
		} else {
			$output->writeln("<error>$errorCaption</error>");
			foreach ($errors as $error) {
				$output->writeln("    <fg=red>✘ $error</fg=red>");
			}
		}
	}
}
