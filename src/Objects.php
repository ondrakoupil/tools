<?php

namespace OndraKoupil\Tools;

use Closure;
use Exception;
use InvalidArgumentException;
use ReflectionClass;

class Objects {

	/**
	 * Vytvoří objekt určité třídy ze zadání v podobě stringu (třídy), callable (factory) nebo rovnou z hotového objektu.
	 *
	 * @param string|object|callable|array $source String = jméno třídy. Objekt = rovnou daný objekt. Callable = funkce, co musí vrátit požadovaný objekt.
	 * @param string $requiredClass Název třídy nebo interface, který musí vrácený objekt mít.
	 * @param array $arguments Poel argumentů pro konstruktor nebo callback $source
	 *
	 * @return object
	 */
	public static function createFrom($source, $requiredClass = null, $arguments = array()) {

		// $source je objekt
		if (is_object($source) and !($source instanceof Closure)) {
			if ($requiredClass) {
				if ($source instanceof $requiredClass) {
					return $source;
				} else {
					throw new Exception('Given object of class ' . get_class($source) . ' is not of type ' . $requiredClass);
				}
			}
			return $source;
		}

		// $source je string
		if (is_string($source)) {
			if (class_exists($source)) {
				$reflector = new ReflectionClass($source);
				$obj = $reflector->newInstanceArgs($arguments);
				if ($requiredClass) {
					if ($obj instanceof $requiredClass) {
						return $obj;
					} else {
						throw new Exception('Given string "' . $source . '" is not class name of type ' . $requiredClass);
					}
				}
				return $obj;
			}
		}

		if (is_callable($source)) {
			$obj = call_user_func_array($source, $arguments);
			if (!$obj or !is_object($obj)) {
				throw new Exception('Given function did not return an object.');
			}
			if ($requiredClass) {
				if ($obj instanceof $requiredClass) {
					return $obj;
				} else {
					throw new Exception('Given function returned object of class ' . get_class($obj) . ' that is not of type ' . $requiredClass);
				}
			}
			return $obj;
		}

		throw new Exception('Argument must be an object, class name or callable.');

	}

	public static function createFromArray($sourceArray, $className, $constructorArgs = array()) {
		$reflector = new ReflectionClass($className);
		$obj = $reflector->newInstanceArgs($constructorArgs);

		foreach (get_object_vars($obj) as $propName => $propValue) {
			if (array_key_exists($propName, $sourceArray)) {
				$obj->$propName = $sourceArray[$propName];
			}
		}

		return $obj;
	}

	public static function extractWithKeyPath($source, $keyPath, $separatorInKeyPath = '.') {
		$keyPathSplit = explode($separatorInKeyPath, $keyPath);
		return self::extractWithKeyPathStep($source, $keyPathSplit, 0);
	}

	protected static function extractWithKeyPathStep($sourceOfThisStep, $keyPathSplit, $keyPathPosition) {

		if ($keyPathPosition >= 20) {
			throw new Exception('Max recursion reached.');
		}

		if (!$keyPathSplit or count($keyPathSplit) < $keyPathPosition + 1) {
			return $sourceOfThisStep;
		}

		$currentKeyPathPart = $keyPathSplit[$keyPathPosition];
		$keyPathToThisPlace = array_slice($keyPathSplit, 0, $keyPathPosition + 1);

		$arrayMode = false;
		if (is_array($sourceOfThisStep)) {
			$arrayMode = true;
		} elseif (!is_object($sourceOfThisStep)) {
			throw new InvalidArgumentException(implode('.', $keyPathToThisPlace) . ' is not an array or object in source data');
		}

		if ($arrayMode) {
			if (!array_key_exists($currentKeyPathPart, $sourceOfThisStep)) {
				throw new Exception(implode('.', $keyPathToThisPlace) . ' was not found in source array.');
			}
		} else {
			if (!property_exists($sourceOfThisStep, $currentKeyPathPart)) {
				throw new Exception(implode('.', $keyPathToThisPlace) . ' was not found in source object.');
			}
		}

		if ($arrayMode) {
			$nextSource = $sourceOfThisStep[$currentKeyPathPart];
			return self::extractWithKeyPathStep($nextSource, $keyPathSplit, $keyPathPosition + 1);
		} else {
			$nextSource = $sourceOfThisStep->$currentKeyPathPart;
			return self::extractWithKeyPathStep($nextSource, $keyPathSplit, $keyPathPosition + 1);
		}

	}

}
