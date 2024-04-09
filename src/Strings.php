<?php

namespace OndraKoupil\Tools;

use ArrayAccess;

class Strings {

	/**
	 * Skloňuje řetězec dle českých pravidel řetězec
	 * @param number $amount
	 * @param string $one Lze použít dvě procenta - %% - pro nahrazení za $amount
	 * @param string $two
	 * @param string $five Vynechat nebo null = použít $two
	 * @param string $zero Vynechat nebo null = použít $five
	 * @return string
	 */
	static function plural($amount, $one, $two = null, $five = null, $zero = null) {
		if ($two === null) $two = $one;
		if ($five === null) $five = $two;
		if ($zero === null) $zero = $five;
		if ($amount==1) return str_replace("%%",$amount,$one);
		if ($amount>1 and $amount<5) return str_replace("%%",$amount,$two);
		if ($amount == 0) return str_replace("%%",$amount,$zero);
		return str_replace("%%",$amount,$five);
	}

	/**
	 * strlen pro UTF-8
	 * @param string $input
	 * @return int
	 */
	static function length($input) {
		return mb_strlen($input, "utf-8");
	}

	/**
	 * strlen pro UTF-8
	 * @param string $input
	 * @return int
	 */
	static function strlen($input) {
		return self::length($input);
	}

	/**
	 * substr() pro UTF-8
	 *
	 * @param string $input
	 * @param int $start
	 * @param int $length
	 * @return string
	 */
	static function substring($input, $start, $length = null) {
		return self::substr($input, $start, $length, "utf-8");
	}

	/**
	 * substr() pro UTF-8
	 *
	 * @param string $input
	 * @param int $start
	 * @param int $length
	 * @return string
	 */
	static function substr($input, $start, $length = null) {
		if ($length === null) {
			$length = self::length($input) - $start;
		}
		return mb_substr($input, $start, $length, "utf-8");
	}

	static function strpos($haystack, $needle, $offset = 0) {
		return mb_strpos($haystack, $needle, $offset, "utf-8");
	}


	static function strToLower($string) {
		return mb_strtolower($string, "utf-8");
	}

	static function lower($string) {
		return self::strToLower($string);
	}

	static function strToUpper($string) {
		return mb_strtoupper($string, "utf-8");
	}

	static function upper($string) {
		return self::strToUpper($string);
	}

    /**
     * Otestuje zda řetězec obsahuje hledaný výraz
     * @param $haystack
     * @param $needle
     * @return bool
     */
    public static function contains($haystack, $needle) {
        return strpos($haystack, $needle) !== FALSE;
    }

    /**
     * Otestuje zda řetězec obsahuje hledaný výraz, nedbá na velikost znaků
     * @param $haystack
     * @param $needle
     * @return bool
     */
    public static function icontains($haystack, $needle) {
        return stripos($haystack, $needle) !== FALSE;
    }

	/**
	* Funkce pro zkrácení dlouhého textu na menší délku.
	* Ořezává tak, aby nerozdělovala slova, a případně umí i odstranit HTML znaky.
	* @param string $text Původní (dlouhý) text
	* @param int $length Požadovaná délka textu. Oříznutý text nebude mít přesně tuto délku, může být o nějaké znaky kratší nebo delší podle toho, kde končí slovo.
	* @param string $ending Pokud dojde ke zkrácení textu, tak s na jeho konec přilepí tento řetězec. Defaultně trojtečka (jako HTML entita &amp;hellip;). TRUE = &amp;hellip; (nemusíš si pak pamatovat tu entitu)
	* @param bool $stripHtml Mají se odstraňovat HTML tagy? True = odstranit. Zachovají se pouze <br />, a všechny konce řádků (\n i \r) budou nahrazeny za <br />.
	* Odstraňování je důležité, jinak by mohlo dojít k ořezu uprostřed HTML tagu, anebo by nebyl nějaký tag správně ukončen.
	* Pro ořezávání se zachováním html tagů je shortenHtml().
	* @param bool $ignoreWords Ignorovat slova a rozdělit přesně.
	* @return string Zkrácený text
	*/
	static function shorten($text, $length, $ending="&hellip;", $stripHtml=true, $ignoreWords = false) {
		if ($stripHtml) {
			$text=self::br2nl($text);
			$text=strip_tags($text);
		}
		$text=trim($text);
		if ($ending===true) $ending="&hellip;";

		$text = trim($text);

		$needsTrim = (self::strlen($text) > $length);
		if (!$needsTrim) {
			return $text;
		}

		$hardTrimmed = self::substr($text, 0, $length);

		if (!$ignoreWords) {
			$nextChar = self::substr($text, $length, 1);
			if (!preg_match('~[\s.,/\-]~', $nextChar)) {
				$endingRemains = preg_match('~[\s.,/\-]([^\s.,/\-]*)$~', $hardTrimmed, $foundParts);
				if ($endingRemains) {
					$endingLength = self::strlen($foundParts[1]);
					$hardTrimmed = self::substr($hardTrimmed, 0, -1 * $endingLength - 1);
				}
			}
		}

		$hardTrimmed .= $ending;

		$hardTrimmed = trim($hardTrimmed);

		return $hardTrimmed;
	}

