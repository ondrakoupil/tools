<?php

namespace OndraKoupil\Tools;

use OndraKoupil\Tools\Exceptions\FileException;
use OndraKoupil\Tools\Exceptions\FileAccessException;

/**
 * Pár vylepšených nsátrojů pro práci se soubory
 */
class Files {

	const LOWERCASE = "L";
	const UPPERCASE = "U";

	/**
	 * Vrací jen jméno souboru
	 *
	 * `/var/www/vhosts/somefile.txt` => `somefile.txt`
	 *
	 * @param string $in
	 * @return string
	 */
	static function filename($in) {
		return basename($in);
	}

	/**
	 * Přípona souboru
	 *
	 * `/var/www/vhosts/somefile.txt` => `txt`
	 * @param string $in
	 * @param string $case self::LOWERCASE nebo self::UPPERCASE. Cokoliv jiného = neměnit velikost přípony.
	 * @return string
	 */
	static function extension($in,$case=false) {

		$name=self::filename($in);

		if (preg_match('~\.(\w{1,10})\s*$~',$name,$parts)) {
			if (!$case) return $parts[1];
			if (strtoupper($case)==self::LOWERCASE) return Strings::lower($parts[1]);
			if (strtoupper($case)==self::UPPERCASE) return Strings::upper($parts[1]);
			return $parts[1];
		}
		return "";
	}

	/**
	 * Jméno souboru, ale bez přípony.
	 *
	 * `/var/www/vhosts/somefile.txt` => `somefile`
	 * @param type $filename
	 * @return type
	 */
	static function filenameWithoutExtension($filename) {
		$filename=self::filename($filename);
		if (preg_match('~(.*)\.(\w{1,10})$~',$filename,$parts)) {
			return $parts[1];
		}
		return $filename;
	}

	/**
	 * Vrátí jméno souboru, jako kdyby byl přejmenován, ale ve stejném adresáři
	 *
	 * `/var/www/vhosts/somefile.txt` => `/var/www/vhosts/anotherfile.txt`
	 *
	 * @param string $path Původní cesta k souboru
	 * @param string $to Nové jméno souboru
	 * @return string
	 */
	static function changedFilename($path, $newName) {
		return self::dir($path)."/".$newName;
	}

	/**
	 * Jen cesta k adresáři.
	 *
	 * `/var/www/vhosts/somefile.txt` => `/var/www/vhosts`
	 *
	 * @param string $in
	 * @param bool $real True = použít realpath()
	 * @return string Pokud je $real==true a $in neexistuje, vrací empty string
	 */
	static function dir($in,$real=false) {
		if ($real) {
			$in=realpath($in);
			if ($in and is_dir($in)) $in.="/file";
		}
		return dirname($in);
	}

	/**
	 * Přidá do jména souboru něco na konec, před příponu.
	 *
	 * `/var/www/vhosts/somefile.txt` => `/var/www/vhosts/somefile-affix.txt`
	 *
	 * @param string $filename
	 * @param string $addedString
	 * @param bool $withPath Vracet i s cestou? Anebo jen jméno souboru?
	 * @return string
	 */
	static function addBeforeExtension($filename,$addedString,$withPath=true) {
		if ($withPath) {
			$dir=self::dir($filename)."/";
		} else {
			$dir="";
		}
		if (!$dir or $dir=="./") $dir="";
		$filenameWithoutExtension=self::filenameWithoutExtension($filename);
		$extension=self::extension($filename);
		if ($extension) $addExtension=".".$extension;
			else $addExtension="";
		return $dir.$filenameWithoutExtension.$addedString.$addExtension;
	}

	/**
	 * Nastaví práva, aby $filename bylo zapisovatelné, ať už je to soubor nebo adresář
	 * @param string $filename
	 * @return bool Dle úspěchu
	 * @throws Exceptions\FileException Pokud zadaná cesta není
	 * @throws Exceptions\FileAccessException Pokud změna selže
	 */
	static function perms($filename) {

		if (!file_exists($filename)) {
			throw new FileException("Missing: $filename");
		}
		if (!is_writeable($filename)) {
			throw new FileException("Not writable: $filename");
		}

		if (is_dir($filename)) {
			$ok=chmod($filename,0777);
		} else {
			$ok=chmod($filename,0666);
		}

		if (!$ok) {
			throw new FileAccessException("Could not chmod $filename");
		}

		return $ok;
	}

