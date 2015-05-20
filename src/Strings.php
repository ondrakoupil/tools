<?php

namespace OndraKoupil\Tools;

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
	static function plural($amount, $one, $two, $five = null, $zero = null) {
		if (!$five) $five=$two;
		if (!$zero) $zero = $five;
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
		return mb_substr($input, $start, $length, "utf-8");
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
		return mb_substr($input, $start, $length, "utf-8");
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
	

}
