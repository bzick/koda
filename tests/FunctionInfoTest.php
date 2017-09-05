<?php

namespace Koda;


class FunctionInfoTest extends TestCase
{

    public function testInfo()
    {
        $info = new FunctionInfo();
        $info->scan('Koda\__koda_unit1');
        $this->assertSame('Koda\__koda_unit1', $info->name);
        $this->assertSame('__koda_unit1', $info->function);
        $this->assertSame('Koda', $info->namespace);
        $this->assertSame('more', $info->getOption('see'));
        $this->assertSame(2, count($info->args));
    }
}

/**
 * @param int $one
 * @param string $two
 *
 * @see more
 */
function __koda_unit1($one, $two) {

}