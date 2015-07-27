<?php

namespace OndraKoupil\Tools;

class Html {

	/**
	 * Z plaintextu udělá HTML kód tím, že zaktivní odkazy
	 * @param type $input
	 * @param type $stripTags
	 * @param type $blankTarget
	 * @return type
	 */
	static function processLinksInText($input, $stripTags=false, $blankTarget=true) {

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
			if (Strings::length(preg_replace('/<.*?>/', '', $text)) <= $length) {
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

	/**
	 * Ošetření nebezpečných znaků pro utf-8, ve výchozím stavu nedělá doubleencode
	 * @param string $input
	 * @param bool $doubleEncode
	 * @return string
	 */
	static function escape($input, $doubleEncode = false) {
		return htmlentities($input, ENT_QUOTES, "utf-8", $doubleEncode);
	}

	/**
	 * Jednoduché zvýraznění změn v řetězci. Pracuje s přesností na jednotlivá slova.
	 *
	 * @param string $old
	 * @param string $new
	 * @param string $startIns
	 * @param string $endIns
	 * @param string $startDel
	 * @param string $endDel
	 * @return string
	 *
	 * @author Paul's Simple Diff Algorithm v 0.1
	 * (C) Paul Butler 2007 <http://www.paulbutler.org/>
     * May be used and distributed under the zlib/libpng license.
	 */
	public static function diff($old, $new, $startIns = "<ins>", $endIns = "</ins>", $startDel = "<del>", $endDel = "</del>") {
		$ret = '';
		$diff = Arrays::diff(preg_split("/[\s]+/", $old), preg_split("/[\s]+/", $new));
		foreach($diff as $k){
			if(is_array($k))
				$ret .= (!empty($k['d'])?$startDel.implode(' ',$k['d']).$endDel." ":'').
					(!empty($k['i'])?$startIns.implode(' ',$k['i']).$endIns." ":'');
			else $ret .= $k . ' ';
		}
		return $ret;
	}


}
