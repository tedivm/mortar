<?php

class ShellExec
{
	protected $longOpts = array();
	protected $shortOpts = array();
	protected $arguments = array();
	protected $flags = array();

	protected $pipeOutput;
	protected $outputFile;
	protected $truncate = true;

	protected $binary;

	public function run()
	{
		$command = $this->getShellString();
		return shell_exec($command);
	}

	public function setBinary($path)
	{
		if($tmppath = realpath($path))
		{
			$path = $tmppath;
		}else{

			$command = 'which ' . escapeshellarg($path);
			$results = shell_exec($command);
			$results = trim($results);

			if(!(strlen($results) > 0))
				return false;

			$path = $results;
		}

		$this->binary = $path;
	}

	public function setOutputFile($file, $truncate = true)
	{
		$this->outputFile = $file;
		$this->truncate = (bool) $truncate;
	}

	public function addArgument($argument)
	{
		$this->arguments[] = $argument;
	}

	public function addFlag($flag, $value = null)
	{
		if(is_null($value))
		{
			$this->flags[] = $flag;
		}else{
			$this->shortOpts[$flag] = $value;
		}
	}

	public function addOption($option, $value = null)
	{
		if(is_null($value))
			$value = '';

		$this->longOpts[$option] = $value;
	}

	public function addPipe(ShellExec $pipeTo)
	{
		$this->pipeOutput = $pipeTo;
	}

	public function getShellString()
	{
		if(!isset($this->binary))
			throw new CoreError('Binary needs to be set');

		$string = $this->binary;

		if(count($this->flags) > 0)
			$string .= ' -' . implode('', $this->flags);

		$string .= $this->buildOptions($this->shortOpts, '-');
		$string .= $this->buildOptions($this->longOpts, '--');

		if(count($this->arguments) > 0)
			foreach($this->arguments as $argument)
				$string .= ' ' . self::escape($argument);

		if(isset($this->pipeOutput))
			$string .= ' | ' . $this->pipeOutput->getShellString();

		if(isset($this->outputFile))
		{
			$string .= ' ' . ($this->truncate ? '>' : '>>');
			$string .= ' ' . self::escape($this->outputFile);
		}

		return $string;
	}

	protected function buildOptions($options, $prefix)
	{
		$string = '';
		foreach($options as $name => $option)
		{
			$string .= ' ' . $prefix . $name;

			if($option instanceof ShellExec)
				$option = $option->getShellString();

			if(strlen($option) > 0)
				$string .= ' ' . self::escape($option);
		}
		return $string;
	}

	static function escape($value)
	{
		if(is_numeric($value))
			return $value;

		return escapeshellarg($value);
	}
}

?>