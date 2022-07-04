<?php

namespace OndraKoupil\Testing;

/**
 * Simple stub for testing Traversable interfaces
 *
 * Basically it contains traversable array-ish content
 * of $maxLength items, each having $item value, or if $item is true,
 * then the value is equal to the key.
 *
 * @author Ondřej Koupil koupil@optimato.cz
 */
class TraversableTestObject implements \Iterator {

	private $i = 0;
	private $maxLength;
	private $item;

	function __construct($maxLength = 5, $item = true) {
		$this->maxLength = $maxLength;
		$this->item = $item;
	}

	public function current() {
		if ($this->item === true) {
			return $this->i;
		}
		return $this->item;
	}

	public function key() {
		return $this->i;
	}

	public function next() {
		return $this->i++;
	}

	public function rewind() {
		return $this->i=0;
	}

	public function valid() {
		return $this->i < $this->maxLength;
	}
}