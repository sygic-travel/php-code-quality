<?php

namespace Tripomatic\PhpCodeQuality\Vcs;

use Symfony\Component\Process\ProcessBuilder;

class MercurialAdapter implements AdapterInterface
{
	/** @var string */
	protected $binaryPath;

	/** @var string */
	protected $root;

	/** @var string[] */
	protected $files = [];

	public function __construct($binaryPath = 'hg')
	{
		// get root directory
		$processBuilder = new ProcessBuilder([$binaryPath, 'root']);
		$process = $processBuilder->getProcess();
		$process->mustRun();
		$this->root = trim($process->getOutput());

		// get staged files
		$processBuilder = new ProcessBuilder([$binaryPath,  'status', '--added', '--modified', '--no-status']);
		$processBuilder->setWorkingDirectory($this->root);
		$process = $processBuilder->getProcess();
		$process->mustRun();
		$this->files = preg_split('/\r\n?|\n/', $process->getOutput(), -1, PREG_SPLIT_NO_EMPTY);

		$this->binaryPath = $binaryPath;
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
		$processBuilder = new ProcessBuilder([$this->binaryPath, 'locate', $file]);
		$processBuilder->setWorkingDirectory($this->root);
		$process = $processBuilder->getProcess();
		$process->run();

		return $process->isSuccessful();
	}
}
