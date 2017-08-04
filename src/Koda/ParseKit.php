<?php
/**
 * Created by PhpStorm.
 * User: bzick
 * Date: 03.08.17
 * Time: 10:36
 */

namespace Koda;


class ParseKit
{

    /**
     * Parse doc-block
     * @param string $doc
     *
     * @return array
     */
    public static function parseDocBlock(string $doc)
    {
        $parsed = [];
        $doc = preg_replace('/^\s*(\*\s*)+/mS', '', trim($doc, "*/ \t\n\r"));
        if (strpos($doc, "@") !== false) {
            $doc = explode("@", $doc, 2);
            if ($doc[0] = trim($doc[0])) {
                $parsed["desc"] = [$doc[0]];
            }
            if ($doc[1]) {
                foreach (preg_split('/\r?\n@/mS', $doc[1]) as $param) {
                    $param = preg_split('/\s+/S', $param, 2);
                    if (!isset($param[1])) {
                        $param[1] = "";
                    }
                    $p = strtolower($param[0]);
                    if(isset($parsed[$p])) {
                        $parsed[$p][] = trim($param[1]);
                    } else {
                        $parsed[$p]   = [trim($param[1])];
                    }
                }
            }
        }

        return $parsed;
    }

}