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
        $doc = preg_replace('/^\s*(\*[ \t]*)+/mS', '', trim($doc, "*/ \t\n\r"));
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
        } else {
            $parsed["desc"] = [$doc];
        }

        return $parsed;
    }

    /**
     * @param $filters
     *
     * @return array
     */
    public static function parseDoc($filters)
    {
        $verify = [];
        if (preg_match_all('!((.*?):?(\s+.*?)?),\s*!S', $filters . ',', $m)) {
            foreach ($m[2] as $k => $filter) {
                $arg = trim($m[3][$k]);
                if ($arg) {
                    if ($filter == "variants") {
                        if (is_callable($arg)) {
                            $args = call_user_func($arg);
                        } else {
                            $args = preg_split('/\s+/', trim($arg));
                        }
                    } elseif (preg_match('!^(?<interval>(?<interval_from>\d+)\.\.(?<interval_to>\d+))|(?<range>(?<range_sign>[\>\<]\=?)\s*(?<range_value>\d+))$!S',
                        $arg, $args)) {
                        if ($args['interval']) {
                            $args = [$args['interval_from'] * 1, $args['interval_to'] * 1];
                        } elseif ($args['range']) {
                            switch ($args['range_sign']) {
                                case '<':
                                    $args = [-PHP_INT_MAX, $args['range_value'] - 1];
                                    break;
                                case '<=':
                                    $args = [-PHP_INT_MAX, $args['range_value']];
                                    break;
                                case '>':
                                    $args = [$args['range_value'] + 1, PHP_INT_MAX];
                                    break;
                                case '>=':
                                    $args = [$args['range_value'], PHP_INT_MAX];
                                    break;
                                case '=':
                                    $args = [$args['range_value'], $args['range_value']];
                                    break;
                                default:
                                    continue;
                            }
                        } else {
                            $args = $arg;
                        }
                    } else {
                        $args = $arg;
                    }
                } else {
                    $args = "";
                }
                $verify[$filter] = [
                    "original" => $m[1][$k],
                    "args"     => $args
                ];
            }
        }

        return $verify;
    }

    public static function parseUse($file, $limit): array
    {
        $use  = [];
        $file = new \SplFileObject($file, "r");
        $code = "";
        foreach($file as $no => $line) {
            if ($no >= $limit - 1) {
                break;
            }

            $code .= $line;
        }
        unset($file);

        $tokens = token_get_all($code, TOKEN_PARSE);

        $class_name = "";
        $alias = "";
        $mode = "none";
        foreach ($tokens as $token) {
            if(is_array($token)) {
                $token[] = token_name($token[0]);
            }
            var_dump($token);
            if ($token[0] === T_WHITESPACE) {
                continue;
            }
            if ($mode === "none") {
                if ($token[0] === T_NAMESPACE) {
                    $mode = "namespace";
                }
            } elseif ($mode === "namespace") {
                if ($token === ';' || $token === '{') {
                    $mode = "space";
                }
            } elseif ($mode === "space") {
                if ($token[0] === T_USE) {
                    $mode = "use";
                }
            } elseif ($mode == "use") {
                if ($token[0] === T_FUNCTION || $token[0] === T_CONST) {
                    $mode = "space";
                } elseif ($token[0] === T_STRING) {
                    if ($mode === "class_name") {
                        $class_name .= $token[1];
                        $alias = $token[1];
                    } else {
                        $alias = $token[1];
                    }
                }
            }
            if (is_array($token)) {
                if ($token[0] === T_NAMESPACE) {
                    $use  = [];
                    $mode = "namespace";
                } elseif ($token[0] === T_USE) {
                    $mode = "class_name";
                } elseif ($token[0] === T_STRING) {
                    if ($mode === "class_name") {
                        $class_name .= $token[1];
                        $alias = $token[1];
                    } else {
                        $alias = $token[1];
                    }

                } elseif ($token[0] === T_NAMESPACE) {
                    $class_name .= "\\";
                } elseif ($token[0] === T_AS) {
                    $mode = "alias";
                }
            } else {
                if ($token === "," || $token === ";") {
                    $mode = "";
                    $use[$alias] = $class_name;
                }
            }
        }

        var_dump($use);

        return $use;
    }


}