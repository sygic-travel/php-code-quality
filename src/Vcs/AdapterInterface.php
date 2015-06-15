<?php

namespace Tripomatic\PhpCodeQuality\Vcs;

interface AdapterInterface
{
	/**
	 * @return string[]
	 */
	public function getStagedFiles();

	/**
	 * @param string $file
	 * @return bool
	 */
	public function isTracked($file);

	/**
	 * @return string
	 */
	public function getRootDirectory();

	/**
	 * @return string
	 */
	public function getWorkingDirectory();
}
