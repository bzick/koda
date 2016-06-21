<?php

namespace Koda;


use Koda\Error\InvalidArgumentException;

class KodaTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @param int[] $a (unsigned)
	 * @param int[] $b (value 1..10)
	 * @return bool
	 */
	public static function staticEquals(array $a, array $b) {
		return $a == $b;
	}

	public function providerCall() {
		return [
			[KodaTest::class."::staticEquals", [[1], [1.0]], true],
			[[KodaTest::class, "staticEquals"], [[1], [1.0]], true],
			[[$this, "staticEquals"], [[1], [1.0]], true],
			[KodaTest::class."::staticEquals", [[1], [11]], new InvalidArgumentException()],
		];
	}

	/**
	 * @dataProvider providerCall
	 * @param $cb
	 * @param $args
	 * @param $result
	 * @param array $options
	 * @throws \Exception
	 */
	public function testCall($cb, $args, $result, $options = []) {
		try {
			$this->assertEquals($result, \Koda::call($cb, $args, $options));
		} catch (\Exception $e) {
			if($result instanceof \Exception) {
				$this->assertInstanceOf(get_class($result), $e);
			} else {
				throw $e;
			}
		}
	}
}