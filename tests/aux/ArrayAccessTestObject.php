<?php


namespace OndraKoupil\Testing;

use ReturnTypeWillChange;

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

	public function offsetExists($offset): bool {
		return isset($this->data[$offset]);
	}

	#[ReturnTypeWillChange]
	public function offsetGet($offset) {
		if (isset($this->data[$offset])) {
			return $this->data[$offset];
		}
		return null;
	}

	public function offsetSet($offset, $value): void {
		if ($offset === null) {
			$this->data[] = $value;
		} else {
			$this->data[$offset] = $value;
		}
	}

	public function offsetUnset($offset): void {
		unset($this->data[$offset]);
	}

	public function count(): int {
		return count($this->data);
	}
}