	/**
	 * Přesune soubor i s adresářovou strukturou zpod jednoho do jiného.
	 * @param string $file Cílový soubor
	 * @param string $from Adresář, který brát jako základ
	 * @param string $to Clový adresář
	 * @param bool $copy True (default) = kopírovat, false = přesunout
	 * @return string Cesta k novému souboru
	 * @throws FileException Když $file není nalezeno nebo když selže kopírování
	 * @throws \InvalidArgumentException Když $file není umístěno v $from
	 */
	static function rebaseFile($file, $from, $to, $copy=false) {
		if (!file_exists($file)) {
			throw new FileException("Not found: $file");
		}
		if (!Strings::startsWith($file, $from)) {
			throw new \InvalidArgumentException("File $file is not in directory $from");
		}
		$newPath=$to."/".Strings::substring($file, Strings::length($from));
		$newDir=self::dir($newPath);
		self::createDirectories($newDir);
		if ($copy) {
			$ok=copy($file,$newPath);
		} else {
			$ok=rename($file, $newPath);
		}
		if (!$ok) {
			throw new FileException("Failed copying to $newPath");
		}
		self::perms($newPath);
		return $newPath;
	}

	/**
	 * Vrátí cestu k souboru, jako kdyby byl umístěn do jiného adresáře i s cestou k sobě.
	 * @param string $file Jméno souboru
	 * @param string $from Cesta k němu
	 * @param string $to Adresář, kam ho chceš přesunout
	 * @return string
	 * @throws \InvalidArgumentException
	 */
	static function  rebasedFilename($file,$from,$to) {
		if (!Strings::startsWith($file, $from)) {
			throw new \InvalidArgumentException("File $file is not in directory $from");
		}
		$secondPart=Strings::substring($file, Strings::length($from));
		if ($secondPart[0]=="/") $secondPart=substr($secondPart,1);
		$newPath=$to."/".$secondPart;
		return $newPath;
	}

	/**
	 * Ověří, zda soubor je v zadaném adresáři.
	 * @param string $file
	 * @param string $dir
	 * @return bool
	 */
	static function isFileInDir($file,$dir) {
		if (!Strings::endsWith($dir, "/")) $dir.="/";
		return Strings::startsWith($file, $dir);
	}

	/**
	 * Vytvoří bezpečné jméno pro soubor
	 * @param string $filename
	 * @param array $unsafeExtensions
	 * @param string $safeExtension
	 * @return string
	 */
	static function safeName($filename,$unsafeExtensions=null,$safeExtension="txt") {
		if ($unsafeExtensions===null) $unsafeExtensions=array("php","phtml","inc","php3","php4","php5");
		$extension=self::extension($filename, "l");
		if (in_array($extension, $unsafeExtensions)) {
			$extension=$safeExtension;
		}
		$name=self::filenameWithoutExtension($filename);
		$name=Strings::safe($name, false);
		if (preg_match('~^(.*)[-_]+$~',$name,$partsName)) {
			$name=$partsName[1];
		}
		if (preg_match('~^[-_](.*)$~',$name,$partsName)) {
			$name=$partsName[1];
		}
		$ret=$name;
		if ($extension) $ret.=".".$extension;
		return $ret;
	}

	/**
	 * Vytvoří soubor, pokud neexistuje, a udělá ho zapisovatelným
	 * @param string $filename
	 * @param bool $createDirectoriesIfNeeded
	 * @param string $content Pokud se má vytvořit nový soubor, naplní se tímto obsahem
	 * @return string Jméno vytvořného souboru (cesta k němu)
	 * @throws \InvalidArgumentException
	 * @throws FileException
	 * @throws FileAccessException
	 */
	static function create($filename, $createDirectoriesIfNeeded=true, $content="") {
		if (!$filename) {
			throw new \InvalidArgumentException("Completely missing argument!");
		}
		if (file_exists($filename) and is_dir($filename)) {
			throw new FileException("$filename is directory!");
		}
		if (file_exists($filename)) {
			self::perms($filename);
			return $filename;
		}
		if ($createDirectoriesIfNeeded) self::createDirectories(self::dir($filename, false));
		$ok=@touch($filename);
		if (!$ok) {
			throw new FileAccessException("Could not create file $filename");
		}
		self::perms($filename);
		if ($content) {
			file_put_contents($filename, $content);
		}
		return $filename;
	}

