<?php
/**
 * Created by PhpStorm.
 * User: bzick
 * Date: 07.08.17
 * Time: 14:19
 */

namespace Koda;


class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Koda
     */
    public $koda
    ;
    public function setUp()
    {
        parent::setUp();
        $this->koda = new Koda();
    }
}