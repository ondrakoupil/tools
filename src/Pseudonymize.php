<?php

namespace OndraKoupil\Tools;

class Pseudonymize {

	static function string($str, $maskChar = '*') {
		$str = trim(strip_tags($str));
		$len = mb_strlen($str);


		if ($len < 2) {
			$outStr = str_repeat($maskChar, $len);
		}
		elseif ($len < 5) {
			$outStr = mb_substr($str, 0, 1) . str_repeat($maskChar, $len - 1);
		}
		elseif ($len < 9) {
			$outStr = mb_substr($str, 0, 1) . str_repeat($maskChar, $len - 2) . mb_substr($str, -1);
		}
		else {
			$outStr = mb_substr($str, 0, 2) . str_repeat($maskChar, 5) . mb_substr($str, -2);
		}

		return $outStr;
	}

	static function email($str, $maskChar = '*') {

		$parts = explode('@', $str);

		if (count($parts) != 2) {
			return self::string($str, $maskChar);
		}

		$username = $parts[0];
		$username = self::string($username, $maskChar);

		$domainParts = explode('.', $parts[1]);
		$domainPartsLen = count($domainParts);
		$tld = $domainParts[$domainPartsLen - 1];
		$tld = self::string($tld, $maskChar);

		$domain = implode('.', array_slice($domainParts, 0, -1));
		$domain = self::string($domain, $maskChar);

		return $username . '@' . $domain . '.' . $tld;


	}

	static function ip($str, $maskChar = '*') {
		$parts = explode('.', trim($str));
		$len = count($parts);
		if ($len == 4) {
			$parts[$len - 1] = str_repeat($maskChar, 3);
			return implode('.', $parts);
		}
		return self::string($str, $maskChar);
	}

	static function phone($str, $maskChar = '*') {
		$str = preg_replace('/\s/', '', $str);
		$len = mb_strlen($str);
		if ($len > 9) {
			$formatted = Strings::phoneNumberFormatter($str, true, false);
			return mb_substr($formatted, 0, 7) . self::string(mb_substr($formatted, 7));
		}
		return self::string($str, $maskChar);
	}


}
