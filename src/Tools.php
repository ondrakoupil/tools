<?php

namespace OndraKoupil;
use \Nette\Utils\Strings;

class Tools {


	// Not used anymore
	static function dearrayize($value,$glue=", ") {
		if (is_array($value)) return implode($glue,$value);
		return $value;
	}




















	const TIME_PHP=-1;
	const TIME_INT=-2;
	const TIME_MYSQL="Y-m-d H:i:s";
	const TIME_MYSQL_DATE="Y-m-d";
	const TIME_MYSQL_TIME="H:i:s";

	/**
	 * Konverze časového údaje do různých formátů
	 * @param bool|int|string|\DateTime $input Vstupní čas. False = aktuální čas.
	 * @param int $outputFormat Konstanty TIME_*
	 * @return \DateTime|string|int
	 * @throws \InvalidArgumentException
	 */
	static function convertTime($input,$outputFormat=self::TIME_PHP) {
		if ($input===false) {
			$input=time();
		}
		if (func_num_args()==1) {
			$outputFormat=$input;
			$input=time();
		}

		if ($input instanceof \DateTime) {
			if ($outputFormat===self::TIME_INT) return $input->getTimestamp();
			if (is_string($outputFormat)) return date($outputFormat,$input->getTimestamp());
			if ($outputFormat===self::TIME_PHP) return $input;
			return null;
		}

		if (!is_numeric($input)) {
			$input=@strtotime($input);
			if ($input===false) throw new \InvalidArgumentException("Invalid input to Tools::convertTime");
		}

		if ($outputFormat===self::TIME_INT) return $input;
		if (is_string($outputFormat)) return date($outputFormat,$input);
		if ($outputFormat===self::TIME_PHP) {
			$d=new \DateTime();
			return $d->setTimestamp($input);
		}
		return null;
	}

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
	 * České vyjádření relativního času k dnešku
	 * @param mixed $time Cokoliv, co umí přijmout convertTime()
	 * @param bool $alwaysTime True = i u starých událostí přidávat čas dne
	 * @return string
	 */
	static function relativeTime($time, $alwaysTime = false) {
		$time=self::convertTime($time, self::TIME_PHP);
		$today=date("Ymd");
		if ($time->format("Ymd")==$today) {
			return "dnes v ".$time->format("H.i");
		}
		$time2=clone $time;
		if ($time2->add(new \DateInterval("P1D"))->format("Ymd")==$today) {
			return "včera v ".$time->format("H.i");
		}
		$time2=clone $time;
		if ($time2->add(new \DateInterval("P2D"))->format("Ymd")==$today) {
			return "předevčírem v ".$time->format("H.i");
		}
		if ($time->format("Y")==date("Y")) {
			return $time->format("j. n.").($alwaysTime?(" v ".$time->format("H.i")):"");
		}
		return $time->format("j. n. Y").($alwaysTime?(" v ".$time->format("H.i")):"");
	}

	static function processLinksInText($input,$stripTags=false,$blankTarget=true) {

		if ($stripTags) $input=strip_tags($input);

		//Add protocol to www links without one. We use ahttp to strip it later
		$input = preg_replace("~([^\w\/:-_]|^)(www\.[^<>,\s\)]+\.\w+)~i","\\1ahttp://\\2", $input);

		//Make classical links
		$input = preg_replace("~([a-zA-Z]{2,10}):\/\/([^<>,\s\)]+)~i","<a href=\"\\0\"".($blankTarget?" target=\"_blank\"":"").">\\0</a>", $input);

		//Strip ahttp back
		$input = str_replace("href=\"ahttp://","href=\"http://", $input);
		$input = str_replace("ahttp://","", $input);

		//E-mailové odkazy
		$input = preg_replace('~[\w\.\-]+@[^<>\s]+[\w]~',"<a href=\"mailto:\\0\">\\0</a>", $input);
		$input=trim($input);

		return $input;
	}





