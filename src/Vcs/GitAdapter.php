<?php

namespace Tripomatic\PhpCodeQuality\Vcs;

use Symfony\Component\Process\ProcessBuilder;

class GitAdapter implements AdapterInterface
{
	/** @var string */
	protected $binaryPath;

	/** @var string */
	protected $root;

	/** @var string */
	protected $temp;

	/** @var string[] */
	protected $files = [];

	public function __construct($binaryPath = 'git')
	{
		// get root directory
		$processBuilder = new ProcessBuilder([$binaryPath, 'rev-parse', '--git-dir']);
		$process = $processBuilder->getProcess();
		$process->mustRun();
		$this->root = realpath(dirname($process->getOutput()));

		// get temp directory
		$this->temp = sys_get_temp_dir() . uniqid('/php-code-quality-');

		// get staged files
		$processBuilder = new ProcessBuilder([$binaryPath,  'diff', '--cached', '--name-only', '--diff-filter=ACMR']);
		$processBuilder->setWorkingDirectory($this->root);
		$process = $processBuilder->getProcess();
		$process->mustRun();
		$this->files = preg_split('/\r\n?|\n/', $process->getOutput(), -1, PREG_SPLIT_NO_EMPTY);

		// copy actual index
		$processBuilder = new ProcessBuilder([$binaryPath,  'checkout-index', '--all', "--prefix={$this->temp}/"]);
		$processBuilder->setWorkingDirectory($this->root);
		$process = $processBuilder->getProcess();
		$process->mustRun();

		$this->binaryPath = $binaryPath;
		$this->temp = realpath($this->temp);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getRootDirectory()
	{
		return $this->root;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getWorkingDirectory()
	{
		return $this->temp;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getStagedFiles()
	{
		return $this->files;
	}

	/**
	 * {@inheritdoc}
	 */
	public function isTracked($file)
	{
		$processBuilder = new ProcessBuilder([$this->binaryPath, 'ls-files', $file, '--error-unmatch']);
		$processBuilder->setWorkingDirectory($this->root);
		$process = $processBuilder->getProcess();
		$process->run();

		return $process->isSuccessful();
	}

	public function __destruct()
	{
		if ($this->temp !== null) {
			$processBuilder = new ProcessBuilder(['rm',  '-rf', $this->temp]);
			$processBuilder->setWorkingDirectory($this->root);
			$process = $processBuilder->getProcess();
			$process->run();
		}
	}
}
