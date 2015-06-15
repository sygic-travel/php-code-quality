<?php

namespace Tripomatic\PhpCodeQuality\Vcs;

use Symfony\Component\Process\ProcessBuilder;

class MercurialAdapter implements AdapterInterface
{
	/** @var string */
	protected $root;

	/** @var string[] */
	protected $files = [];

	public function __construct()
	{
		// get root directory
		$processBuilder = new ProcessBuilder(['hg', 'root']);
		$process = $processBuilder->getProcess();
		$process->mustRun();
		$this->root = trim($process->getOutput());

		// get staged files
		$processBuilder = new ProcessBuilder(['hg',  'status', '--added', '--modified', '--no-status']);
		$processBuilder->setWorkingDirectory($this->root);
		$process = $processBuilder->getProcess();
		$process->mustRun();
		$this->files = preg_split('/\r\n?|\n/', $process->getOutput(), -1, PREG_SPLIT_NO_EMPTY);
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
		return $this->root;
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
		$processBuilder = new ProcessBuilder(array_merge(['git',  'ls-files', $file, '--error-unmatch']));
		$processBuilder->setWorkingDirectory($this->root);
		$process = $processBuilder->getProcess();
		$process->run();

		return $process->isSuccessful();
	}
}
