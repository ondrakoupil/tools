<?php

namespace OndraKoupil;
use \Nette\Utils\Strings;

class Tools {

	static function arrayize($value) {
		if (is_array($value)) return $value;
		if (is_bool($value) or $value===null) return array();
		if ($value instanceof \Traversable or $value instanceof \ArrayAccess) return $value;
		return array(0=>$value);
	}

	static function dearrayize($value,$glue=", ") {
		if (is_array($value)) return implode($glue,$value);
		return $value;
	}

	/**
	 * Transformace dvoj(či více)-rozměrných polí či Traversable objektů
	 * @param array $input Vstupní pole.
	 * @param mixed $outputKeys Jak mají být tvořeny indexy výstupního pole?
	 * <br />False = numericky indexovat od 0.
	 * <br />True = zachovat původní indexy.
	 * <br />Cokoliv jiného - použít takto pojmenovanou hodnotu z druhého rozměru
	 * @param mixed $outputValue Jak mají být tvořeny hodnoty výstupního pole?
	 * <br />True = zachovat původní položky
	 * <br />String nebo array = vybrat pouze takto pojmenovanou položku nebo položky.
	 * <br />False = původní index. Může být zadán i jako prvek pole, pak bude daný prvek mít index [key].
	 * @return mixed
	 */
	static function arrayTransform($input,$outputKeys,$outputValue) {
		$input=self::arrayize($input);
		$output=array();
		foreach($input as $inputI=>$inputR) {
			if (is_array($outputValue)) {
				$novaPolozka=array();
				foreach($outputValue as $ov) {
					if ($ov===false) {
						$novaPolozka["key"]=$inputI;
					} else {
						if (isset($inputR[$ov])) {
							$novaPolozka[$ov]=$inputR[$ov];
						} else {
							$novaPolozka[$ov]=null;
						}
					}
				}
			} else {
				if ($outputValue===true) {
					$novaPolozka=$inputR;
				} elseif ($outputValue===false) {
					$novaPolozka=$inputI;
				} elseif (isset($inputR[$outputValue])) {
					$novaPolozka=$inputR[$outputValue];
				} else {
					$novaPolozka=null;
				}
			}


			if ($outputKeys===false) {
				$output[]=$novaPolozka;
			} elseif ($outputKeys===true) {
				$output[$inputI]=$novaPolozka;
			} else {
				if (isset($inputR[$outputKeys])) {
					$output[$inputR[$outputKeys]]=$novaPolozka;
				} else {
					$output[]=$novaPolozka;
				}
			}
		}
		return $output;
	}

	/**
	 * Seřadí prvky v jednom poli dle klíčů podle pořadí hodnot v jiném poli
	 * @param array $dataArray
	 * @param array $keysArray
	 * @return null
	 */
	static function arraySortByExternalKeys($dataArray, $keysArray) {
		$returnArray = array();
		foreach($keysArray as $k) {
			if (isset($dataArray[$k])) {
				$returnArray[$k] = $dataArray[$k];
			} else {
				$returnArray[$k] = null;
			}
		}
		return $returnArray;
	}

	/**
	* Vybere všechny možné hodnoty z dvourozměrného asociativního pole či Traversable objektu.
	* Funkce iteruje po prvním rozměru pole $array a ve druhém rozměru hledá $hodnota. Ve druhém rozměru
	* mohou být jak pole, tak objekty.
	* Vrátí všechny různé nalezené hodnoty (bez duplikátů).
	* @param array $array
	* @param string $hodnota Index nebo jméno hodnoty, který chceme získat
	* @param array $ignoredValues Volitelně lze doplnit hodnoty, které mají být ignorovány (pro porovnávání se
	 * používá striktní === ekvivalence)
	* @return array
	*/
	static function arrayValuePicker($array, $hodnota, $ignoredValues = null) {
		$vrat=array();
		foreach($array as $a) {
			if ((is_array($a) or ($a instanceof \ArrayAccess)) and isset($a[$hodnota])) {
				$vrat[]=$a[$hodnota];
			} elseif (is_object($a) and isset($a->$hodnota)) {
				$vrat[]=$a->$hodnota;
			}
		}
		$vrat=array_values(array_unique($vrat));

		if ($ignoredValues) {
			$ignoredValues = self::arrayize($ignoredValues);
			foreach($vrat as $i=>$r) {
				if (in_array($r, $ignoredValues, true)) unset($vrat[$i]);
			}
			$vrat = array_values($vrat);
		}

		return $vrat;
	}