	/**
	 * Zkracování řetězce na požadovanou délku při zachování všech HTML tagů.
	 * Nenahrazuje konce řádků za <br />.
	 * @param string $text Řetězec ke zkrácení
	 * @param int $length Požadovaná délka výsledného řetězce (včetně ukončení)
	 * @param string $ending Ukončení, které se přilepí na konec zkráceného řetězce. TRUE = použít &amp;hellip;, tj. trojtečku.
	 * @param bool $exact False (default) ořízne s ohledem na slova. True ořízne přesně.
	 * @param bool $considerHtml TRUE = zachovat správným způsobem HTML tagy.
	 * @return string Zkrácený řetězec.
	 */
	static function shortenHtml($text, $length = 100, $ending = true, $exact = false, $considerHtml = true) {
		if ($ending===true) {
			$ending="&hellip;";
			$length+=7; //Jde o jediný znak, ne o 8 znaků
		}
		if ($considerHtml) {
			// if the plain text is shorter than the maximum length, return the whole text
			if (Strings::length(Strings::replace($text, '/<.*?>/', '')) <= $length) {
				return $text;
			}
			// splits all html-tags to scanable lines
			preg_match_all('/(<.+?>)?([^<>]*)/s', $text, $lines, PREG_SET_ORDER);
			$total_length = Strings::length($ending);
			$open_tags = array();
			$truncate = '';
			foreach ($lines as $line_matchings) {
				// if there is any html-tag in this line, handle it and add it (uncounted) to the output
				if (!empty($line_matchings[1])) {
					// if it's an "empty element" with or without xhtml-conform closing slash
					if (preg_match('/^<(\s*.+?\/\s*|\s*(img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param)(\s.+?)?)>$/is', $line_matchings[1])) {
						// do nothing
					// if tag is a closing tag
					} else if (preg_match('/^<\s*\/([^\s]+?)\s*>$/s', $line_matchings[1], $tag_matchings)) {
						// delete tag from $open_tags list
						$pos = array_search($tag_matchings[1], $open_tags);
						if ($pos !== false) {
						unset($open_tags[$pos]);
						}
					// if tag is an opening tag
					} else if (preg_match('/^<\s*([^\s>!]+).*?>$/s', $line_matchings[1], $tag_matchings)) {//<? //Toto je kvůli mému blbému editoru, který si myslí, že je ukončeno PHP
						// add tag to the beginning of $open_tags list
						array_unshift($open_tags, Strings::lower($tag_matchings[1]));
					}
					// add html-tag to $truncate'd text
					$truncate .= $line_matchings[1];
				}
				// calculate the length of the plain text part of the line; handle entities as one character
				$content_length = Strings::length(preg_replace('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|[0-9a-f]{1,6};/i', ' ', $line_matchings[2]));
				if ($total_length+$content_length> $length) {
					// the number of characters which are left
					$left = $length - $total_length;
					$entities_length = 0;
					// search for html entities
					if (preg_match_all('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|[0-9a-f]{1,6};/i', $line_matchings[2], $entities, PREG_OFFSET_CAPTURE)) {
						// calculate the real length of all entities in the legal range
						foreach ($entities[0] as $entity) {
							if ($entity[1]+1-$entities_length <= $left) {
								$left--;
								$entities_length += Strings::length($entity[0]);
							} else {
								// no more characters left
								break;
							}
						}
					}
					$truncate .= Strings::substring($line_matchings[2], 0, $left+$entities_length);
					// maximum lenght is reached, so get off the loop
					break;
				} else {
					$truncate .= $line_matchings[2];
					$total_length += $content_length;
				}
				// if the maximum length is reached, get off the loop
				if($total_length>= $length) {
					break;
				}
			}
		} else {
			if (Strings::length($text) <= $length) {
				return $text;
			} else {
				$truncate = Strings::substring($text, 0, $length - Strings::length($ending));
			}
		}
		// if the words shouldn't be cut in the middle...
		if (!$exact) {
			// ...search the last occurance of a space...
			$spacepos = strrpos($truncate, ' ');
			if (isset($spacepos)) {
				// ...and cut the text in this position
				$truncate = substr($truncate, 0, $spacepos);
			}
		}
		// add the defined ending to the text
		$truncate .= $ending;
		if($considerHtml) {
			// close all unclosed html-tags
			foreach ($open_tags as $tag) {
				$truncate .= '</' . $tag . '>';
			}
		}
		return $truncate;
	}

}