	/**
	 * Vrací práva k určitému souboru či afdresáři jako třímístný string.
	 * @param string $path
	 * @return string Např. "644" nebo "777"
	 * @throws FileException
	 */
	static function getPerms($path) {
		//http://us3.php.net/manual/en/function.fileperms.php example #1
		if (!file_exists($path)) {
			throw new FileException("File '$path' is missing");
		}
		return substr(sprintf('%o', fileperms($path)), -3);
	}

	/**
	 * Pokusí se vytvořit strukturu adresářů v zadané cestě.
	 * @param string $path
	 * @return string Vytvořená cesta
	 * @throws FileException Když už takto pojmenovaný soubor existuje a jde o obyčejný soubor nebo když vytváření selže.
	 */
	static function createDirectories($path) {

		if (!$path) throw new \InvalidArgumentException("\$path can not be empty.");

		/*
		$parts=explode("/",$path);
		$pathPart="";
		foreach($parts as $i=>$p) {
			if ($i) $pathPart.="/";
			$pathPart.=$p;
			if ($pathPart) {
				if (@file_exists($pathPart) and !is_dir($pathPart)) {
					throw new FileException("\"$pathPart\" is a regular file!");
				}
				if (!(@file_exists($pathPart))) {
					self::mkdir($pathPart,false);
				}
			}
		}
		return $pathPart;
		 *
		 */

		if (file_exists($path)) {
			if (is_dir($path)) {
				return $path;
			}
			throw new FileException("\"$path\" is a regular file!");
		}

		$ret = @mkdir($path, 0777, true);
		if (!$ret) {
			throw new FileException("Directory \"$path\ could not be created.");
		}

		return $path;
	}

	/**
	 * Vytvoří adresář, pokud neexistuje, a udělá ho obecně zapisovatelným
	 * @param string $filename
	 * @param bool $createDirectoriesIfNeeded
	 * @return string Jméno vytvořneého adresáře
	 * @throws \InvalidArgumentException
	 * @throws FileException
	 * @throws FileAccessException
	 */
	static function mkdir($filename, $createDirectoriesIfNeeded=true) {
		if (!$filename) {
			throw new \InvalidArgumentException("Completely missing argument!");
		}
		if (file_exists($filename) and !is_dir($filename)) {
			throw new FileException("$filename is not a directory!");
		}
		if (file_exists($filename)) {
			self::perms($filename);
			return $filename;
		}
		if ($createDirectoriesIfNeeded) {
			self::createDirectories($filename);
		} else {
			$ok=@mkdir($filename);
			if (!$ok) {
				throw new FileAccessException("Could not create directory $filename");
			}
		}
		self::perms($filename);
		return $filename;
	}

	/**
	 * Najde volné pojmenování pro soubor v určitém adresáři tak, aby bylo jméno volné.
	 * <br />Pokus je obsazené, pokouší se přidávat pomlčku a čísla až do 99, pak přejde na uniqid():
	 * <br />freeFilename("/files/somewhere","abc.txt");
	 * <br />Bude zkoušet: abc.txt, abc-2.txt, abc-3.txt atd.
	 *
	 * @param string $path Adresář
	 * @param string $filename Požadované jméno souboru
	 * @return string Jméno souboru (ne celá cesta, jen jméno souboru)
	 * @throws AccessException
	 */
	static function freeFilename($path,$filename) {
		if (!file_exists($path) or !is_dir($path) or !is_writable($path)) {
			throw new FileAccessException("Directory $path is missing or not writeble.");
		}
		if (!file_exists($path."/".$filename)) {
			return $filename;
		}
		$maxTries=99;
		$filenamePart=self::filenameWithoutExtension($filename);
		$extension=self::extension($filename);
		$addExtension=$extension?".$extension":"";
		for ( $addedIndex=2 ; $addedIndex<$maxTries ; $addedIndex++ ) {
			if (!file_exists($path."/".$filenamePart."-".$addedIndex.$addExtension)) {
				break;
			}
		}
		if ($addedIndex==$maxTries) {
			return $filenamePart."-".uniqid("").$addExtension;
		}
		return $filenamePart."-".$addedIndex.$addExtension;
	}

