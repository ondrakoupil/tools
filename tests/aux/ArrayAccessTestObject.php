<?php


namespace OndraKoupil\Testing;

/**
 * Simple stub for testing ArrayAccess interfaces
 *
 * @author Ondřej Koupil koupil@optimato.cz
 */
class ArrayAccessTestObject implements \ArrayAccess, \Countable {
	private $data;

	function __construct($initialData = array()) {
		$this->data = $initialData;
	}

	function getData() {
		return $this->data;
	}

	public function offsetExists($offset) {
		return isset($this->data[$offset]);
	}

	public function offsetGet($offset) {
		if (isset($this->data[$offset])) {
			return $this->data[$offset];
		}
		return null;
	}

	public function offsetSet($offset, $value) {
		if ($offset === null) {
			$this->data[] = $value;
		} else {
			$this->data[$offset] = $value;
		}
	}

	public function offsetUnset($offset) {
		unset($this->data[$offset]);
	}

	public function count() {
		return count($this->data);
	}
}