	/**
	* Všechny tagy BR (ve formě &lt;br> i &lt;br />) nahradí za \n (LF)
	* @param string $input
	* @return string
	*/
	static function br2nl($input) {
		return preg_replace('~<br\s*/?>~i', "\n", $input ?: '');
	}


	/**
	* Nahradí nové řádky za &lt;br />, ale nezanechá je tam.
	* @param string $input
	* @return string
	*/
	static function nl2br($input) {
		$input = str_replace("\r\n", "\n", $input ?: '');
		return str_replace("\n", "<br />", $input ?: '');
	}

	/**
	 * Nahradí entity v řetězci hodnotami ze zadaného pole.
	 * @param string $string
	 * @param array|ArrayAccess $valuesArray
	 * @param callback $escapeFunction Funkce, ktrsou se prožene každá nahrazená entita (např. kvůli escapování paznaků). Defaultně Html::escape()
	 * @param string $entityDelimiter Jeden znak
	 * @param string $entityNameChars Rozsah povolených znaků v názvech entit
	 * @return string
	 */
	static function replaceEntities($string, $valuesArray, $escapeFunction = "!!default", $entityDelimiter = "%", $entityNameChars = 'a-z0-9_-') {
		if ($escapeFunction === "!!default") {
			$escapeFunction = "\\OndraKoupil\\Tools\\Html::escape";
		}
		$arrayMode = is_array($valuesArray);
		$arrayAccessMode = (!is_array($valuesArray) and $valuesArray instanceof ArrayAccess);
		$string = \preg_replace_callback('~'.preg_quote($entityDelimiter).'(['.$entityNameChars.']+)'.preg_quote($entityDelimiter).'~i', function($found) use ($valuesArray, $escapeFunction, $arrayMode, $arrayAccessMode) {
			if ($arrayMode and key_exists($found[1], $valuesArray)) {
				$v = $valuesArray[$found[1]];
				if ($escapeFunction) {
					$v = call_user_func_array($escapeFunction, array($v));
				}
				return $v;
			}
			if ($arrayAccessMode) {
				if (isset($valuesArray[$found[1]])) {
					$v = $valuesArray[$found[1]];
					if ($escapeFunction) {
						$v = call_user_func_array($escapeFunction, array($v));
					}
					return $v;
				}
			}
			if (!$arrayAccessMode and !$arrayMode) {
				if (property_exists($valuesArray, $found[1])) {
					$v = $valuesArray->{$found[1]};
					if ($escapeFunction) {
						$v = call_user_func_array($escapeFunction, array($v));
					}
					return $v;
				}
			}

			return $found[0];

		}, $string);

		return $string;
	}

	/**
	 * Převede číslo s lidsky čitelným násobitelem, jako to zadávané v php.ini (např. 100M jako 100 mega), na normální číslo
	 * @param string $number
	 * @return number|boolean False, pokud je vstup nepřevoditelný
	 */
	static function parsePhpNumber($number) {
		$number = trim($number);

		if (is_numeric($number)) {
			return $number * 1;
		}

		if (preg_match('~^(-?)([0-9\.,]+)([kmgt]?)$~i', $number, $parts)) {
			$base = self::number($parts[2]);

			switch ($parts[3]) {
				case "K": case "k":
					$base *= 1024;
					break;

				case "M": case "m":
					$base *= 1024 * 1024;
					break;

				case "G": case "g":
					$base *= 1024 * 1024 * 1024;
					break;

				case "T": case "t":
					$base *= 1024 * 1024 * 1024 * 1024;
					break;

			}

			if ($parts[1]) {
				$c = -1;
			} else {
				$c = 1;
			}

			return $base * $c;
		}

		return false;
	}

