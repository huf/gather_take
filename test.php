<?php

function is_deeply($result, $should, $msg) {
	if (count($result) != count($should)) {
		return $msg;
	}
	$l = count($result);
	for ($i = 0; $i < $l; ++$i) {
		if (!array_key_exists($i, $result)
			|| !array_key_exists($i, $should))
		{
			return $msg;
		}
		if ($result[$i] !== $should[$i]) {
			return $msg;
		}
	}
	return true;
}

require_once 'gather_take.php';

// basic gather take
function test_0() {
	$res = gather(function() {
		foreach (range(1, 10) as $i) {
			take($i*100);
		}
	});
	$should = array(
		100,
		200,
		300,
		400,
		500,
		600,
		700,
		800,
		900,
		1000,
	);
	return is_deeply($res, $should, 'basic gather take');
}

// last
function test_1() {
	$res = gather(function() {
		take(0);
		last();
		take(1);
		take(2);
	});
	return is_deeply($res, array(0), 'last breaks out of a gather');
}

// gathered
function test_2() {
	$res = gather(function() {
		for ($i = 100; $i < 0; $i++) {
			take($i);
		}
		if (!gathered()) {
			take(null);
		}
	});
	return is_deeply($res, array(null), 'gathered detects no gather');
}

// gathered the other way
function test_3() {
	$res = gather(function() {
		take(1);
		take(2);
		if (!gathered()) {
			take(3);
		}
	});
	return is_deeply($res, array(1, 2), 'gathered detects some gather');
}

// non-last() exceptions are left alone
function test_4() {
	try {
		$res = gather(function() {
			take(1);
			throw new Exception('meh');
		});
	}
	catch (Exception $e) {
		return true;
	}
	return 'non-last()-generated exceptions are left alone';
}

// takev()
function test_5() {
	$res = gather(function() {
		takev(array(1,2,3));
		take(4);
	});
	return is_deeply($res, array(1,2,3,4), 'takev() works');
}

// gather() argument passing
function test_6() {
	$res = gather(function($f, $a, $b) {
		if ($a == 'param_a' && $b == 'param_b') {
			take('a');
		}
	}, 'param_a', 'param_b');
	return is_deeply($res, array('a'), 'gather param passing works');
}

// recursive $f($f) calls...
function test_7() {
	$res = gather(function($f, $i) {
		take($i);
		if ($i-1) {
			$f($f, $i-1);
		}
	}, 10);
	return is_deeply($res, array(10, 9, 8, 7, 6, 5, 4, 3, 2, 1), 'recursive calls of the gatherer work');
}

// nested gathering
function test_8() {
	$i = 0;
	$res = gather(function() use(&$i) {
		while ($i < 10) {
			$r = gather(function() use(&$i) {
				take($i);
				take($i * 10);
				$i++;
			});
			takev($r);
		}
	});
	$should = array(
		0,
		0,
		1,
		10,
		2,
		20,
		3,
		30,
		4,
		40,
		5,
		50,
		6,
		60,
		7,
		70,
		8,
		80,
		9,
		90,
	);
	return is_deeply($res, $should, 'nested gathers work');
}

$i = 0;
while (function_exists("test_$i")) {
	$f = "test_$i";
	$r = $f();
	echo "TEST $i ", ($r === true ? 'OK' : "FAIL: $r"), "\n";
	$i++;
}
