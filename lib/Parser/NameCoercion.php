<?php
/** @license MIT
 * Copyright 2017 , Dustin Wilson, J. King et al.
 * See LICENSE and AUTHORS files for details */

declare(strict_types=1);
namespace MensBeam\HTML\Parser;

use MensBeam\Intl\Encoding\UTF8;

trait NameCoercion {
    /** @codeCoverageIgnore */
    protected function coerceNameFifthEdition(string $name): string {
        // This matches the inverse of the production of NameChar in XML 1.0 Fifth Edition,
        //   with the added exclusion of ":" from allowed characters
        // See https://www.w3.org/TR/REC-xml/#NT-NameStartChar
        preg_match_all('/[^\-\.0-9\x{B7}\x{300}-\x{36F}\x{203F}-\x{2040}A-Za-z_\x{C0}-\x{D6}\x{D8}-\x{F6}\x{F8}-\x{2FF}\x{370}-\x{37D}\x{37F}-\x{1FFF}\x{200C}-\x{200D}\x{2070}-\x{218F}\x{2C00}-\x{2FEF}\x{3001}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFFD}\x{10000}-\x{EFFFF}]/u', $name, $m);
        foreach (array_unique($m[0], \SORT_STRING) as $c) {
            $o = (new UTF8($c))->nextCode();
            $esc = "U".str_pad(strtoupper(dechex($o)), 6, "0", \STR_PAD_LEFT);
            $name = str_replace($c, $esc, $name);
        }
        // Apply stricter rules to the first character
        if (preg_match('/^[^A-Za-z_\x{C0}-\x{D6}\x{D8}-\x{F6}\x{F8}-\x{2FF}\x{370}-\x{37D}\x{37F}-\x{1FFF}\x{200C}-\x{200D}\x{2070}-\x{218F}\x{2C00}-\x{2FEF}\x{3001}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFFD}\x{10000}-\x{EFFFF}]/u', $name, $m)) {
            $c = (string) $m[0];
            $o = (new UTF8($c))->nextCode();
            $esc = "U".str_pad(strtoupper(dechex($o)), 6, "0", \STR_PAD_LEFT);
            $name = $esc.substr($name, strlen($c));
        }
        return $name;
    }