	/**
	 * Naformátuje telefonní číslo
	 * @param string $input
	 * @param bool $international Nechat/přidat mezinárodní předvolbu?
	 * @param bool|string $spaces Přidat mezery pro trojčíslí? True = mezery. False = žádné mezery. String = zadaný řetězec použít jako mezeru.
	 * @param string $internationalPrefix Prefix pro mezinárodní řpedvolbu, používá se většinou "+" nebo "00"
	 * @param string $defaultInternational Výchozí mezinárodní předvolba (je-li $international == true a $input je bez předvolby). Zadávej BEZ prefixu.
	 * @return string
	 */

	static function phoneNumberFormatter($input, $international = true, $spaces = false, $internationalPrefix = "+", $defaultInternational = "420") {

		if (!trim($input)) {
			return "";
		}

		if ($spaces === true) {
			$spaces = " ";
		}
		$filteredInput = preg_replace('~\D~', '', $input);

		$parsedInternational = "";
		$parsedMain = "";
		if (strlen($filteredInput) > 9) {
			$parsedInternational = self::substr($filteredInput, 0, -9);
			$parsedMain = self::substr($filteredInput, -9);
		} else {
			$parsedMain = $filteredInput;
		}
		if (self::startsWith($parsedInternational, $internationalPrefix)) {
			$parsedInternational = self::substr($parsedInternational, self::strlen($internationalPrefix));
		}

		if ($spaces) {
			$spacedMain = "";
			$len = self::strlen($parsedMain);
			for ($i = $len; $i > -3; $i-=3) {
				$spacedMain = self::substr($parsedMain, ($i >= 0 ? $i : 0), ($i >= 0 ? 3 : (3 - $i * -1)))
					.($spacedMain ? ($spaces.$spacedMain) : "");
			}
		} else {
			$spacedMain = $parsedMain;
		}

		$output = "";
		if ($international) {
			if (!$parsedInternational) {
				$parsedInternational = $defaultInternational;
			}
			$output .= $internationalPrefix.$parsedInternational;
			if ($spaces) {
				$output .= $spaces;
			}
		}
		$output .= $spacedMain;

		return $output;


	}

	/**
	 * Začíná $string na $startsWith?
	 * @param string $string
	 * @param string $startsWith
	 * @param bool $caseSensitive
	 * @return bool
	 */
	static function startsWith($string, $startsWith, $caseSensitive = true) {
		$len = self::strlen($startsWith);
		if ($caseSensitive) return self::substr($string, 0, $len) == $startsWith;
		return self::strtolower(self::substr($string, 0, $len)) == self::strtolower($startsWith);
	}

	/**
	 * Končí $string na $endsWith?
	 * @param string $string
	 * @param string $endsWith
	 * @return string
	 */
	static function endsWith($string, $endsWith, $caseSensitive = true) {
		$len = self::strlen($endsWith);
		if ($caseSensitive) return self::substr($string, -1 * $len) == $endsWith;
		return self::strtolower(self::substr($string, -1 * $len)) == self::strtolower($endsWith);
	}

	/**
	* Ošetří zadanou hodnotu tak, aby z ní bylo číslo.
	* (normalizuje desetinnou čárku na tečku a ověří is_numeric).
	* @param mixed $string
	* @param int|float $default Vrátí se, pokud $vstup není čílený řetězec ani číslo (tj. je array, object, bool nebo nenumerický řetězec)
	* @param bool $positiveOnly Dáš-li true, tak se záporné číslo bude považovat za nepřijatelné a vrátí se $default (vhodné např. pro strtotime)
	* @return int|float
	*/
	static function number($string, $default = 0, $positiveOnly = false) {
		if (is_bool($string) or is_object($string) or is_array($string)) return $default;
		$string = (string)$string;
		$string=str_replace(array(","," "),array(".",""),trim($string));
		if (!is_numeric($string)) return $default;
		$string = $string * 1; // Convert to number
		if ($positiveOnly and $string<0) return $default;
		return $string;
	}

	/**
	* Funkce zlikviduje z řetězce všechno kromě číselných znaků a vybraného desetinného oddělovače.
	* @param string $string
	* @param string $decimalPoint
	* @param string $convertedDecimalPoint Takto lze normalizovat desetinný oddělovač.
	* @return string
	*/
	static function numberOnly($string, $decimalPoint = ".", $convertedDecimalPoint = ".") {
		$vystup="";
		for ($i=0;$i<strlen($string);$i++) {
			$znak=substr($string,$i,1);
			if (is_numeric($znak)) $vystup.=$znak;
			else {
				if ($znak==$decimalPoint) {
					$vystup.=$convertedDecimalPoint;
				}
			}
		}
		return $vystup;
	}

