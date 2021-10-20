<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

namespace MensBeam\HTML\Parser;

// This file adds shims for matching single characters 
//  using the same API as the ctype extension, if the
//  extension is missing. They are not a complete
//  replacement, as they are designed only to evaluate
//  single characters

// @codeCoverageIgnoreStart
if (!extension_loaded("ctype")) {
    function ctype_alnum(string $str): bool {
        return ["a"=>true,"b"=>true,"c"=>true,"d"=>true,"e"=>true,"f"=>true,"g"=>true,"h"=>true,"i"=>true,"j"=>true,"k"=>true,"l"=>true,"m"=>true,"n"=>true,"o"=>true,"p"=>true,"q"=>true,"r"=>true,"s"=>true,"t"=>true,"u"=>true,"v"=>true,"w"=>true,"x"=>true,"y"=>true,"z"=>true,"A"=>true,"B"=>true,"C"=>true,"D"=>true,"E"=>true,"F"=>true,"G"=>true,"H"=>true,"I"=>true,"J"=>true,"K"=>true,"L"=>true,"M"=>true,"N"=>true,"O"=>true,"P"=>true,"Q"=>true,"R"=>true,"S"=>true,"T"=>true,"U"=>true,"V"=>true,"W"=>true,"X"=>true,"Y"=>true,"Z"=>true,"0"=>true,"1"=>true,"2"=>true,"3"=>true,"4"=>true,"5"=>true,"6"=>true,"7"=>true,"8"=>true,"9"=>true][$str] ?? false;
    }

    function ctype_alpha(string $str): bool {
        return ["a"=>true,"b"=>true,"c"=>true,"d"=>true,"e"=>true,"f"=>true,"g"=>true,"h"=>true,"i"=>true,"j"=>true,"k"=>true,"l"=>true,"m"=>true,"n"=>true,"o"=>true,"p"=>true,"q"=>true,"r"=>true,"s"=>true,"t"=>true,"u"=>true,"v"=>true,"w"=>true,"x"=>true,"y"=>true,"z"=>true,"A"=>true,"B"=>true,"C"=>true,"D"=>true,"E"=>true,"F"=>true,"G"=>true,"H"=>true,"I"=>true,"J"=>true,"K"=>true,"L"=>true,"M"=>true,"N"=>true,"O"=>true,"P"=>true,"Q"=>true,"R"=>true,"S"=>true,"T"=>true,"U"=>true,"V"=>true,"W"=>true,"X"=>true,"Y"=>true,"Z"=>true][$str] ?? false;
    }

    function ctype_upper(string $str): bool {
        return ["A"=>true,"B"=>true,"C"=>true,"D"=>true,"E"=>true,"F"=>true,"G"=>true,"H"=>true,"I"=>true,"J"=>true,"K"=>true,"L"=>true,"M"=>true,"N"=>true,"O"=>true,"P"=>true,"Q"=>true,"R"=>true,"S"=>true,"T"=>true,"U"=>true,"V"=>true,"W"=>true,"X"=>true,"Y"=>true,"Z"=>true][$str] ?? false;
    }

    function ctype_digit(string $str): bool {
        return ["0"=>true,"1"=>true,"2"=>true,"3"=>true,"4"=>true,"5"=>true,"6"=>true,"7"=>true,"8"=>true,"9"=>true][$str] ?? false;
    }

    function ctype_xdigit(string $str): bool {
        return ["a"=>true,"b"=>true,"c"=>true,"d"=>true,"e"=>true,"f"=>true,"A"=>true,"B"=>true,"C"=>true,"D"=>true,"E"=>true,"F"=>true,"0"=>true,"1"=>true,"2"=>true,"3"=>true,"4"=>true,"5"=>true,"6"=>true,"7"=>true,"8"=>true,"9"=>true][$str] ?? false;
    }
}
// @codeCoverageIgnoreEnd
