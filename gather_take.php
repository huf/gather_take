<?php

class gather_take extends Exception {
	static $store = array();
	static $i = -1;
	public static function gather($args) {
		$f = $args[0];
		self::$i++;
		self::$store[self::$i] = array();
		try {
			call_user_func_array($f, $args);
		}
		catch (gather_take $e) {
			// nothing
		}
		catch (Exception $e) {
			self::$i--;
			array_pop(self::$store);
			throw $e;
		}
		self::$i--;
		return array_pop(self::$store);
	}
	public static function take($v) {
		array_push(self::$store[self::$i], $v);
	}
	public static function takev($vv) {
		self::$store[self::$i] = array_merge(self::$store[self::$i], $vv);
	}
	public static function last() {
		throw new self;
	}
	public static function gathered() {
		return count(self::$store[self::$i]);
	}
}

function gather($f) {
	$args = func_get_args();
	return gather_take::gather($args);
}
function take($v) {
	gather_take::take($v);
}
function takev($vv) {
	gather_take::takev($vv);
}
function last() { 
	gather_take::last();
}
function gathered() {
	return gather_take::gathered();
}