	/**
	 * Ze zadaného pole vybere jen ty položky, které mají klíč udaný v druhém poli.
	 * @param array|\ArrayAccess $array Asociativní pole
	 * @param array $requiredKeys Obyčejné pole klíčů
	 * @return array
	 */
	static function arrayFilterByKeys($array, $requiredKeys) {
		if (is_array($array)) {
			return array_intersect_key($array, array_fill_keys($requiredKeys, true));
		}
		if ($array instanceof \ArrayAccess) {
			$ret = array();
			foreach ($requiredKeys as $k) {
				if (isset($array[$k])) {
					$ret[$k] = $array[$k];
				}
			}
			return $ret;
		}

		throw new \InvalidArgumentException("Argument must be an array or object with ArrayAccess");
	}

	/**
	 * Z klasického dvojrozměrného pole udělá trojrozměrné pole, kde první index bude sdružovat řádku dle nějaké z hodnot.
	 * @param array $data
	 * @param string $groupBy Název políčka v $data, podle něhož se má sdružovat
	 * @param bool|string $orderByKey False (def.) = nechat, jak to přišlo pod ruku. True = seřadit dle sdružované hodnoty. String "desc" = sestupně.
	 * @return array
	 */
	static public function arrayGroup($data,$groupBy,$orderByKey=false) {
		$vrat=array();
		foreach($data as $index=>$radek) {
			if (!isset($radek[$groupBy])) {
				$radek[$groupBy]="0";
			}
			if (!isset($vrat[$radek[$groupBy]])) {
				$vrat[$radek[$groupBy]]=array();
			}
			$vrat[$radek[$groupBy]][$index]=$radek;
		}
		if ($orderByKey) {
			ksort($vrat);
		}
		if ($orderByKey==="desc") {
			$vrat=array_reverse($vrat);
		}
		return $vrat;
	}

	/**
	 * Zruší z pole všechny výskyty určité hodnoty.
	 * @param array $dataArray
	 * @param mixed $valueToDelete Nesmí být null!
	 * @param bool $keysInsignificant True = přečíslovat vrácené pole, indexy nejsou podstatné. False = nechat původní indexy.
	 * @param bool $strict == nebo ===
	 * @return array Upravené $dataArray
	 */
	static public function arrayDeleteValue($dataArray, $valueToDelete, $keysInsignificant = true, $strict = false) {
		if ($valueToDelete === null) throw new \InvalidArgumentException("\$valueToDelete cannot be null.");
		$keys = array_keys($dataArray, $valueToDelete, $strict);
		if ($keys) {
			foreach($keys as $k) {
				unset($dataArray[$k]);
			}
			if ($keysInsignificant) {
				$dataArray = array_values($dataArray);
			}
		}
		return $dataArray;
	}

	/**
	 * Zruší z jednoho pole všechny hodnoty, které se vyskytují ve druhém poli.
	 * Ve druhém poli musí jít o skalární typy, objekty nebo array povedou k chybě.
	 * @param array $dataArray
	 * @param array $arrayOfValuesToDelete
	 * @param bool $keysInsignificant True = přečíslovat vrácené pole, indexy nejsou podstatné. False = nechat původní indexy.
	 * @return array Upravené $dataArray
	 */
	static public function arrayDeleteValues($dataArray, $arrayOfValuesToDelete, $keysInsignificant = true) {
		$arrayOfValuesToDelete = self::arrayize($arrayOfValuesToDelete);
		$invertedDeletes = array_fill_keys($arrayOfValuesToDelete, true);
		foreach ($dataArray as $i=>$r) {
			if (isset($invertedDeletes[$r])) {
				unset($dataArray[$i]);
			}
		}
		if ($keysInsignificant) {
			$dataArray = array_values($dataArray);
		}

		return $dataArray;
	}


	/**
	 * Obohatí $mainArray o nějaké prvky z $mixinArray. Obě pole by měla být dvourozměrná pole, kde
	 * první rozměr je ID a další rozměr je asociativní pole s nějakými vlastnostmi.
	 * <br />Data z $mainArray se považují za prioritnější a správnější, a pokud již příslušný prvek obsahují,
	 * nepřepíší se tím z $mixinArray.
	 * @param array $mainArray
	 * @param array $mixinArray
	 * @param bool|array|string $fields True = obohatit vším, co v $mixinArray je. Jinak string/array stringů.
	 * @param array $changeIndexes Do $mainField lze použít jiné indexy, než v originále. Sem zadej "překladovou tabulku" ve tvaru array([original_key] => new_key).
	 * Ve $fields používej již indexy po přejmenování.
	 * @return array Obohacené $mainArray
	 */
	static public function arrayEnrich($mainArray, $mixinArray, $fields=true, $changeIndexes = array()) {
		if ($fields!==true) $fields=self::arrayize($fields);
		foreach($mixinArray as $mixinId=>$mixinData) {
			if (!isset($mainArray[$mixinId])) continue;
			if ($changeIndexes) {
				foreach($changeIndexes as $fromI=>$toI) {
					if (isset($mixinData[$fromI])) {
						$mixinData[$toI] = $mixinData[$fromI];
						unset($mixinData[$fromI]);
					} else {
						$mixinData[$toI] = null;
					}
				}
			}
			if ($fields===true) {
				$mainArray[$mixinId]+=$mixinData;
			} else {
				foreach($fields as $field) {
					if (!isset($mainArray[$mixinId][$field])) {
						if (isset($mixinData[$field])) {
							$mainArray[$mixinId][$field]=$mixinData[$field];
						} else {
							$mainArray[$mixinId][$field]=null;
						}
					}
				}
			}
		}
		return $mainArray;
	}