	/**
	 * Převede řetězec na základní alfanumerické znaky a pomlčky [a-z0-9.], umožní nechat tečku (vhodné pro jména souborů)
	 * <br />Alias pro webalize()
	 * @param string $string
	 * @param bool $allowDot Povolit tečku?
	 * @return string
	 */
	static function safe($string, $allowDot = true) {
		return self::webalize($string, $allowDot ? "." : "");
	}

	/**
	 * Converts to ASCII.
	 * @param  string  UTF-8 encoding
	 * @return string  ASCII
	 * @author Nette Framework
	 */
	public static function toAscii($s)
	{
		$s = preg_replace('#[^\x09\x0A\x0D\x20-\x7E\xA0-\x{2FF}\x{370}-\x{10FFFF}]#u', '', $s);
		$s = strtr($s, '`\'"^~', "\x01\x02\x03\x04\x05");
		if (ICONV_IMPL === 'glibc') {
			$s = @iconv('UTF-8', 'WINDOWS-1250//TRANSLIT', $s); // intentionally @
			$s = strtr($s, "\xa5\xa3\xbc\x8c\xa7\x8a\xaa\x8d\x8f\x8e\xaf\xb9\xb3\xbe\x9c\x9a\xba\x9d\x9f\x9e"
				. "\xbf\xc0\xc1\xc2\xc3\xc4\xc5\xc6\xc7\xc8\xc9\xca\xcb\xcc\xcd\xce\xcf\xd0\xd1\xd2\xd3"
				. "\xd4\xd5\xd6\xd7\xd8\xd9\xda\xdb\xdc\xdd\xde\xdf\xe0\xe1\xe2\xe3\xe4\xe5\xe6\xe7\xe8"
				. "\xe9\xea\xeb\xec\xed\xee\xef\xf0\xf1\xf2\xf3\xf4\xf5\xf6\xf8\xf9\xfa\xfb\xfc\xfd\xfe\x96",
				"ALLSSSSTZZZallssstzzzRAAAALCCCEEEEIIDDNNOOOOxRUUUUYTsraaaalccceeeeiiddnnooooruuuuyt-");
		} else {
			$s = @iconv('UTF-8', 'ASCII//TRANSLIT', $s); // intentionally @
		}
		$s = str_replace(array('`', "'", '"', '^', '~'), '', $s);
		return strtr($s, "\x01\x02\x03\x04\x05", '`\'"^~');
	}


	/**
	 * Převede řetězec na základní alfanumerické znaky a pomlčky [a-z0-9.-]
	 * @param string $s Řetězec, UTF-8 encoding
	 * @param string $charlist allowed characters jako regexp
	 * @param bool $lower Zmenšit na malá písmena?
	 * @return string
	 * @author Nette Framework
	 */
	public static function webalize($s, $charlist = NULL, $lower = TRUE)
	{
		$s = self::toAscii($s);
		if ($lower) {
			$s = strtolower($s);
		}
		if (!$charlist) {
			$charlist = '';
		}
		$s = preg_replace('#[^a-z0-9' . preg_quote($charlist, '#') . ']+#i', '-', $s);
		$s = trim($s, '-');
		return $s;
	}


    /**
     * Převede číselnou velikost na textové výjádření v jednotkách velikosti (KB,MB,...)
     * @param $size
     * @return string
     */
    public static function formatSize($size, $decimalPrecision = 2) {

        if ($size < 1024)                           return $size . ' B';
        elseif ($size < 1048576)                   return round($size / 1024, $decimalPrecision) . ' kB';
        elseif ($size < 1073741824)                return round($size / 1048576, $decimalPrecision) . ' MB';
        elseif ($size < 1099511627776)             return round($size / 1073741824, $decimalPrecision) . ' GB';
        elseif ($size < 1125899906842624)          return round($size / 1099511627776, $decimalPrecision) . ' TB';
        elseif ($size < 1152921504606846976)       return round($size / 1125899906842624, $decimalPrecision) . ' PB';
        else return round($size / 1152921504606846976, $decimalPrecision) . ' EB';
    }

	/**
	 * Ošetření paznaků v HTML kódu
	 *
	 * @param string $input
	 * @param bool $doubleEncode
	 *
	 * @return string
	 */
    public static function specChars($input, $doubleEncode = false) {
    	return htmlspecialchars($input, ENT_QUOTES, 'utf-8', $doubleEncode);
	}

