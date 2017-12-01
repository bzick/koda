<?php

require_once __DIR__.'/../vendor/autoload.php';


class Example {

	public function __construct() {
	}

	/**
	 * Calculate of hypotenuse
	 * @param float $leg1 (unsigned)
	 * @param float $leg2 (unsigned)
	 * @param int $round (value 1..6)
	 * @return float
	 */
	public function hypotenuseCalc($leg1, $leg2, $round = 2) {
		var_dump("=======", $leg1, $leg2, $round);
		return round(sqrt($leg1*$leg1 + $leg2*$leg2), $round);
	}

	public function test() {

	}
}

//$class = new Koda\ClassInfo('Example', Koda\ClassInfo::FLAG_NON_STATIC, "*Calc");

//$koda = new Koda\Koda();
//var_dump(json_encode($class, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
//var_dump(Koda::call([new Example(), 'hypotenuseCalc'], ['leg2' => 3, 'leg1' => 4]));
//var_dump($koda->make(Example::class));