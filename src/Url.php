<?php

namespace OndraKoupil\Tools;

class Url {

	/**
	* Pomůcka pro sestavení adresy s GET parametry.
	* @param array $params Asociativní pole [název-parametru] => hodnota-parametru. Null pro odstranění daného parametru, byl-li tam.
	* Hodnota může být array, pak se automaticky převede na několik samostatných parametrů ve tvaru název-parametru[]
	* @param string $originalUrl Původní adresa
	* @param bool $clear True = všechny GET parametry z $original URL vyhodit a pouze přidávat ty z $params
	* @param bool $clearArrays Jak se má chovat, když v $originalUrl je nějaký parametr - pole (s [] na konci) a v $params také.
	* False = sloučit obě pole. True = smazat to z $originalUrl a nechat jen hodnoty z $params.
	* @param array $keepEntities Array entit, které se nemají enkódovat (např. %id%)
	* @return string
	*/
	static function builder($params, $originalUrl, $clear=false, $clearArrays=true, $keepEntities = array()) {

		$origParams=array();
		if (!$clear) {
			$query=parse_url($originalUrl,PHP_URL_QUERY);
			if ($query) {
				parse_str($query, $origParams);
			}
			if (!$origParams) {
				$origParams=array();
			}
		}

		if ($clearArrays) {
			$finalParams=array_merge($origParams,$params);
		} else {
			$finalParams=array_replace_recursive($origParams,$params);
		}

		foreach($finalParams as $i=>$r) {
			if ($r===null) unset($finalParams[$i]);
		}

		$casti=array();
		foreach($finalParams as $i=>$p) {
			if (is_array($p)) {
				foreach($p as $pi=>$pp) {
					$casti[]=$i."[".urlencode($pi)."]=".urlencode($pp);
				}
			} else {
				$casti[]=$i."=".urlencode($p);
			}
		}

		if (Strings::strpos($originalUrl,"?")!==false) {
			$zaklad=Strings::substr($originalUrl,0,Strings::strpos($originalUrl,"?"));
		} else {
			$zaklad=$originalUrl;
		}

		$vrat=$zaklad;
		if ($casti) $vrat.="?".implode("&",$casti);

		if ($keepEntities) {
			foreach(Arrays::arrayize($keepEntities) as $ent) {
				$entEncoded = urlencode($ent);
				$vrat = str_replace($entEncoded, $ent, $vrat);
			}
		}

		return $vrat;
	}

	static function absolutize($url, $baseUrl) {
		$url = self::absolutizeStep($url, "href", $baseUrl);
		$url = self::absolutizeStep($url, "src", $baseUrl);
		return $url;
	}


	/**
	* @ignore
	*/
	static protected  function absolutizeStep($vstup, $atribut, $predadresa) {
		$poz=0;
		$kontrola=0;
		while ($poz!==false and $poz<Strings::strlen($vstup) and $kontrola<100) {
			$kontrola++;
			$poz=Strings::strpos($vstup," $atribut=",$poz+1);
			if ($poz===false) break;
			$poz+=Strings::strlen($atribut)+2;
			$poz_hodnoty=$poz;

			if (Strings::substr($vstup,$poz_hodnoty,1)=="\"" or Strings::substr($vstup,$poz_hodnoty,1)=="'") $poz_hodnoty++;
			if (Strings::substr($vstup,$poz_hodnoty,7)=="http://" or Strings::substr($vstup,$poz_hodnoty,7)=="mailto:" or Strings::substr($vstup,$poz_hodnoty,6)=="ftp://" or Strings::substr($vstup,$poz_hodnoty,8)=="https://") continue;
			if (Strings::substr($vstup,$poz_hodnoty,1)=="%") continue;
			$vstup=Strings::substr($vstup,0,$poz_hodnoty).$predadresa.Strings::substr($vstup,$poz_hodnoty);
			$poz=$poz_hodnoty+Strings::strlen($predadresa);
		}
		return $vstup;

	}

}
