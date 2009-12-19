<?php

class FileSystem
{
	/**
	 * Copies a file or directory recursively. This will overwrite existing files but will not remove existing ones.
	 *
	 * @param string $src
	 * @param string $dest
	 * @param int $dirPerm To use an octal value like '0755' use "octdec()". Defaults to the sources permissions.
	 * @param int $filePerm To use an octal value like '0755' use "octdec()". Defaults to the sources permissions.
	 * @param bool $stopOnError
	 * @return bool
	 */
	static function copyRecursive($src, $dest, $dirPerm = null, $filePerm = null, $stopOnError = true)
	{
		$cwd = getcwd();

		if($src[0] != '/')
			$src = $cwd . '/' . $src;

		if($dest[0] != '/')
			$dest = $cwd . '/' . $dest;

		try{

			if(!is_readable($src))
				throw new FileSystemError('Unable to read file ' . $src . ' when attempting to copy it.');

			if(is_file($src))
			{
				if(is_null($filePerm))
					$filePerm = octdec(substr(sprintf('%o', fileperms($src)), -4));

				if(is_dir($dest))
				{
					if($dest[strlen($dest) - 1] != '/');
						$dest .= '/';

					$filePath = $dest . basename($src);

				}else{

					$dirName = dirname($dest);
					if(!is_dir($dirName))
						throw new FileSystemError('Unable to copy file ' . $src . ' into non-existant directory '
												  . $dirName . '.' );

					$filePath = $dest;
				}

				$srcHandler = fopen($src, 'r');
				$destHandler = fopen($filePath, 'w');

				$bytes = stream_copy_to_stream($srcHandler, $destHandler);
				chmod($filePath, $filePerm);
				return true;

			}elseif(is_dir($src)){

				$realDirPerms = isset($dirPerm) ? $dirPerm : octdec(substr(sprintf('%o', fileperms($src)), -4));

				if(!is_dir($dest))
				{
					mkdir($dest, $realDirPerms, true);
					chmod($dest, $realDirPerms);
				}

				if($src[strlen($src) - 1] != '/')
					$src .= '/';

				if($dest[strlen($dest) - 1] != '/')
					$dest .= '/';

				$filesInDir = glob($src . '*');
				$tempfilesInDir = glob($src . '.*');
				$filesInDir = array_merge($filesInDir, $tempfilesInDir);

				foreach($filesInDir as $file)
				{
					if(substr($file, -2) == '/.' || substr($file, -3) == '/..')
						continue;

					$fileName = basename($file);
					$testDir = dirname($dest . $fileName) . '/';
					if(strpos(realpath($testDir) . '/', $dest) !== 0)
						continue;

					self::copyRecursive($src . $fileName, $dest . $fileName, $dirPerm, $filePerm, $stopOnError);
				}

				return true;

			}else{
				return false;
			}

		}catch(Exception $e){

			if(!$stopOnError)
				return false;

			throw $e;
		}
	}

	static function deleteRecursive($file)
	{
		if(substr($file, 0, 1) !== '/')
			throw new FileSystemError('deltree function requires an absolute path.');

		$badCalls = array('/', '/*', '/.', '/..');
		if(in_array($file, $badCalls))
			throw new FileSystemError('deltree function does not like that call.');

		$file = rtrim($file, ' /');
		if(is_dir($file)) {
			$hiddenFiles = glob($file.'/.?*');
			$files = glob($file.'/*');
			$files = array_merge($hiddenFiles, $files);

			foreach($files as $filePath)
			{
				if(substr($filePath, -2, 2) == '/.' || substr($filePath, -3, 3) == '/..')
					continue;

				if(is_dir($filePath) && !is_link($filePath)) {
					deltree($filePath);
				}else{
					unlink($filePath);
				}
			}
			rmdir($file);
		}
	}
}


class FileSystemError extends Exception {} //CoreError {}
class FileSystemWarning extends Exception {} //FileSystemWarning {}

//class FileSystemError extends CoreError {}
//class FileSystemWarning extends FileSystemWarning {}

?>