	/**
	 * Vygeneruje náhodný alfanumerický řetězec zadané délky
	 *
	 * @param int $length
	 * @return string Skládá se z [a-zA-Z0-9] nebo [a-z0-9] při $lowercase === true
	 */
	public static function randomString($length, $lowercase = false) {

		if ($length <= 0) {
			$length = 32;
		}
		$bytesLength = ceil($length * 3/4) + 1;
		$randomBytes = openssl_random_pseudo_bytes($bytesLength);
		if (!$randomBytes) {
			$randomBytes = md5(rand(10000000,99999999));
		}
		$hex = base64_encode($randomBytes);
		$hex = preg_replace('~[/+=]~', '', $hex);
		$len = strlen($hex);
		if ($len > $length) {
			$hex = substr($hex, 0, $length);
		}
		if ($len < $length) {
			$hex .= self::randomString($length - $len);
		}
		if ($lowercase) {
			$hex = strtolower($hex);
		}
		return $hex;

	}

	/**
	 * Vygeneruje náhodný alfanumerický řetezec složený z číslic a velkých písmen, které se nemohou snadno poplést (např. 0 a O).
	 * Vhodné pro hesla nebo jednorázové kódy.
	 *
	 * @param int $length
	 *
	 * @return string
	 */
	public static function randomTypoProofCode($length) {

		$letters = array('A', 'B', 'C', 'E', 'F', 'H', 'J', 'K', 'L', 'M', 'N', 'P', 'R', 'T', 'U', 'V', 'W', 'X', 'Y', '3', '4', '6', '7', '8');
		$len = count($letters) - 1;
		$str = '';
		for ($i = 0; $i < $length; $i++) {
			$rand = mt_rand(0, $len);
			$str .= $letters[$rand];
		}
		return $str;

	}

	/**
	 * Vygeneruje náhodný číselný řetězec o zadané délce.
	 *
	 * @param int $length
	 *
	 * @return string
	 */
	public static function randomNumericCode($length) {

		$letters = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0');
		$len = count($letters) - 1;
		$str = '';
		for ($i = 0; $i < $length; $i++) {
			$rand = mt_rand(0, $len);
			$str .= $letters[$rand];
		}
		return $str;

	}


	private static $excelToNumberCache = array();

	/**
	 * Převede excelovské značení sloupců (a, b, c, ..., aa, ab, ac, ...) na zero-based (0, 1, 2, ..., 26, 27, 28, ...) číslování.
	 * @param string $excelSloupec
	 * @param bool $zeroBased
	 * @return int
	 */
	static function excelToNumber($excelSloupec, bool $zeroBased = true) {
		$excelSloupec = strtolower(trim($excelSloupec));

		if (isset(self::$excelToNumberCache[$excelSloupec])) {
			$cislo = self::$excelToNumberCache[$excelSloupec];
		} else {
			$cislo = 0;
			while ($excelSloupec) {
				$pismenko = $excelSloupec[0];
				$cislo *= 26;
				$cislo += ord($pismenko) - 96;
				$excelSloupec = substr($excelSloupec, 1);
			}
			self::$excelToNumberCache[$excelSloupec] = $cislo;
		}


		if ($zeroBased) {
			return $cislo - 1;
		} else {
			return $cislo;
		}

	}

	private static $numberToExcelCache = array();

	/**
	 * Převede zero-based (0, 1, 2, ..., 26, 27, 28, ...) číslování na excelovské značené sloupců (A, B, C...).
	 * @param int $excelNumber
	 * @param bool $zeroBased
	 * @param bool $upperCase
	 * @return string
	 * @see https://icesquare.com/wordpress/example-code-to-convert-a-number-to-excel-column-letter/
	 */
	static function numberToExcel($excelNumber, $zeroBased = true, $upperCase = true) {
		$c = intval($excelNumber);
		if ($zeroBased) {
			$c += 1;
		}

		if ($c <= 0) return '';
		
		if (isset(self::$numberToExcelCache[$c])) {
			$letter = self::$numberToExcelCache[$c];
		} else {

			$letter = '';

			while ($c !== 0){
				$p = ($c - 1) % 26;
				$c = intval(($c - $p) / 26);
				$letter = chr(65 + $p) . $letter;
			}

			self::$numberToExcelCache[$c] = $letter;
		}

		if (!$upperCase) {
			$letter = strtolower($letter);
		}

		return $letter;
	}

}