	/**
	 * Vymaže obsah adresáře
	 * @param string $dir
	 * @return boolean Dle úspěchu
	 * @throws \InvalidArgumentException
	 */
	static function purgeDir($dir) {
		if (!is_dir($dir)) {
			throw new \InvalidArgumentException("$dir is not directory.");
		}
		$content=glob($dir."/*");
		if ($content) {
			foreach($content as $sub) {
				if ($sub=="." or $sub=="..") continue;
				self::remove($sub);
			}
		}
		return true;
	}

	/**
	 * Smaže adresář a rekurzivně i jeho obsah
	 * @param string $dir
	 * @param int $depthLock Interní, ochrana proti nekonečné rekurzi
	 * @return boolean Dle úspěchu
	 * @throws \RuntimeException
	 * @throws \InvalidArgumentException
	 * @throws FileAccessException
	 */
	static function removeDir($dir,$depthLock=0) {
		if ($depthLock > 15) {
			throw new \RuntimeException("Recursion too deep at $dir");
		}
		if (!file_exists($dir)) {
			return true;
		}
		if (!is_dir($dir)) {
			throw new \InvalidArgumentException("$dir is not directory.");
		}

		$content=glob($dir."/*");
		if ($content) {
			foreach($content as $sub) {
				if ($sub=="." or $sub=="..") continue;
				if (is_dir($sub)) {
					self::removeDir($sub,$depthLock+1);
				} else {
					if (is_writable($sub)) {
						unlink($sub);
					} else {
						throw new FileAccessException("Could not delete file $sub");
					}
				}
			}
		}
		$ok=rmdir($dir);
		if (!$ok) {
			throw new FileAccessException("Could not remove dir $dir");
		}

		return true;
	}

	/**
	 * Smaže $path, ať již je to adresář nebo soubor
	 * @param string $path
	 * @param bool $onlyFiles Zakáže mazání adresářů
	 * @return boolean Dle úspěchu
	 * @throws FileAccessException
	 * @throws FileException
	 */
	static function remove($path, $onlyFiles=false) {
		if (!file_exists($path)) {
			return true;
		}
		if (is_dir($path)) {
			if ($onlyFiles) throw new FileException("$path is a directory!");
			return self::removeDir($path);
		}
		else {
			$ok=unlink($path);
			if (!$ok) throw new FileAccessException("Could not delete file $path");
		}
		return true;
	}

    /**
     * Stažení vzdáleného souboru pomocí  cURL
     * @param $url URL vzdáleného souboru
     * @param $path Kam stažený soubor uložit?
     * @param bool $stream
     */
    public static function downloadFile($url, $path, $stream = TRUE) {

        $curl = curl_init($url);

        if(!$stream) {

            curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
            file_put_contents($path, curl_exec($curl));
        }
        else {

            $fp = fopen($path, 'w');

            curl_setopt($curl, CURLOPT_FILE, $fp);
            curl_exec($curl);
            fclose($fp);
        }

        curl_close($curl);
    }

	/**
	 * Vrací maximální nahratelnou velikost souboru.
	 *
	 * Bere menší z hodnot post_max_size a upload_max_filesize a převede je na obyčejné číslo.
	 * @return int Bytes
	 */
	static function maxUploadFileSize() {
		$file_max = Strings::parsePhpNumber(ini_get("post_max_size"));
		$post_max = Strings::parsePhpNumber(ini_get("upload_max_filesize"));
		$php_max = min($file_max,$post_max);
		return $php_max;
	}

}