    protected function coerceName(string $name): string {
        // This matches the inverse of the production of Name in XML 1.0 Fourth Edition,
        //   with the added exclusion of ":" from allowed characters
        // See https://www.w3.org/TR/2006/REC-xml-20060816/#NT-NameChar
        preg_match_all('/[^_\.\-\x{41}-\x{5A}\x{61}-\x{7A}\x{C0}-\x{D6}\x{D8}-\x{F6}\x{F8}-\x{FF}\x{100}-\x{131}\x{134}-\x{13E}\x{141}-\x{148}\x{14A}-\x{17E}\x{180}-\x{1C3}\x{1CD}-\x{1F0}\x{1F4}-\x{1F5}\x{1FA}-\x{217}\x{250}-\x{2A8}\x{2BB}-\x{2C1}\x{386}\x{388}-\x{38A}\x{38C}\x{38E}-\x{3A1}\x{3A3}-\x{3CE}\x{3D0}-\x{3D6}\x{3DA}\x{3DC}\x{3DE}\x{3E0}\x{3E2}-\x{3F3}\x{401}-\x{40C}\x{40E}-\x{44F}\x{451}-\x{45C}\x{45E}-\x{481}\x{490}-\x{4C4}\x{4C7}-\x{4C8}\x{4CB}-\x{4CC}\x{4D0}-\x{4EB}\x{4EE}-\x{4F5}\x{4F8}-\x{4F9}\x{531}-\x{556}\x{559}\x{561}-\x{586}\x{5D0}-\x{5EA}\x{5F0}-\x{5F2}\x{621}-\x{63A}\x{641}-\x{64A}\x{671}-\x{6B7}\x{6BA}-\x{6BE}\x{6C0}-\x{6CE}\x{6D0}-\x{6D3}\x{6D5}\x{6E5}-\x{6E6}\x{905}-\x{939}\x{93D}\x{958}-\x{961}\x{985}-\x{98C}\x{98F}-\x{990}\x{993}-\x{9A8}\x{9AA}-\x{9B0}\x{9B2}\x{9B6}-\x{9B9}\x{9DC}-\x{9DD}\x{9DF}-\x{9E1}\x{9F0}-\x{9F1}\x{A05}-\x{A0A}\x{A0F}-\x{A10}\x{A13}-\x{A28}\x{A2A}-\x{A30}\x{A32}-\x{A33}\x{A35}-\x{A36}\x{A38}-\x{A39}\x{A59}-\x{A5C}\x{A5E}\x{A72}-\x{A74}\x{A85}-\x{A8B}\x{A8D}\x{A8F}-\x{A91}\x{A93}-\x{AA8}\x{AAA}-\x{AB0}\x{AB2}-\x{AB3}\x{AB5}-\x{AB9}\x{ABD}\x{AE0}\x{B05}-\x{B0C}\x{B0F}-\x{B10}\x{B13}-\x{B28}\x{B2A}-\x{B30}\x{B32}-\x{B33}\x{B36}-\x{B39}\x{B3D}\x{B5C}-\x{B5D}\x{B5F}-\x{B61}\x{B85}-\x{B8A}\x{B8E}-\x{B90}\x{B92}-\x{B95}\x{B99}-\x{B9A}\x{B9C}\x{B9E}-\x{B9F}\x{BA3}-\x{BA4}\x{BA8}-\x{BAA}\x{BAE}-\x{BB5}\x{BB7}-\x{BB9}\x{C05}-\x{C0C}\x{C0E}-\x{C10}\x{C12}-\x{C28}\x{C2A}-\x{C33}\x{C35}-\x{C39}\x{C60}-\x{C61}\x{C85}-\x{C8C}\x{C8E}-\x{C90}\x{C92}-\x{CA8}\x{CAA}-\x{CB3}\x{CB5}-\x{CB9}\x{CDE}\x{CE0}-\x{CE1}\x{D05}-\x{D0C}\x{D0E}-\x{D10}\x{D12}-\x{D28}\x{D2A}-\x{D39}\x{D60}-\x{D61}\x{E01}-\x{E2E}\x{E30}\x{E32}-\x{E33}\x{E40}-\x{E45}\x{E81}-\x{E82}\x{E84}\x{E87}-\x{E88}\x{E8A}\x{E8D}\x{E94}-\x{E97}\x{E99}-\x{E9F}\x{EA1}-\x{EA3}\x{EA5}\x{EA7}\x{EAA}-\x{EAB}\x{EAD}-\x{EAE}\x{EB0}\x{EB2}-\x{EB3}\x{EBD}\x{EC0}-\x{EC4}\x{F40}-\x{F47}\x{F49}-\x{F69}\x{10A0}-\x{10C5}\x{10D0}-\x{10F6}\x{1100}\x{1102}-\x{1103}\x{1105}-\x{1107}\x{1109}\x{110B}-\x{110C}\x{110E}-\x{1112}\x{113C}\x{113E}\x{1140}\x{114C}\x{114E}\x{1150}\x{1154}-\x{1155}\x{1159}\x{115F}-\x{1161}\x{1163}\x{1165}\x{1167}\x{1169}\x{116D}-\x{116E}\x{1172}-\x{1173}\x{1175}\x{119E}\x{11A8}\x{11AB}\x{11AE}-\x{11AF}\x{11B7}-\x{11B8}\x{11BA}\x{11BC}-\x{11C2}\x{11EB}\x{11F0}\x{11F9}\x{1E00}-\x{1E9B}\x{1EA0}-\x{1EF9}\x{1F00}-\x{1F15}\x{1F18}-\x{1F1D}\x{1F20}-\x{1F45}\x{1F48}-\x{1F4D}\x{1F50}-\x{1F57}\x{1F59}\x{1F5B}\x{1F5D}\x{1F5F}-\x{1F7D}\x{1F80}-\x{1FB4}\x{1FB6}-\x{1FBC}\x{1FBE}\x{1FC2}-\x{1FC4}\x{1FC6}-\x{1FCC}\x{1FD0}-\x{1FD3}\x{1FD6}-\x{1FDB}\x{1FE0}-\x{1FEC}\x{1FF2}-\x{1FF4}\x{1FF6}-\x{1FFC}\x{2126}\x{212A}-\x{212B}\x{212E}\x{2180}-\x{2182}\x{3041}-\x{3094}\x{30A1}-\x{30FA}\x{3105}-\x{312C}\x{AC00}-\x{D7A3}\x{4E00}-\x{9FA5}\x{3007}\x{3021}-\x{3029}\x{30}-\x{39}\x{660}-\x{669}\x{6F0}-\x{6F9}\x{966}-\x{96F}\x{9E6}-\x{9EF}\x{A66}-\x{A6F}\x{AE6}-\x{AEF}\x{B66}-\x{B6F}\x{BE7}-\x{BEF}\x{C66}-\x{C6F}\x{CE6}-\x{CEF}\x{D66}-\x{D6F}\x{E50}-\x{E59}\x{ED0}-\x{ED9}\x{F20}-\x{F29}\x{300}-\x{345}\x{360}-\x{361}\x{483}-\x{486}\x{591}-\x{5A1}\x{5A3}-\x{5B9}\x{5BB}-\x{5BD}\x{5BF}\x{5C1}-\x{5C2}\x{5C4}\x{64B}-\x{652}\x{670}\x{6D6}-\x{6DC}\x{6DD}-\x{6DF}\x{6E0}-\x{6E4}\x{6E7}-\x{6E8}\x{6EA}-\x{6ED}\x{901}-\x{903}\x{93C}\x{93E}-\x{94C}\x{94D}\x{951}-\x{954}\x{962}-\x{963}\x{981}-\x{983}\x{9BC}\x{9BE}\x{9BF}\x{9C0}-\x{9C4}\x{9C7}-\x{9C8}\x{9CB}-\x{9CD}\x{9D7}\x{9E2}-\x{9E3}\x{A02}\x{A3C}\x{A3E}\x{A3F}\x{A40}-\x{A42}\x{A47}-\x{A48}\x{A4B}-\x{A4D}\x{A70}-\x{A71}\x{A81}-\x{A83}\x{ABC}\x{ABE}-\x{AC5}\x{AC7}-\x{AC9}\x{ACB}-\x{ACD}\x{B01}-\x{B03}\x{B3C}\x{B3E}-\x{B43}\x{B47}-\x{B48}\x{B4B}-\x{B4D}\x{B56}-\x{B57}\x{B82}-\x{B83}\x{BBE}-\x{BC2}\x{BC6}-\x{BC8}\x{BCA}-\x{BCD}\x{BD7}\x{C01}-\x{C03}\x{C3E}-\x{C44}\x{C46}-\x{C48}\x{C4A}-\x{C4D}\x{C55}-\x{C56}\x{C82}-\x{C83}\x{CBE}-\x{CC4}\x{CC6}-\x{CC8}\x{CCA}-\x{CCD}\x{CD5}-\x{CD6}\x{D02}-\x{D03}\x{D3E}-\x{D43}\x{D46}-\x{D48}\x{D4A}-\x{D4D}\x{D57}\x{E31}\x{E34}-\x{E3A}\x{E47}-\x{E4E}\x{EB1}\x{EB4}-\x{EB9}\x{EBB}-\x{EBC}\x{EC8}-\x{ECD}\x{F18}-\x{F19}\x{F35}\x{F37}\x{F39}\x{F3E}\x{F3F}\x{F71}-\x{F84}\x{F86}-\x{F8B}\x{F90}-\x{F95}\x{F97}\x{F99}-\x{FAD}\x{FB1}-\x{FB7}\x{FB9}\x{20D0}-\x{20DC}\x{20E1}\x{302A}-\x{302F}\x{3099}\x{309A}\x{B7}\x{2D0}\x{2D1}\x{387}\x{640}\x{E46}\x{EC6}\x{3005}\x{3031}-\x{3035}\x{309D}-\x{309E}\x{30FC}-\x{30FE}]/u', $name, $m);
        foreach (array_unique($m[0], \SORT_STRING) as $c) {
            $o = (new UTF8($c))->nextCode();
            $esc = "U".str_pad(strtoupper(dechex($o)), 6, "0", \STR_PAD_LEFT);
            $name = str_replace($c, $esc, $name);
        }
        // Apply stricter rules to the first character
        if (preg_match('/^[^_\x{41}-\x{5A}\x{61}-\x{7A}\x{C0}-\x{D6}\x{D8}-\x{F6}\x{F8}-\x{FF}\x{100}-\x{131}\x{134}-\x{13E}\x{141}-\x{148}\x{14A}-\x{17E}\x{180}-\x{1C3}\x{1CD}-\x{1F0}\x{1F4}-\x{1F5}\x{1FA}-\x{217}\x{250}-\x{2A8}\x{2BB}-\x{2C1}\x{386}\x{388}-\x{38A}\x{38C}\x{38E}-\x{3A1}\x{3A3}-\x{3CE}\x{3D0}-\x{3D6}\x{3DA}\x{3DC}\x{3DE}\x{3E0}\x{3E2}-\x{3F3}\x{401}-\x{40C}\x{40E}-\x{44F}\x{451}-\x{45C}\x{45E}-\x{481}\x{490}-\x{4C4}\x{4C7}-\x{4C8}\x{4CB}-\x{4CC}\x{4D0}-\x{4EB}\x{4EE}-\x{4F5}\x{4F8}-\x{4F9}\x{531}-\x{556}\x{559}\x{561}-\x{586}\x{5D0}-\x{5EA}\x{5F0}-\x{5F2}\x{621}-\x{63A}\x{641}-\x{64A}\x{671}-\x{6B7}\x{6BA}-\x{6BE}\x{6C0}-\x{6CE}\x{6D0}-\x{6D3}\x{6D5}\x{6E5}-\x{6E6}\x{905}-\x{939}\x{93D}\x{958}-\x{961}\x{985}-\x{98C}\x{98F}-\x{990}\x{993}-\x{9A8}\x{9AA}-\x{9B0}\x{9B2}\x{9B6}-\x{9B9}\x{9DC}-\x{9DD}\x{9DF}-\x{9E1}\x{9F0}-\x{9F1}\x{A05}-\x{A0A}\x{A0F}-\x{A10}\x{A13}-\x{A28}\x{A2A}-\x{A30}\x{A32}-\x{A33}\x{A35}-\x{A36}\x{A38}-\x{A39}\x{A59}-\x{A5C}\x{A5E}\x{A72}-\x{A74}\x{A85}-\x{A8B}\x{A8D}\x{A8F}-\x{A91}\x{A93}-\x{AA8}\x{AAA}-\x{AB0}\x{AB2}-\x{AB3}\x{AB5}-\x{AB9}\x{ABD}\x{AE0}\x{B05}-\x{B0C}\x{B0F}-\x{B10}\x{B13}-\x{B28}\x{B2A}-\x{B30}\x{B32}-\x{B33}\x{B36}-\x{B39}\x{B3D}\x{B5C}-\x{B5D}\x{B5F}-\x{B61}\x{B85}-\x{B8A}\x{B8E}-\x{B90}\x{B92}-\x{B95}\x{B99}-\x{B9A}\x{B9C}\x{B9E}-\x{B9F}\x{BA3}-\x{BA4}\x{BA8}-\x{BAA}\x{BAE}-\x{BB5}\x{BB7}-\x{BB9}\x{C05}-\x{C0C}\x{C0E}-\x{C10}\x{C12}-\x{C28}\x{C2A}-\x{C33}\x{C35}-\x{C39}\x{C60}-\x{C61}\x{C85}-\x{C8C}\x{C8E}-\x{C90}\x{C92}-\x{CA8}\x{CAA}-\x{CB3}\x{CB5}-\x{CB9}\x{CDE}\x{CE0}-\x{CE1}\x{D05}-\x{D0C}\x{D0E}-\x{D10}\x{D12}-\x{D28}\x{D2A}-\x{D39}\x{D60}-\x{D61}\x{E01}-\x{E2E}\x{E30}\x{E32}-\x{E33}\x{E40}-\x{E45}\x{E81}-\x{E82}\x{E84}\x{E87}-\x{E88}\x{E8A}\x{E8D}\x{E94}-\x{E97}\x{E99}-\x{E9F}\x{EA1}-\x{EA3}\x{EA5}\x{EA7}\x{EAA}-\x{EAB}\x{EAD}-\x{EAE}\x{EB0}\x{EB2}-\x{EB3}\x{EBD}\x{EC0}-\x{EC4}\x{F40}-\x{F47}\x{F49}-\x{F69}\x{10A0}-\x{10C5}\x{10D0}-\x{10F6}\x{1100}\x{1102}-\x{1103}\x{1105}-\x{1107}\x{1109}\x{110B}-\x{110C}\x{110E}-\x{1112}\x{113C}\x{113E}\x{1140}\x{114C}\x{114E}\x{1150}\x{1154}-\x{1155}\x{1159}\x{115F}-\x{1161}\x{1163}\x{1165}\x{1167}\x{1169}\x{116D}-\x{116E}\x{1172}-\x{1173}\x{1175}\x{119E}\x{11A8}\x{11AB}\x{11AE}-\x{11AF}\x{11B7}-\x{11B8}\x{11BA}\x{11BC}-\x{11C2}\x{11EB}\x{11F0}\x{11F9}\x{1E00}-\x{1E9B}\x{1EA0}-\x{1EF9}\x{1F00}-\x{1F15}\x{1F18}-\x{1F1D}\x{1F20}-\x{1F45}\x{1F48}-\x{1F4D}\x{1F50}-\x{1F57}\x{1F59}\x{1F5B}\x{1F5D}\x{1F5F}-\x{1F7D}\x{1F80}-\x{1FB4}\x{1FB6}-\x{1FBC}\x{1FBE}\x{1FC2}-\x{1FC4}\x{1FC6}-\x{1FCC}\x{1FD0}-\x{1FD3}\x{1FD6}-\x{1FDB}\x{1FE0}-\x{1FEC}\x{1FF2}-\x{1FF4}\x{1FF6}-\x{1FFC}\x{2126}\x{212A}-\x{212B}\x{212E}\x{2180}-\x{2182}\x{3041}-\x{3094}\x{30A1}-\x{30FA}\x{3105}-\x{312C}\x{AC00}-\x{D7A3}\x{4E00}-\x{9FA5}\x{3007}\x{3021}-\x{3029}]/u', $name, $m)) {
            $c = (string) $m[0];
            $o = (new UTF8($c))->nextCode();
            $esc = "U".str_pad(strtoupper(dechex($o)), 6, "0", \STR_PAD_LEFT);
            $name = $esc.substr($name, strlen($c));
        }
        return $name;
    }

    protected function uncoerceName(string $name): string {
        preg_match_all('/U[0-9A-F]{6}/', $name, $m);
        foreach (array_unique($m[0], \SORT_STRING) as $o) {
            $c = UTF8::encode(hexdec(substr($o, 1)));
            $name = str_replace($o, $c, $name);
        }
        return $name;
    }

    protected function escapeString(string $string, bool $attribute = false): string {
        # Escaping a string (for the purposes of the algorithm above) consists of
        # running the following steps:

        # 1. Replace any occurrence of the "&" character by the string "&amp;".
        # 2. Replace any occurrences of the U+00A0 NO-BREAK SPACE character by the
        # string "&nbsp;".
        $string = str_replace(['&', "\u{A0}"], ['&amp;', '&nbsp;'], $string);
        # 3. If the algorithm was invoked in the attribute mode, replace any
        # occurrences of the """ character by the string "&quot;".
        # 4. If the algorithm was not invoked in the attribute mode, replace any
        # occurrences of the "<" character by the string "&lt;", and any
        # occurrences of the ">" character by the string "&gt;".
        return ($attribute) ? str_replace('"', '&quot;', $string) : str_replace(['<', '>'], ['&lt;', '&gt;'], $string);
    }
}