	/**
	 * Konverze asociativního pole na objekt
	 * @param array|Traversable $array
	 * @return \stdClass
	 */
	static function arrayToObject($array) {
		if (!is_array($array) and !($array instanceof \Traversable)) {
			throw new \InvalidArgumentException("You must give me an array!");
		}
		$obj = new \stdClass();
		foreach ($array as $i=>$r) {
			$obj->$i = $r;
		}
		return $obj;
	}

	/**
	 * Z dvourozměrného pole, které bylo sgrupované podle nějaké hodnoty, udělá zpět jednorozměrné, s výčtem jednotlivých hodnot.
	 * Funguje pouze za předpokladu, že jednotlivé hodnoty jsou obyčejné skalární typy. Objekty nebo array třetího rozměru povede k chybě.
	 * @param array $array
	 * @return array
	 */
	static public function arrayFlatten($array) {
		$out=array();
		foreach($array as $i=>$subArray) {
			foreach($subArray as $value) {
				$out[$value]=true;
			}
		}
		return array_keys($out);
	}

	/**
	 * Normalizuje hodnoty v poli do rozsahu <0-1>
	 * @param array $array
	 * @return array
	 */
	static public function arrayNormaliseValues($array) {
		$array=self::arrayize($array);
		if (!$array) return $array;
		$minValue=min($array);
		$maxValue=max($array);
		if ($maxValue==$minValue) {
			$minValue-=1;
		}
		foreach($array as $index=>$value) {
			$array[$index]=($value-$minValue)/($maxValue-$minValue);
		}
		return $array;
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
	 * Rekurzivně převede traversable objekt na obyčejné array.
	 * @param \Traversable $traversable
	 * @param int $depth Interní, pro kontorlu nekonečné rekurze
	 * @return array
	 * @throws \RuntimeException
	 */
	static function traversableToArray($traversable, $depth = 0) {
		$vrat = array();
		if ($depth > 10) throw new \RuntimeException("Recursion is too deep.");
		if (!is_array($traversable) and !($traversable instanceof \Traversable)) {
			throw new \InvalidArgumentException("\$traversable must be an array or Traversable object.");
		}
		foreach ($traversable as $i=>$r) {
			if (is_array($r) or ($r instanceof \Traversable)) {
				$vrat[$i] = self::traversableToArray($r, $depth + 1);
			} else {
				$vrat[$i] = $r;
			}
		}
		return $vrat;
	}

	/**
	* Pomocná funkce zjednodušující práci s různými číselníky definovanými jako array v PHP. Umožňuje buď "lidsky" zformátovat jeden vybraný prvek z číselníku, nebo vrátit celé array hodnot.
	* @param array $data Celé array se všemi položkami ve tvaru [index]=>$value
	* @param string|int|bool $index False = vrať array se všemi. Jinak zadej index jedné konkrétní položky.
	* @param string|bool $pattern False = vrať tak, jak to je v $data. String = naformátuj. Entity %index%, %value%, %i%. %i% označuje pořadí a vyplňuje se jen je-li $index false a je 0-based.
	* @param string|int $default Pokud by snad v $data nebyla položka s indexem $indexPolozky, hledej index $default, pokud není, vrať $default.
	* @param bool $reverse dej True, má-li se vrátit v opačném pořadí.
	* @return array|string Array pokud $indexPolozky je false, jinak string.
	*/
	static function enumItem ($data,$index,$pattern=false,$default=0,$reverse=false) {
		if ($index!==false) {
			if (!isset($data[$index])) {
				$index=$default;
				if (!isset($data[$index])) return $default;
			}
			if ($pattern===false) return $data[$index];
			return self::enumItemPattern($pattern,$index,$data[$index],"");
		}

		if ($pattern===false) {
			if ($reverse) return array_reverse($data,true);
			return $data;
		}

		$vrat=array();
		$i=0;
		foreach($data as $di=>$dr) {
			$vrat[$di]=self::enumItemPattern($pattern,$di,$dr,$i);
			$i++;
		}
		if ($reverse) return array_reverse($vrat,true);
		return $vrat;
	}

	/**
	* @ignore
	*/
	protected static function enumItemPattern($pattern,$index,$value,$i) {
		return str_replace(
			array("%index%","%i%","%value%"),
			array($index,$i,$value),
			$pattern
		);
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
