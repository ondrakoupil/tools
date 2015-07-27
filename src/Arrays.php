<?php

namespace OndraKoupil\Tools;

class Arrays {

	/**
	 * Zajistí, aby zadaný argument byl array.
	 *
	 * Převede booly nebo nully na array(), pole nechá být, ArrayAccess a Traversable
	 * také, vše ostatní převede na array(0=>$hodnota)
	 *
	 * @param mixed $value
	 * @param bool $forceArrayFromObject True = Traversable objekty také převádět na array
	 * @return array|\ArrayAccess|\Traversable
	 */
	static function arrayize($value, $forceArrayFromObject = false) {
		if (is_array($value)) return $value;
		if (is_bool($value) or $value===null) return array();
		if ($value instanceof \Traversable) {
			if ($forceArrayFromObject) {
				return iterator_to_array($value);
			}
			return $value;
		}
		if ($value instanceof \ArrayAccess) {
			return $value;
		}
		return array(0=>$value);
	}

	/**
	 * Pokud je pole, převede na řetězec, jinak nechá být
	 * @param array|mixed $value
	 * @param string $glue
	 * @return mixed
	 */
	static function dearrayize($value,$glue=",") {
		if (is_array($value)) return implode($glue, $value);
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
	static function transform($input,$outputKeys,$outputValue) {
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
	static function sortByExternalKeys($dataArray, $keysArray) {
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
	static function valuePicker($array, $hodnota, $ignoredValues = null) {
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
	static function filterByKeys($array, $requiredKeys) {
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
	static public function group($data,$groupBy,$orderByKey=false) {
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
	static public function deleteValue($dataArray, $valueToDelete, $keysInsignificant = true, $strict = false) {
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
	static public function deleteValues($dataArray, $arrayOfValuesToDelete, $keysInsignificant = true) {
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
	static public function enrich($mainArray, $mixinArray, $fields=true, $changeIndexes = array()) {
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
	 * Konverze asociativního pole na objekt třídy stdClass
	 * @param array|Traversable $array
	 * @return \stdClass
	 */
	static function toObject($array) {
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
	static public function flatten($array) {
		$out=array();
		foreach($array as $i=>$subArray) {
			foreach($subArray as $value) {
				$out[$value]=true;
			}
		}
		return array_keys($out);
	}


	/**
	 * Normalizuje hodnoty v poli do rozsahu &lt;0-1&gt;
	 * @param array $array
	 * @return array
	 */
	static public function normaliseValues($array) {
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
	 * Porovná, zda jsou hodnoty ve dvou polích stejné. Nezáleží na indexech ani na pořadí prvků v poli.
	 * @param array $array1
	 * @param array $array2
	 * @param bool $strict Používat ===
	 * @return boolean True = stejné. False = rozdílné.
	 */
	static function compareValues($array1, $array2, $strict = false) {
		if (count($array1) != count($array2)) return false;

		$array1 = array_values($array1);
		$array2 = array_values($array2);
		sort($array1, SORT_STRING);
		sort($array2, SORT_STRING);

		foreach($array1 as $i=>$r) {
			if ($array2[$i] != $r) return false;
			if ($strict and $array2[$i] !== $r) return false;
		}

		return true;
	}

	/**
	* Rekurzivní změna kódování libovolného typu proměnné (array, string, atd., kromě objektů).
	* @param string $from Vstupní kódování
	* @param string $to Výstupní kódování
	* @param mixed $array Co překódovat
	* @param bool $keys Mají se iconvovat i klíče? Def. false.
	* @param int $checkDepth Tento parametr ignoruj, používá se jako pojistka proti nekonečné rekurzi.
	* @return mixed
	*/
	static function iconv($from, $to, $array, $keys=false, $checkDepth = 0) {
		if (is_object($array)) {
			return $array;
		}
		if (!is_array($array)) {
			if (is_string($array)) {
				return iconv($from,$to,$array);
			} else {
				return $array;
			}
		}
		if ($checkDepth>20) return $array;
		$vrat=array();
		foreach($array as $i=>$r) {
			if ($keys) {
				$i=iconv($from,$to,$i);
			}
			$vrat[$i]=self::iconv($from,$to,$r,$keys,$checkDepth+1);
		}
		return $vrat;
	}

	/**
	 * Vytvoří kartézský součin.
	 * <code>
	 * $input = array(
	 *		"barva" => array("red", "green"),
	 *		"size" => array("small", "big")
	 * );
	 *
	 * $output = array(
	 *		[0] => array("barva" => "red", "size" => "small"),
	 *		[1] => array("barva" => "green", "size" => "small"),
	 *		[2] => array("barva" => "red", "size" => "big"),
	 *		[3] => array("barva" => "green", "size" => "big")
	 * );
	 *
	 * </code>
	 * @param array $input
	 * @return array
	 * @see http://stackoverflow.com/questions/6311779/finding-cartesian-product-with-php-associative-arrays
	 */
	static function cartesian($input) {
		$input = array_filter($input);

		$result = array(array());

		foreach ($input as $key => $values) {
			$append = array();

			foreach($result as $product) {
				foreach($values as $item) {
					$product[$key] = $item;
					$append[] = $product;
				}
			}

			$result = $append;
		}

		return $result;
	}

    /**
     * Zjistí, zda má pole pouze číselné indexy
     * @param array $array
     * @return bool
	 * @author Michael Pavlista
     */
    public static function isNumeric(array $array) {

        return empty($array) ? TRUE : is_numeric(implode('', array_keys($array)));
    }


    /**
     * Zjistí, zda je pole asociativní
     * @param array $array
     * @return bool
	 * @author Michael Pavlista
     */
    public static function isAssoc(array $array) {

        return empty($array) ? TRUE : !self::isNumeric($array);
    }

	/**
	 * @param array $old
	 * @param array $new
	 * @return array
	 *
	 * @author Paul's Simple Diff Algorithm v 0.1
	 * (C) Paul Butler 2007 <http://www.paulbutler.org/>
     * May be used and distributed under the zlib/libpng license.
	 */
	public static function diff($old, $new) {
		$matrix = array();
		$maxlen = 0;
		foreach($old as $oindex => $ovalue){
			$nkeys = array_keys($new, $ovalue);
			foreach($nkeys as $nindex){
				$matrix[$oindex][$nindex] = isset($matrix[$oindex - 1][$nindex - 1]) ?
					$matrix[$oindex - 1][$nindex - 1] + 1 : 1;
				if($matrix[$oindex][$nindex] > $maxlen){
					$maxlen = $matrix[$oindex][$nindex];
					$omax = $oindex + 1 - $maxlen;
					$nmax = $nindex + 1 - $maxlen;
				}
			}
		}
		if($maxlen == 0) return array(array('d'=>$old, 'i'=>$new));
		return array_merge(
			self::diff(array_slice($old, 0, $omax), array_slice($new, 0, $nmax)),
			array_slice($new, $nmax, $maxlen),
			self::diff(array_slice($old, $omax + $maxlen), array_slice($new, $nmax + $maxlen)));
	}
}
