<?php

namespace Koda;


class FilterTest extends \PHPUnit_Framework_TestCase
{

	public $verify;

	public static function variants()
	{
		return ["v1", "v2"];
	}

	public static function options()
	{
		return ["v1" => "version 1", "v2" => "version 2"];
	}

	public function equalsFilter($val, $letter)
	{
		return $val === $letter;
	}

	public function setUp()
	{
		$this->verify = new Filter($this);
	}

	public function providerValidators()
	{
		return [
			["unsigned", true, 1],
			["unsigned", true, 0],
			["unsigned", false, -1],

			["date", true, "2015-06-21"],
			["date", true, "20150621"],
			["date", false, "15|06|21"],
			["date", true, "15/06/21", "y/m/d"],

			["is", true, 1],

			["smalltext", true, str_pad("", 255, "a")],
			["smalltext", false, str_pad("", 256, "a")],

			["text", true, str_pad("", 256, "a")],

			["largetext", true, str_pad("", 256, "a")],

			["positive", true, 1],
			["positive", false, 0],
			["positive", false, -1],

			["negative", true, -1],
			["negative", false, 0],
			["negative", false, 1],

			["email", true, "a.cobest@gmail.com"],
			["email", false, "a.cobest.gmail.com"],
			["email", false, "Ivan Shalganov <a.cobest@gmail.com>"],
			["email", true, "Ivan Shalganov <a.cobest@gmail.com>", "extended"],
			["email", false, "Ivan Shalganov a.cobest@gmail.com", "extended"],
			["email", false, "Ivan Shalganov <a.cobest@gmail.com", "extended"],
			["email", false, "Ivan Shalganov <a.cobest.gmail.com>"],

			["domain", true, "yandex.com"],
			["domain", false, "yandexcom"],
			["domain", false, "домен.рф"], // add punicode

			["url", true, "https://www.yandex.com/search/?text=Smart%20Invoker%20PHP"],
			["url", true, "https://www.yandex.com/"],
			["url", false, "https://домен.рф/"], // add punicode
			["url", false, "//www.yandex.com/search/?text=Smart%20Invoker%20PHP"],
			["url", false, "text=Smart%20Invoker%20PHP"],

			["ip", true, "127.0.0.1"],
			["ip", true, "::1"],
			["ip", false, "127.o.o.1"],

			["keyword", true, "bzick"],
			["keyword", false, "bzick/"],

			["value", true, 2, [1, 3]],
			["value", false, 4, [1, 3]],
			["value", true, 4, 4],
			["value", false, 4, 5],


			["length", true, "bzick", [1, 6]],
			["length", false, "bzick", [1, 3]],
			["length", true, "bzick", 5],
			["length", false, "bzick", 6],

			["callback", true, "is_string"],
			["callback", false, "is_string2"],

			["className", true, FilterTest::class],
			["className", false, FilterTest::class . "Invalid"],

			["file", true, __FILE__],
			["file", false, '/unexists'],

			["dir", true, __DIR__],
			["dir", false, '/unexists'],

			["mask", true, 'bzick', "a-z"],
			["mask", false, 'bzick2', "a-z"],
			["mask", true, 'bzick2', "a-z0-9"],

			["regexp", true, 'bzick', '/^bzick$/'],
			["regexp", false, 'bzick2', '/^bzick$/'],
			["regexp", true, 'bzick2', '/^bzick\d+$/'],
			["regexp", false, 'bzick22', '/^bzick\d$/'],

			["like", true, 'bzick', "bz*ck"],
			["like", false, 'bzick2', "bz*ck"],
			["like", true, 'bzick2', "bz*ck[0-9]"],

			["variants", true, 'v1', ["v1", "v2"]],
			["variants", false, 'v3', ["v1", "v2"]],
			["variants", true, 'v1', "v1 v2"],
			["variants", false, 'v3', "v1 v2"],
			["variants", true, 'v1', FilterTest::class . '::variants'],
			["variants", false, 'v3', FilterTest::class . '::variants'],

			["option", true, 'v1', FilterTest::class . '::options'],
			["option", false, 'v3', FilterTest::class . '::options'],

			["equals", true, 'v3', 'v3'],
			["equals", false, 'v3', 'v4'],

			["fake", true, 'v3'],

		];
	}

	/**
	 * @dataProvider providerValidators
	 *
	 * @param $method
	 * @param $value
	 * @param $param
	 * @param $result
	 */
	public function testValidations($method, $result, $value, $param = true)
	{
		$this->assertEquals($result, $this->verify->{$method . "Filter"}($value, $param));
	}

}