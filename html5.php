<?php

# Deviations:
# 1. Because of how this is used there's no need for navigation of a browsing
#    context or scripting.
# 2. Only UTF-8 is supported. This means all methods the parser is supposed to
#    use to convert and detect encodings are ignored except converting to the
#    UTF-8 encoding while processing the input stream.
# 3. NULL characters are stripped from the document instead of being
#    categorically converted to replacement characters. NULL characters are
#    invalid anyway, and NULL characters can't be injected into the document
#    through scripting in this implementation so stripping them makes more sense.
# 4. Comments before the DOCTYPE will be stripped from the document. It's a
#    limitation of PHP5's DOM which is what is used in tree building in this
#    implementation.
# 5. PHP's DOM cannot accept an empty DOCTYPE qualified name, so when an
#    empty one is encountered it is replaced with 'html'.
# 6. The DOM serializer (HTML5::serialize()) in this class is different from
#    what's specified in the spec (§13.3). While it's based upon it this
#    implementation is capable of accurately printing foreign content.

class HTML5
{
 # Debug flag.
 public static $debug=0;

 # DOMDocument.
 protected static $DOM=null;

 # DOMDocumentFragment used when parsing fragments.
 protected static $DOMFragment=null;

 # List of active formatting elements used by the tree builder.
 protected static $active=array();

 # Size of static::$active.
 protected static $activeSize=0;

 # Context element used when parsing fragments.
 protected static $context=null;

 # Input data that's being parsed.
 protected static $data=null;

 # Length of the input data.
 protected static $EOF=0;

 # Temporary buffer used by some states.
 protected static $buffer='';

 # Temporary attribute name buffer used by some states. Not in spec.
 # Used to store the attribute name as it's being built. That way the
 # attributes can be stored in a more efficient manner.
 protected static $attributenamebuffer='';

 protected static $parseErrors=array('tag name expected'                                => 'Tag name expected; found %s',
                                     'tag end expected'                                 => 'Tag end expected; found %s',
                                     'attribute name expected'                          => 'Attribute name expected; found %s',
                                     'attribute exists'                                 => 'Attribute %s already exists',
                                     'attribute value tag end expected'                 => 'Attribute value or tag end expected; found %s',
                                     'attribute value expected'                         => 'Attribute value expected; found %s',
                                     'unquoted attribute value expected'                => 'Unquoted attribute value expected; found %s',
                                     'attribute name tag end expected'                  => 'Attribute name or tag end expected; found %s',
                                     'doctype dashes cdata expected'                    => 'DOCTYPE, dashes, or CDATA expected; found %s',
                                     'comment expected'                                 => 'Comment data expected; found %s',
                                     'comment end expected'                             => 'Comment end expected; found %s',
                                     'doctype name expected'                            => 'DOCTYPE name expected; found %s',
                                     'doctype keyword tag end expected'                 => 'DOCTYPE keyword or tag end expected; found %s',
                                     'doctype public identifier expected'               => 'DOCTYPE public identifier expected; found %s',
                                     'double-quoted doctype public identifier expected' => 'Double-quoted DOCTYPE public identifier expected; found %s',
                                     'single-quoted doctype public identifier expected' => 'Single-quoted DOCTYPE public identifier expected; found %s',
                                     'doctype system identifier expected'               => 'DOCTYPE system identifier expected; found %s',
                                     'double-quoted doctype system identifier expected' => 'Double-quoted DOCTYPE system identifier expected; found %s',
                                     'single-quoted doctype system identifier expected' => 'Single-quoted DOCTYPE system identifier expected; found %s',
                                     'unexpected eof tag name'                          => 'Unexpected end of file; tag name expected',
                                     'unexpected eof escaped script data'               => 'Unexpected end of file; escaped script data expected',
                                     'unexpected eof double escaped script data'        => 'Unexpected end of file; double escaped script data expected',
                                     'unexpected eof attribute name'                    => 'Unexpected end of file; attribute name expected',
                                     'unexpected eof attribute value'                   => 'Unexpected end of file; attribute value expected',
                                     'unexpected eof unquoted attribute value'          => 'Unexpected end of file; unquoted attribute value expected',
                                     'unexpected eof attribute value tag end'           => 'Unexpected end of file; attribute value or tag end expected',
                                     'unexpected eof attribute name tag end'            => 'Unexpected end of file; attribute name or tag end expected',
                                     'unexpected eof comment'                           => 'Unexpected end of file; comment expected',
                                     'unexpected eof comment end'                       => 'Unexpected end of file; comment end expected',
                                     'unexpected eof doctype name'                      => 'Unexpected end of file; DOCTYPE name expected',
                                     'unexpected eof doctype keyword end tag'           => 'Unexpected end of file; DOCTYPE keyword or end tag expected',
                                     'unexpected eof doctype public identifier'         => 'Unexpected end of file; DOCTYPE public identifier expected',
                                     'unexpected eof doctype system identifier'         => 'Unexpected end of file; DOCTYPE system identifier expected',
                                     'control or noncharacters'                         => 'Control or permanently undefined unicode character in input stream',
                                     'numeric entity expected'                          => 'Numeric entity expected; found %s',
                                     'semicolon terminator expected'                    => 'Semicolon entity terminator expected; found %s',
                                     'invalid numeric entity'                           => 'Invalid numeric entity; replacing with an appropriate entity',
                                     'illegal codepoint'                                => 'Illegal codepoint for a numeric entity; replacing with a U+FFFD replacement character',
                                     'invalid named entity'                             => 'Invalid named entity',
                                     'doctype expected character'                       => 'DOCTYPE expected; found %s',
                                     'doctype expected start tag'                       => 'DOCTYPE expected; found %s start tag',
                                     'doctype expected end tag'                         => 'DOCTYPE expected; found %s end tag',
                                     'unexpected doctype'                               => 'Unexpected DOCTYPE; the current open element is %s',
                                     'unexpected start tag'                             => 'Unexpected %s start tag; the current open element is %s',
                                     'attributes in end tag'                            => 'Attributes found in %s end tag',
                                     'self-closing end tag'                             => '%s end tag cannot be self-closing',
                                     'unexpected end tag'                               => 'Unexpected %s end tag; the current open element is %s',
                                     'invalid doctype'                                  => 'Invalid DOCTYPE',
                                     'unexpected character'                             => 'Unexpected %s; the current open element is %s',
                                     'unexpected eof'                                   => 'Unexpected end of file; the current open element is %s',
                                     'invalid start tag'                                => '%s start tag is invalid; replaced with %s element',
                                     'invalid end tag'                                  => '%s end tag is invalid; replaced with %s element',
                                     'invalid foreign attribute'                        => '%s element\'s %s attribute is invalid; should be %s');

 protected static $fatalErrors=array('domdocument expected'                             => 'DOMDocument expected',
                                     'string expected'                                  => 'String expected; found %s',
                                     'callback expected'                                => 'Callback expected, found %s',
                                     'invalid consume length'                           => 'Consume length must be greater than 0',
                                     'invalid peek length'                              => 'Peek length must be greater than 0',
                                     'method expected'                                  => 'Class method name expected',
                                     'string array closure expected'                    => 'String, array, or closure expected; found %s',
                                     'closure expected'                                 => 'Closure expected; found instance of %s',
                                     'domnode expected'                                 => 'Instance of DOMNode expected; found %s',
                                     'domelement document frag expected'                => 'Instance of DOMElement, DOMDocument, or DOMDocumentFrag expected; found %s',
                                     'invalid option value'                             => 'Invalid value for option %s; %s expected; found %s',
                                     'invalid option value type'                        => 'Invalid value type for option %s; %s expected; found %s',
                                     'invalid parse error'                              => '%s is an invalid parse error',
                                     'invalid fatal error'                              => '%s is an invalid fatal error');

 //const PARSE_ERROR_TAG_NAME_EXPECTED = 0;
 //const PARSE_ERROR_TAG_END_EXPECTED = 1;
 //const PARSE_ERROR_ATTRIBUTE_NAME_EXPECTED = 2;
 //const PARSE_ERROR_ATTRIBUTE_EXISTS = 3;

 # Element table for foreign attribute adjustments.
 protected static $foreignAttributes=array('xlink:actuate' => 'http://www.w3.org/1999/xlink',
                                           'xlink:arcrole' => 'http://www.w3.org/1999/xlink',
                                           'xlink:href'    => 'http://www.w3.org/1999/xlink',
                                           'xlink:role'    => 'http://www.w3.org/1999/xlink',
                                           'xlink:show'    => 'http://www.w3.org/1999/xlink',
                                           'xlink:title'   => 'http://www.w3.org/1999/xlink',
                                           'xlink:type'    => 'http://www.w3.org/1999/xlink',
                                           'xml:base'      => 'http://www.w3.org/XML/1998/namespace',
                                           'xml:lang'      => 'http://www.w3.org/XML/1998/namespace',
                                           'xml:space'     => 'http://www.w3.org/XML/1998/namespace',
                                           'xmlns'         => 'http://www.w3.org/2000/xmlns/',
                                           'xmlns:xlink'   => 'http://www.w3.org/2000/xmlns/');

 # Used by the tree builder to house the parsed form element.
 protected static $form=null;

 # Used by the tree builder to determine if foster parenting is needed instead
 # of inserting elements.
 protected static $fosterParenting=false;

 # Used by the tree builder to determine if the current algorithm is a fragment.
 protected static $fragment=false;

 # Flag used by the tree builder to determine if framesets are okay to use.
 protected static $framesetOk=true;

 # Used by the tree builder to house the parsed head element.
 protected static $head=null;

 # Used by extended classes to see if the emitted token was HTML or foreign
 # content.
 protected static $htmlContent=false;

 # Elements that have implied end tags.
 protected static $impliedElements=array('dd','dt','li','option','optgroup','p','rp','rt');

 # Used by the tree building to house pending table character tokens.
 protected static $pendingTableCharacterTokens=array();

 # Used when parsing is completed to fix the PHP id attribute bug. Allows
 # DOMDocument->getElementById() to work on id attributes.
  protected static $relaxNG=<<<'NOWDOC'
<grammar xmlns="http://relaxng.org/ns/structure/1.0" datatypeLibrary="http://www.w3.org/2001/XMLSchema-datatypes">
 <start>
  <element>
   <anyName/>
   <ref name="anythingID"/>
  </element>
 </start>
 <define name="anythingID">
  <zeroOrMore>
   <choice>
    <element>
     <anyName/>
     <ref name="anythingID"/>
    </element>
    <attribute name="id">
     <data type="ID"/>
    </attribute>
    <zeroOrMore>
     <attribute><anyName/></attribute>
    </zeroOrMore>
    <text/>
   </choice>
  </zeroOrMore>
 </define>
</grammar>
NOWDOC;

 # Element table for SVG element attribute adjustments.
 protected static $svgAttributes=array('attributename'=>'attributeName',
                                       'attributetype'=>'attributeType',
                                       'basefrequency'=>'baseFrequency',
                                       'baseprofile'=>'baseProfile',
                                       'calcmode'=>'calcMode',
                                       'clippathunits'=>'clipPathUnits',
                                       'contentscripttype'=>'contentScriptType',
                                       'contentstyletype'=>'contentStyleType',
                                       'diffuseconstant'=>'diffuseConstant',
                                       'edgemode'=>'edgeMode',
                                       'externalresourcesrequired'=>'externalResourcesRequired',
                                       'filterres'=>'filterRes',
                                       'filterunits'=>'filterUnits',
                                       'glyphref'=>'glyphRef',
                                       'gradienttransform'=>'gradientTransform',
                                       'gradientunits'=>'gradientUnits',
                                       'kernelmatrix'=>'kernelMatrix',
                                       'kernelunitlength'=>'kernelUnitLength',
                                       'keypoints'=>'keyPoints',
                                       'keysplines'=>'keySplines',
                                       'keytimes'=>'keyTimes',
                                       'lengthadjust'=>'lengthAdjust',
                                       'limitingconeangle'=>'limitingConeAngle',
                                       'markerheight'=>'markerHeight',
                                       'markerunits'=>'markerUnits',
                                       'markerwidth'=>'markerWidth',
                                       'maskcontentunits'=>'maskContentUnits',
                                       'maskunits'=>'maskUnits',
                                       'numoctaves'=>'numOctaves',
                                       'pathlength'=>'pathLength',
                                       'patterncontentunits'=>'patternContentUnits',
                                       'patterntransform'=>'patternTransform',
                                       'patternunits'=>'patternUnits',
                                       'pointsatx'=>'pointsAtX',
                                       'pointsaty'=>'pointsAtY',
                                       'pointsatz'=>'pointsAtZ',
                                       'preservealpha'=>'preserveAlpha',
                                       'preserveaspectratio'=>'preserveAspectRatio',
                                       'primitiveunits'=>'primitiveUnits',
                                       'refx'=>'refX',
                                       'refy'=>'refY',
                                       'repeatcount'=>'repeatCount',
                                       'repeatdur'=>'repeatDur',
                                       'requiredextensions'=>'requiredExtensions',
                                       'requiredfeatures'=>'requiredFeatures',
                                       'specularconstant'=>'specularConstant',
                                       'specularexponent'=>'specularExponent',
                                       'spreadmethod'=>'spreadMethod',
                                       'startoffset'=>'startOffset',
                                       'stddeviation'=>'stdDeviation',
                                       'stitchtiles'=>'stitchTiles',
                                       'surfacescale'=>'surfaceScale',
                                       'systemlanguage'=>'systemLanguage',
                                       'tablevalues'=>'tableValues',
                                       'targetx'=>'targetX',
                                       'targety'=>'targetY',
                                       'textlength'=>'textLength',
                                       'viewbox'=>'viewBox',
                                       'viewtarget'=>'viewTarget',
                                       'xchannelselector'=>'xChannelSelector',
                                       'ychannelselector'=>'yChannelSelector',
                                       'zoomandpan'=>'zoomAndPan');

 protected static $svgElements=array('altglyph'=>'altGlyph',
                                     'altglyphdef'=>'altGlyphDef',
                                     'altglyphitem'=>'altGlyphItem',
                                     'animatecolor'=>'animateColor',
                                     'animatemotion'=>'animateMotion',
                                     'animatetransform'=>'animateTransform',
                                     'clippath'=>'clipPath',
                                     'feblend'=>'feBlend',
                                     'fecolormatrix'=>'feColorMatrix',
                                     'fecomponenttransfer'=>'feComponentTransfer',
                                     'fecomposite'=>'feComposite',
                                     'feconvolvematrix'=>'feConvolveMatrix',
                                     'fediffuselighting'=>'feDiffuseLighting',
                                     'fedisplacementmap'=>'feDisplacementMap',
                                     'fedistantlight'=>'feDistantLight',
                                     'feflood'=>'feFlood',
                                     'fefunca'=>'feFuncA',
                                     'fefuncb'=>'feFuncB',
                                     'fefuncg'=>'feFuncG',
                                     'fefuncr'=>'feFuncR',
                                     'fegaussianblur'=>'feGaussianBlur',
                                     'feimage'=>'feImage',
                                     'femerge'=>'feMerge',
                                     'femergenode'=>'feMergeNode',
                                     'femorphology'=>'feMorphology',
                                     'feoffset'=>'feOffset',
                                     'fepointlight'=>'fePointLight',
                                     'fespecularlighting'=>'feSpecularLighting',
                                     'fespotlight'=>'feSpotLight',
                                     'fetile'=>'feTile',
                                     'feturbulence'=>'feTurbulence',
                                     'foreignobject'=>'foreignObject',
                                     'glyphref'=>'glyphRef',
                                     'lineargradient'=>'linearGradient',
                                     'radialgradient'=>'radialGradient',
                                     'textpath'=>'textPath');

 # Used in the "in head" insertion mode. Done this way to make extending the class easier.
 protected static $headElements=array('base','basefont','bgsound','menuitem','link');
 protected static $rawtextHeadElements=array('noframes','style');
 protected static $rcdataHeadElements=array('title');

 # Used when pretty printing in HTML5::serialize().
 protected static $blockElements=array('address','article',
                                       'aside','blockquote',
                                       'body','canvas',
                                       'dd','dir','div','dl',
                                       'dt','fieldset',
                                       'figcaption','figure',
                                       'footer','form',
                                       'frame','frameset',
                                       'h1','h2','h3','h4',
                                       'h5','h6','head',
                                       'header','hgroup',
                                       'hr','html','li','main',
                                       'menu','nav','ol',
                                       'option','output','p',
                                       'pre','section','select',
                                       'source','table','tbody','td',
                                       'th','thead','tr','ul',
                                       '#document');

 # Used when pretty printing in HTML5::serialize().
 protected static $spacedBlockElements=array('address','article',
                                             'aside','blockquote',
                                             'body','canvas','dir',
                                             'div','dl','fieldset',
                                             'figure','footer',
                                             'form','frame',
                                             'frameset','h1','h2',
                                             'h3','h4','h5','h6',
                                             'head','header',
                                             'hgroup','hr','html','main',
                                             'menu','nav','ol','p',
                                             'pre','section','source','table','ul');

 # Used when pretty printing in HTML5::serialize();
 protected static $selfClosingElements=array('area','base','basefont','bgsound',
                                             'br','col','command','embed','frame',
                                             'hr','img','input','keygen','link',
                                             'meta','param','source','track','wbr');

 # Used when pretty printing in HTML5::serialize();
 protected static $preElements=array('pre','title');

 # Used when pretty printing in HTML5::serialize();
 protected static $scriptElements=array('script','style');

 # Used when pretty printing in HTML5::serialize();
 protected static $headBlockElements=array('script','style');

 # Controls the primary operation of the tree builder.
 protected static $mode='initial';

 protected static $entities=array('AElig'=>'Æ',
                                  'AElig;'=>'Æ',
                                  'AMP'=>'&',
                                  'AMP;'=>'&',
                                  'Aacute'=>'Á',
                                  'Aacute;'=>'Á',
                                  'Abreve;'=>'Ă',
                                  'Acirc'=>'Â',
                                  'Acirc;'=>'Â',
                                  'Acy;'=>'А',
                                  'Afr;'=>'프',
                                  'Agrave'=>'À',
                                  'Agrave;'=>'À',
                                  'Alpha;'=>'Α',
                                  'Amacr;'=>'Ā',
                                  'And;'=>'⩓',
                                  'Aogon;'=>'Ą',
                                  'Aopf;'=>'픸',
                                  'ApplyFunction;'=>'⁡',
                                  'Aring'=>'Å',
                                  'Aring;'=>'Å',
                                  'Ascr;'=>'풜',
                                  'Assign;'=>'≔',
                                  'Atilde'=>'Ã',
                                  'Atilde;'=>'Ã',
                                  'Auml'=>'Ä',
                                  'Auml;'=>'Ä',
                                  'Backslash;'=>'∖',
                                  'Barv;'=>'⫧',
                                  'Barwed;'=>'⌆',
                                  'Bcy;'=>'Б',
                                  'Because;'=>'∵',
                                  'Bernoullis;'=>'ℬ',
                                  'Beta;'=>'Β',
                                  'Bfr;'=>'픅',
                                  'Bopf;'=>'픹',
                                  'Breve;'=>'˘',
                                  'Bscr;'=>'ℬ',
                                  'Bumpeq;'=>'≎',
                                  'CHcy;'=>'Ч',
                                  'COPY'=>'©',
                                  'COPY;'=>'©',
                                  'Cacute;'=>'Ć',
                                  'Cap;'=>'⋒',
                                  'CapitalDifferentialD;'=>'ⅅ',
                                  'Cayleys;'=>'ℭ',
                                  'Ccaron;'=>'Č',
                                  'Ccedil'=>'Ç',
                                  'Ccedil;'=>'Ç',
                                  'Ccirc;'=>'Ĉ',
                                  'Cconint;'=>'∰',
                                  'Cdot;'=>'Ċ',
                                  'Cedilla;'=>'¸',
                                  'CenterDot;'=>'·',
                                  'Cfr;'=>'ℭ',
                                  'Chi;'=>'Χ',
                                  'CircleDot;'=>'⊙',
                                  'CircleMinus;'=>'⊖',
                                  'CirclePlus;'=>'⊕',
                                  'CircleTimes;'=>'⊗',
                                  'ClockwiseContourIntegral;'=>'∲',
                                  'CloseCurlyDoubleQuote;'=>'”',
                                  'CloseCurlyQuote;'=>'’',
                                  'Colon;'=>'∷',
                                  'Colone;'=>'⩴',
                                  'Congruent;'=>'≡',
                                  'Conint;'=>'∯',
                                  'ContourIntegral;'=>'∮',
                                  'Copf;'=>'ℂ',
                                  'Coproduct;'=>'∐',
                                  'CounterClockwiseContourIntegral;'=>'∳',
                                  'Cross;'=>'⨯',
                                  'Cscr;'=>'풞',
                                  'Cup;'=>'⋓',
                                  'CupCap;'=>'≍',
                                  'DD;'=>'ⅅ',
                                  'DDotrahd;'=>'⤑',
                                  'DJcy;'=>'Ђ',
                                  'DScy;'=>'Ѕ',
                                  'DZcy;'=>'Џ',
                                  'Dagger;'=>'‡',
                                  'Darr;'=>'↡',
                                  'Dashv;'=>'⫤',
                                  'Dcaron;'=>'Ď',
                                  'Dcy;'=>'Д',
                                  'Del;'=>'∇',
                                  'Delta;'=>'Δ',
                                  'Dfr;'=>'픇',
                                  'DiacriticalAcute;'=>'´',
                                  'DiacriticalDot;'=>'˙',
                                  'DiacriticalDoubleAcute;'=>'˝',
                                  'DiacriticalGrave;'=>'`',
                                  'DiacriticalTilde;'=>'˜',
                                  'Diamond;'=>'⋄',
                                  'DifferentialD;'=>'ⅆ',
                                  'Dopf;'=>'픻',
                                  'Dot;'=>'¨',
                                  'DotDot;'=>'⃜',
                                  'DotEqual;'=>'≐',
                                  'DoubleContourIntegral;'=>'∯',
                                  'DoubleDot;'=>'¨',
                                  'DoubleDownArrow;'=>'⇓',
                                  'DoubleLeftArrow;'=>'⇐',
                                  'DoubleLeftRightArrow;'=>'⇔',
                                  'DoubleLeftTee;'=>'⫤',
                                  'DoubleLongLeftArrow;'=>'⟸',
                                  'DoubleLongLeftRightArrow;'=>'⟺',
                                  'DoubleLongRightArrow;'=>'⟹',
                                  'DoubleRightArrow;'=>'⇒',
                                  'DoubleRightTee;'=>'⊨',
                                  'DoubleUpArrow;'=>'⇑',
                                  'DoubleUpDownArrow;'=>'⇕',
                                  'DoubleVerticalBar;'=>'∥',
                                  'DownArrow;'=>'↓',
                                  'DownArrowBar;'=>'⤓',
                                  'DownArrowUpArrow;'=>'⇵',
                                  'DownBreve;'=>'̑',
                                  'DownLeftRightVector;'=>'⥐',
                                  'DownLeftTeeVector;'=>'⥞',
                                  'DownLeftVector;'=>'↽',
                                  'DownLeftVectorBar;'=>'⥖',
                                  'DownRightTeeVector;'=>'⥟',
                                  'DownRightVector;'=>'⇁',
                                  'DownRightVectorBar;'=>'⥗',
                                  'DownTee;'=>'⊤',
                                  'DownTeeArrow;'=>'↧',
                                  'Downarrow;'=>'⇓',
                                  'Dscr;'=>'풟',
                                  'Dstrok;'=>'Đ',
                                  'ENG;'=>'Ŋ',
                                  'ETH'=>'Ð',
                                  'ETH;'=>'Ð',
                                  'Eacute'=>'É',
                                  'Eacute;'=>'É',
                                  'Ecaron;'=>'Ě',
                                  'Ecirc'=>'Ê',
                                  'Ecirc;'=>'Ê',
                                  'Ecy;'=>'Э',
                                  'Edot;'=>'Ė',
                                  'Efr;'=>'픈',
                                  'Egrave'=>'È',
                                  'Egrave;'=>'È',
                                  'Element;'=>'∈',
                                  'Emacr;'=>'Ē',
                                  'EmptySmallSquare;'=>'◻',
                                  'EmptyVerySmallSquare;'=>'▫',
                                  'Eogon;'=>'Ę',
                                  'Eopf;'=>'피',
                                  'Epsilon;'=>'Ε',
                                  'Equal;'=>'⩵',
                                  'EqualTilde;'=>'≂',
                                  'Equilibrium;'=>'⇌',
                                  'Escr;'=>'ℰ',
                                  'Esim;'=>'⩳',
                                  'Eta;'=>'Η',
                                  'Euml'=>'Ë',
                                  'Euml;'=>'Ë',
                                  'Exists;'=>'∃',
                                  'ExponentialE;'=>'ⅇ',
                                  'Fcy;'=>'Ф',
                                  'Ffr;'=>'픉',
                                  'FilledSmallSquare;'=>'◼',
                                  'FilledVerySmallSquare;'=>'▪',
                                  'Fopf;'=>'픽',
                                  'ForAll;'=>'∀',
                                  'Fouriertrf;'=>'ℱ',
                                  'Fscr;'=>'ℱ',
                                  'GJcy;'=>'Ѓ',
                                  'GT'=>'>',
                                  'GT;'=>'>',
                                  'Gamma;'=>'Γ',
                                  'Gammad;'=>'Ϝ',
                                  'Gbreve;'=>'Ğ',
                                  'Gcedil;'=>'Ģ',
                                  'Gcirc;'=>'Ĝ',
                                  'Gcy;'=>'Г',
                                  'Gdot;'=>'Ġ',
                                  'Gfr;'=>'픊',
                                  'Gg;'=>'⋙',
                                  'Gopf;'=>'픾',
                                  'GreaterEqual;'=>'≥',
                                  'GreaterEqualLess;'=>'⋛',
                                  'GreaterFullEqual;'=>'≧',
                                  'GreaterGreater;'=>'⪢',
                                  'GreaterLess;'=>'≷',
                                  'GreaterSlantEqual;'=>'⩾',
                                  'GreaterTilde;'=>'≳',
                                  'Gscr;'=>'풢',
                                  'Gt;'=>'≫',
                                  'HARDcy;'=>'Ъ',
                                  'Hacek;'=>'ˇ',
                                  'Hat;'=>'^',
                                  'Hcirc;'=>'Ĥ',
                                  'Hfr;'=>'ℌ',
                                  'HilbertSpace;'=>'ℋ',
                                  'Hopf;'=>'ℍ',
                                  'HorizontalLine;'=>'─',
                                  'Hscr;'=>'ℋ',
                                  'Hstrok;'=>'Ħ',
                                  'HumpDownHump;'=>'≎',
                                  'HumpEqual;'=>'≏',
                                  'IEcy;'=>'Е',
                                  'IJlig;'=>'Ĳ',
                                  'IOcy;'=>'Ё',
                                  'Iacute'=>'Í',
                                  'Iacute;'=>'Í',
                                  'Icirc'=>'Î',
                                  'Icirc;'=>'Î',
                                  'Icy;'=>'И',
                                  'Idot;'=>'İ',
                                  'Ifr;'=>'ℑ',
                                  'Igrave'=>'Ì',
                                  'Igrave;'=>'Ì',
                                  'Im;'=>'ℑ',
                                  'Imacr;'=>'Ī',
                                  'ImaginaryI;'=>'ⅈ',
                                  'Implies;'=>'⇒',
                                  'Int;'=>'∬',
                                  'Integral;'=>'∫',
                                  'Intersection;'=>'⋂',
                                  'InvisibleComma;'=>'⁣',
                                  'InvisibleTimes;'=>'⁢',
                                  'Iogon;'=>'Į',
                                  'Iopf;'=>'핀',
                                  'Iota;'=>'Ι',
                                  'Iscr;'=>'ℐ',
                                  'Itilde;'=>'Ĩ',
                                  'Iukcy;'=>'І',
                                  'Iuml'=>'Ï',
                                  'Iuml;'=>'Ï',
                                  'Jcirc;'=>'Ĵ',
                                  'Jcy;'=>'Й',
                                  'Jfr;'=>'픍',
                                  'Jopf;'=>'핁',
                                  'Jscr;'=>'풥',
                                  'Jsercy;'=>'Ј',
                                  'Jukcy;'=>'Є',
                                  'KHcy;'=>'Х',
                                  'KJcy;'=>'Ќ',
                                  'Kappa;'=>'Κ',
                                  'Kcedil;'=>'Ķ',
                                  'Kcy;'=>'К',
                                  'Kfr;'=>'픎',
                                  'Kopf;'=>'핂',
                                  'Kscr;'=>'풦',
                                  'LJcy;'=>'Љ',
                                  'LT'=>'<',
                                  'LT;'=>'<',
                                  'Lacute;'=>'Ĺ',
                                  'Lambda;'=>'Λ',
                                  'Lang;'=>'⟪',
                                  'Laplacetrf;'=>'ℒ',
                                  'Larr;'=>'↞',
                                  'Lcaron;'=>'Ľ',
                                  'Lcedil;'=>'Ļ',
                                  'Lcy;'=>'Л',
                                  'LeftAngleBracket;'=>'⟨',
                                  'LeftArrow;'=>'←',
                                  'LeftArrowBar;'=>'⇤',
                                  'LeftArrowRightArrow;'=>'⇆',
                                  'LeftCeiling;'=>'⌈',
                                  'LeftDoubleBracket;'=>'⟦',
                                  'LeftDownTeeVector;'=>'⥡',
                                  'LeftDownVector;'=>'⇃',
                                  'LeftDownVectorBar;'=>'⥙',
                                  'LeftFloor;'=>'⌊',
                                  'LeftRightArrow;'=>'↔',
                                  'LeftRightVector;'=>'⥎',
                                  'LeftTee;'=>'⊣',
                                  'LeftTeeArrow;'=>'↤',
                                  'LeftTeeVector;'=>'⥚',
                                  'LeftTriangle;'=>'⊲',
                                  'LeftTriangleBar;'=>'⧏',
                                  'LeftTriangleEqual;'=>'⊴',
                                  'LeftUpDownVector;'=>'⥑',
                                  'LeftUpTeeVector;'=>'⥠',
                                  'LeftUpVector;'=>'↿',
                                  'LeftUpVectorBar;'=>'⥘',
                                  'LeftVector;'=>'↼',
                                  'LeftVectorBar;'=>'⥒',
                                  'Leftarrow;'=>'⇐',
                                  'Leftrightarrow;'=>'⇔',
                                  'LessEqualGreater;'=>'⋚',
                                  'LessFullEqual;'=>'≦',
                                  'LessGreater;'=>'≶',
                                  'LessLess;'=>'⪡',
                                  'LessSlantEqual;'=>'⩽',
                                  'LessTilde;'=>'≲',
                                  'Lfr;'=>'픏',
                                  'Ll;'=>'⋘',
                                  'Lleftarrow;'=>'⇚',
                                  'Lmidot;'=>'Ŀ',
                                  'LongLeftArrow;'=>'⟵',
                                  'LongLeftRightArrow;'=>'⟷',
                                  'LongRightArrow;'=>'⟶',
                                  'Longleftarrow;'=>'⟸',
                                  'Longleftrightarrow;'=>'⟺',
                                  'Longrightarrow;'=>'⟹',
                                  'Lopf;'=>'핃',
                                  'LowerLeftArrow;'=>'↙',
                                  'LowerRightArrow;'=>'↘',
                                  'Lscr;'=>'ℒ',
                                  'Lsh;'=>'↰',
                                  'Lstrok;'=>'Ł',
                                  'Lt;'=>'≪',
                                  'Map;'=>'⤅',
                                  'Mcy;'=>'М',
                                  'MediumSpace;'=>' ',
                                  'Mellintrf;'=>'ℳ',
                                  'Mfr;'=>'픐',
                                  'MinusPlus;'=>'∓',
                                  'Mopf;'=>'필',
                                  'Mscr;'=>'ℳ',
                                  'Mu;'=>'Μ',
                                  'NJcy;'=>'Њ',
                                  'Nacute;'=>'Ń',
                                  'Ncaron;'=>'Ň',
                                  'Ncedil;'=>'Ņ',
                                  'Ncy;'=>'Н',
                                  'NegativeMediumSpace;'=>'​',
                                  'NegativeThickSpace;'=>'​',
                                  'NegativeThinSpace;'=>'​',
                                  'NegativeVeryThinSpace;'=>'​',
                                  'NestedGreaterGreater;'=>'≫',
                                  'NestedLessLess;'=>'≪',
                                  'NewLine;'=>'
                                  ','Nfr;'=>'픑',
                                  'NoBreak;'=>'⁠',
                                  'NonBreakingSpace;'=>' ',
                                  'Nopf;'=>'ℕ',
                                  'Not;'=>'⫬',
                                  'NotCongruent;'=>'≢',
                                  'NotCupCap;'=>'≭',
                                  'NotDoubleVerticalBar;'=>'∦',
                                  'NotElement;'=>'∉',
                                  'NotEqual;'=>'≠',
                                  'NotEqualTilde;'=>'≂̸',
                                  'NotExists;'=>'∄',
                                  'NotGreater;'=>'≯',
                                  'NotGreaterEqual;'=>'≱',
                                  'NotGreaterFullEqual;'=>'≧̸',
                                  'NotGreaterGreater;'=>'≫̸',
                                  'NotGreaterLess;'=>'≹',
                                  'NotGreaterSlantEqual;'=>'⩾̸',
                                  'NotGreaterTilde;'=>'≵',
                                  'NotHumpDownHump;'=>'≎̸',
                                  'NotHumpEqual;'=>'≏̸',
                                  'NotLeftTriangle;'=>'⋪',
                                  'NotLeftTriangleBar;'=>'⧏̸',
                                  'NotLeftTriangleEqual;'=>'⋬',
                                  'NotLess;'=>'≮',
                                  'NotLessEqual;'=>'≰',
                                  'NotLessGreater;'=>'≸',
                                  'NotLessLess;'=>'≪̸',
                                  'NotLessSlantEqual;'=>'⩽̸',
                                  'NotLessTilde;'=>'≴',
                                  'NotNestedGreaterGreater;'=>'⪢̸',
                                  'NotNestedLessLess;'=>'⪡̸',
                                  'NotPrecedes;'=>'⊀',
                                  'NotPrecedesEqual;'=>'⪯̸',
                                  'NotPrecedesSlantEqual;'=>'⋠',
                                  'NotReverseElement;'=>'∌',
                                  'NotRightTriangle;'=>'⋫',
                                  'NotRightTriangleBar;'=>'⧐̸',
                                  'NotRightTriangleEqual;'=>'⋭',
                                  'NotSquareSubset;'=>'⊏̸',
                                  'NotSquareSubsetEqual;'=>'⋢',
                                  'NotSquareSuperset;'=>'⊐̸',
                                  'NotSquareSupersetEqual;'=>'⋣',
                                  'NotSubset;'=>'⊂⃒',
                                  'NotSubsetEqual;'=>'⊈',
                                  'NotSucceeds;'=>'⊁',
                                  'NotSucceedsEqual;'=>'⪰̸',
                                  'NotSucceedsSlantEqual;'=>'⋡',
                                  'NotSucceedsTilde;'=>'≿̸',
                                  'NotSuperset;'=>'⊃⃒',
                                  'NotSupersetEqual;'=>'⊉',
                                  'NotTilde;'=>'≁',
                                  'NotTildeEqual;'=>'≄',
                                  'NotTildeFullEqual;'=>'≇',
                                  'NotTildeTilde;'=>'≉',
                                  'NotVerticalBar;'=>'∤',
                                  'Nscr;'=>'풩',
                                  'Ntilde'=>'Ñ',
                                  'Ntilde;'=>'Ñ',
                                  'Nu;'=>'Ν',
                                  'OElig;'=>'Œ',
                                  'Oacute'=>'Ó',
                                  'Oacute;'=>'Ó',
                                  'Ocirc'=>'Ô',
                                  'Ocirc;'=>'Ô',
                                  'Ocy;'=>'О',
                                  'Odblac;'=>'Ő',
                                  'Ofr;'=>'픒',
                                  'Ograve'=>'Ò',
                                  'Ograve;'=>'Ò',
                                  'Omacr;'=>'Ō',
                                  'Omega;'=>'Ω',
                                  'Omicron;'=>'Ο',
                                  'Oopf;'=>'핆',
                                  'OpenCurlyDoubleQuote;'=>'“',
                                  'OpenCurlyQuote;'=>'‘',
                                  'Or;'=>'⩔',
                                  'Oscr;'=>'풪',
                                  'Oslash'=>'Ø',
                                  'Oslash;'=>'Ø',
                                  'Otilde'=>'Õ',
                                  'Otilde;'=>'Õ',
                                  'Otimes;'=>'⨷',
                                  'Ouml'=>'Ö',
                                  'Ouml;'=>'Ö',
                                  'OverBar;'=>'‾',
                                  'OverBrace;'=>'⏞',
                                  'OverBracket;'=>'⎴',
                                  'OverParenthesis;'=>'⏜',
                                  'PartialD;'=>'∂',
                                  'Pcy;'=>'П',
                                  'Pfr;'=>'픓',
                                  'Phi;'=>'Φ',
                                  'Pi;'=>'Π',
                                  'PlusMinus;'=>'±',
                                  'Poincareplane;'=>'ℌ',
                                  'Popf;'=>'ℙ',
                                  'Pr;'=>'⪻',
                                  'Precedes;'=>'≺',
                                  'PrecedesEqual;'=>'⪯',
                                  'PrecedesSlantEqual;'=>'≼',
                                  'PrecedesTilde;'=>'≾',
                                  'Prime;'=>'″',
                                  'Product;'=>'∏',
                                  'Proportion;'=>'∷',
                                  'Proportional;'=>'∝',
                                  'Pscr;'=>'풫',
                                  'Psi;'=>'Ψ',
                                  'QUOT'=>'"',
                                  'QUOT;'=>'"',
                                  'Qfr;'=>'픔',
                                  'Qopf;'=>'ℚ',
                                  'Qscr;'=>'풬',
                                  'RBarr;'=>'⤐',
                                  'REG'=>'®',
                                  'REG;'=>'®',
                                  'Racute;'=>'Ŕ',
                                  'Rang;'=>'⟫',
                                  'Rarr;'=>'↠',
                                  'Rarrtl;'=>'⤖',
                                  'Rcaron;'=>'Ř',
                                  'Rcedil;'=>'Ŗ',
                                  'Rcy;'=>'Р',
                                  'Re;'=>'ℜ',
                                  'ReverseElement;'=>'∋',
                                  'ReverseEquilibrium;'=>'⇋',
                                  'ReverseUpEquilibrium;'=>'⥯',
                                  'Rfr;'=>'ℜ',
                                  'Rho;'=>'Ρ',
                                  'RightAngleBracket;'=>'⟩',
                                  'RightArrow;'=>'→',
                                  'RightArrowBar;'=>'⇥',
                                  'RightArrowLeftArrow;'=>'⇄',
                                  'RightCeiling;'=>'⌉',
                                  'RightDoubleBracket;'=>'⟧',
                                  'RightDownTeeVector;'=>'⥝',
                                  'RightDownVector;'=>'⇂',
                                  'RightDownVectorBar;'=>'⥕',
                                  'RightFloor;'=>'⌋',
                                  'RightTee;'=>'⊢',
                                  'RightTeeArrow;'=>'↦',
                                  'RightTeeVector;'=>'⥛',
                                  'RightTriangle;'=>'⊳',
                                  'RightTriangleBar;'=>'⧐',
                                  'RightTriangleEqual;'=>'⊵',
                                  'RightUpDownVector;'=>'⥏',
                                  'RightUpTeeVector;'=>'⥜',
                                  'RightUpVector;'=>'↾',
                                  'RightUpVectorBar;'=>'⥔',
                                  'RightVector;'=>'⇀',
                                  'RightVectorBar;'=>'⥓',
                                  'Rightarrow;'=>'⇒',
                                  'Ropf;'=>'ℝ',
                                  'RoundImplies;'=>'⥰',
                                  'Rrightarrow;'=>'⇛',
                                  'Rscr;'=>'ℛ',
                                  'Rsh;'=>'↱',
                                  'RuleDelayed;'=>'⧴',
                                  'SHCHcy;'=>'Щ',
                                  'SHcy;'=>'Ш',
                                  'SOFTcy;'=>'Ь',
                                  'Sacute;'=>'Ś',
                                  'Sc;'=>'⪼',
                                  'Scaron;'=>'Š',
                                  'Scedil;'=>'Ş',
                                  'Scirc;'=>'Ŝ',
                                  'Scy;'=>'С',
                                  'Sfr;'=>'픖',
                                  'ShortDownArrow;'=>'↓',
                                  'ShortLeftArrow;'=>'←',
                                  'ShortRightArrow;'=>'→',
                                  'ShortUpArrow;'=>'↑',
                                  'Sigma;'=>'Σ',
                                  'SmallCircle;'=>'∘',
                                  'Sopf;'=>'핊',
                                  'Sqrt;'=>'√',
                                  'Square;'=>'□',
                                  'SquareIntersection;'=>'⊓',
                                  'SquareSubset;'=>'⊏',
                                  'SquareSubsetEqual;'=>'⊑',
                                  'SquareSuperset;'=>'⊐',
                                  'SquareSupersetEqual;'=>'⊒',
                                  'SquareUnion;'=>'⊔',
                                  'Sscr;'=>'풮',
                                  'Star;'=>'⋆',
                                  'Sub;'=>'⋐',
                                  'Subset;'=>'⋐',
                                  'SubsetEqual;'=>'⊆',
                                  'Succeeds;'=>'≻',
                                  'SucceedsEqual;'=>'⪰',
                                  'SucceedsSlantEqual;'=>'≽',
                                  'SucceedsTilde;'=>'≿',
                                  'SuchThat;'=>'∋',
                                  'Sum;'=>'∑',
                                  'Sup;'=>'⋑',
                                  'Superset;'=>'⊃',
                                  'SupersetEqual;'=>'⊇',
                                  'Supset;'=>'⋑',
                                  'THORN'=>'Þ',
                                  'THORN;'=>'Þ',
                                  'TRADE;'=>'™',
                                  'TSHcy;'=>'Ћ',
                                  'TScy;'=>'Ц',
                                  'Tab;'=>'	',
                                  'Tau;'=>'Τ',
                                  'Tcaron;'=>'Ť',
                                  'Tcedil;'=>'Ţ',
                                  'Tcy;'=>'Т',
                                  'Tfr;'=>'픗',
                                  'Therefore;'=>'∴',
                                  'Theta;'=>'Θ',
                                  'ThickSpace;'=>'  ',
                                  'ThinSpace;'=>' ',
                                  'Tilde;'=>'∼',
                                  'TildeEqual;'=>'≃',
                                  'TildeFullEqual;'=>'≅',
                                  'TildeTilde;'=>'≈',
                                  'Topf;'=>'핋',
                                  'TripleDot;'=>'⃛',
                                  'Tscr;'=>'풯',
                                  'Tstrok;'=>'Ŧ',
                                  'Uacute'=>'Ú',
                                  'Uacute;'=>'Ú',
                                  'Uarr;'=>'↟',
                                  'Uarrocir;'=>'⥉',
                                  'Ubrcy;'=>'Ў',
                                  'Ubreve;'=>'Ŭ',
                                  'Ucirc'=>'Û',
                                  'Ucirc;'=>'Û',
                                  'Ucy;'=>'У',
                                  'Udblac;'=>'Ű',
                                  'Ufr;'=>'픘',
                                  'Ugrave'=>'Ù',
                                  'Ugrave;'=>'Ù',
                                  'Umacr;'=>'Ū',
                                  'UnderBar;'=>'_',
                                  'UnderBrace;'=>'⏟',
                                  'UnderBracket;'=>'⎵',
                                  'UnderParenthesis;'=>'⏝',
                                  'Union;'=>'⋃',
                                  'UnionPlus;'=>'⊎',
                                  'Uogon;'=>'Ų',
                                  'Uopf;'=>'핌',
                                  'UpArrow;'=>'↑',
                                  'UpArrowBar;'=>'⤒',
                                  'UpArrowDownArrow;'=>'⇅',
                                  'UpDownArrow;'=>'↕',
                                  'UpEquilibrium;'=>'⥮',
                                  'UpTee;'=>'⊥',
                                  'UpTeeArrow;'=>'↥',
                                  'Uparrow;'=>'⇑',
                                  'Updownarrow;'=>'⇕',
                                  'UpperLeftArrow;'=>'↖',
                                  'UpperRightArrow;'=>'↗',
                                  'Upsi;'=>'ϒ',
                                  'Upsilon;'=>'Υ',
                                  'Uring;'=>'Ů',
                                  'Uscr;'=>'풰',
                                  'Utilde;'=>'Ũ',
                                  'Uuml'=>'Ü',
                                  'Uuml;'=>'Ü',
                                  'VDash;'=>'⊫',
                                  'Vbar;'=>'⫫',
                                  'Vcy;'=>'В',
                                  'Vdash;'=>'⊩',
                                  'Vdashl;'=>'⫦',
                                  'Vee;'=>'⋁',
                                  'Verbar;'=>'‖',
                                  'Vert;'=>'‖',
                                  'VerticalBar;'=>'∣',
                                  'VerticalLine;'=>'|',
                                  'VerticalSeparator;'=>'❘',
                                  'VerticalTilde;'=>'≀',
                                  'VeryThinSpace;'=>' ',
                                  'Vfr;'=>'픙',
                                  'Vopf;'=>'핍',
                                  'Vscr;'=>'풱',
                                  'Vvdash;'=>'⊪',
                                  'Wcirc;'=>'Ŵ',
                                  'Wedge;'=>'⋀',
                                  'Wfr;'=>'픚',
                                  'Wopf;'=>'핎',
                                  'Wscr;'=>'풲',
                                  'Xfr;'=>'픛',
                                  'Xi;'=>'Ξ',
                                  'Xopf;'=>'핏',
                                  'Xscr;'=>'풳',
                                  'YAcy;'=>'Я',
                                  'YIcy;'=>'Ї',
                                  'YUcy;'=>'Ю',
                                  'Yacute'=>'Ý',
                                  'Yacute;'=>'Ý',
                                  'Ycirc;'=>'Ŷ',
                                  'Ycy;'=>'Ы',
                                  'Yfr;'=>'픜',
                                  'Yopf;'=>'핐',
                                  'Yscr;'=>'풴',
                                  'Yuml;'=>'Ÿ',
                                  'ZHcy;'=>'Ж',
                                  'Zacute;'=>'Ź',
                                  'Zcaron;'=>'Ž',
                                  'Zcy;'=>'З',
                                  'Zdot;'=>'Ż',
                                  'ZeroWidthSpace;'=>'​',
                                  'Zeta;'=>'Ζ',
                                  'Zfr;'=>'ℨ',
                                  'Zopf;'=>'ℤ',
                                  'Zscr;'=>'풵',
                                  'aacute'=>'á',
                                  'aacute;'=>'á',
                                  'abreve;'=>'ă',
                                  'ac;'=>'∾',
                                  'acE;'=>'∾̳',
                                  'acd;'=>'∿',
                                  'acirc'=>'â',
                                  'acirc;'=>'â',
                                  'acute'=>'´',
                                  'acute;'=>'´',
                                  'acy;'=>'а',
                                  'aelig'=>'æ',
                                  'aelig;'=>'æ',
                                  'af;'=>'⁡',
                                  'afr;'=>'픞',
                                  'agrave'=>'à',
                                  'agrave;'=>'à',
                                  'alefsym;'=>'ℵ',
                                  'aleph;'=>'ℵ',
                                  'alpha;'=>'α',
                                  'amacr;'=>'ā',
                                  'amalg;'=>'⨿',
                                  'amp'=>'&',
                                  'amp;'=>'&',
                                  'and;'=>'∧',
                                  'andand;'=>'⩕',
                                  'andd;'=>'⩜',
                                  'andslope;'=>'⩘',
                                  'andv;'=>'⩚',
                                  'ang;'=>'∠',
                                  'ange;'=>'⦤',
                                  'angle;'=>'∠',
                                  'angmsd;'=>'∡',
                                  'angmsdaa;'=>'⦨',
                                  'angmsdab;'=>'⦩',
                                  'angmsdac;'=>'⦪',
                                  'angmsdad;'=>'⦫',
                                  'angmsdae;'=>'⦬',
                                  'angmsdaf;'=>'⦭',
                                  'angmsdag;'=>'⦮',
                                  'angmsdah;'=>'⦯',
                                  'angrt;'=>'∟',
                                  'angrtvb;'=>'⊾',
                                  'angrtvbd;'=>'⦝',
                                  'angsph;'=>'∢',
                                  'angst;'=>'Å',
                                  'angzarr;'=>'⍼',
                                  'aogon;'=>'ą',
                                  'aopf;'=>'핒',
                                  'ap;'=>'≈',
                                  'apE;'=>'⩰',
                                  'apacir;'=>'⩯',
                                  'ape;'=>'≊',
                                  'apid;'=>'≋',
                                  'apos;'=>'\'',
                                  'approx;'=>'≈',
                                  'approxeq;'=>'≊',
                                  'aring'=>'å',
                                  'aring;'=>'å',
                                  'ascr;'=>'풶',
                                  'ast;'=>'*',
                                  'asymp;'=>'≈',
                                  'asympeq;'=>'≍',
                                  'atilde'=>'ã',
                                  'atilde;'=>'ã',
                                  'auml'=>'ä',
                                  'auml;'=>'ä',
                                  'awconint;'=>'∳',
                                  'awint;'=>'⨑',
                                  'bNot;'=>'⫭',
                                  'backcong;'=>'≌',
                                  'backepsilon;'=>'϶',
                                  'backprime;'=>'‵',
                                  'backsim;'=>'∽',
                                  'backsimeq;'=>'⋍',
                                  'barvee;'=>'⊽',
                                  'barwed;'=>'⌅',
                                  'barwedge;'=>'⌅',
                                  'bbrk;'=>'⎵',
                                  'bbrktbrk;'=>'⎶',
                                  'bcong;'=>'≌',
                                  'bcy;'=>'б',
                                  'bdquo;'=>'„',
                                  'becaus;'=>'∵',
                                  'because;'=>'∵',
                                  'bemptyv;'=>'⦰',
                                  'bepsi;'=>'϶',
                                  'bernou;'=>'ℬ',
                                  'beta;'=>'β',
                                  'beth;'=>'ℶ',
                                  'between;'=>'≬',
                                  'bfr;'=>'픟',
                                  'bigcap;'=>'⋂',
                                  'bigcirc;'=>'◯',
                                  'bigcup;'=>'⋃',
                                  'bigodot;'=>'⨀',
                                  'bigoplus;'=>'⨁',
                                  'bigotimes;'=>'⨂',
                                  'bigsqcup;'=>'⨆',
                                  'bigstar;'=>'★',
                                  'bigtriangledown;'=>'▽',
                                  'bigtriangleup;'=>'△',
                                  'biguplus;'=>'⨄',
                                  'bigvee;'=>'⋁',
                                  'bigwedge;'=>'⋀',
                                  'bkarow;'=>'⤍',
                                  'blacklozenge;'=>'⧫',
                                  'blacksquare;'=>'▪',
                                  'blacktriangle;'=>'▴',
                                  'blacktriangledown;'=>'▾',
                                  'blacktriangleleft;'=>'◂',
                                  'blacktriangleright;'=>'▸',
                                  'blank;'=>'␣',
                                  'blk12;'=>'▒',
                                  'blk14;'=>'░',
                                  'blk34;'=>'▓',
                                  'block;'=>'█',
                                  'bne;'=>'=⃥',
                                  'bnequiv;'=>'≡⃥',
                                  'bnot;'=>'⌐',
                                  'bopf;'=>'핓',
                                  'bot;'=>'⊥',
                                  'bottom;'=>'⊥',
                                  'bowtie;'=>'⋈',
                                  'boxDL;'=>'╗',
                                  'boxDR;'=>'╔',
                                  'boxDl;'=>'╖',
                                  'boxDr;'=>'╓',
                                  'boxH;'=>'═',
                                  'boxHD;'=>'╦',
                                  'boxHU;'=>'╩',
                                  'boxHd;'=>'╤',
                                  'boxHu;'=>'╧',
                                  'boxUL;'=>'╝',
                                  'boxUR;'=>'╚',
                                  'boxUl;'=>'╜',
                                  'boxUr;'=>'╙',
                                  'boxV;'=>'║',
                                  'boxVH;'=>'╬',
                                  'boxVL;'=>'╣',
                                  'boxVR;'=>'╠',
                                  'boxVh;'=>'╫',
                                  'boxVl;'=>'╢',
                                  'boxVr;'=>'╟',
                                  'boxbox;'=>'⧉',
                                  'boxdL;'=>'╕',
                                  'boxdR;'=>'╒',
                                  'boxdl;'=>'┐',
                                  'boxdr;'=>'┌',
                                  'boxh;'=>'─',
                                  'boxhD;'=>'╥',
                                  'boxhU;'=>'╨',
                                  'boxhd;'=>'┬',
                                  'boxhu;'=>'┴',
                                  'boxminus;'=>'⊟',
                                  'boxplus;'=>'⊞',
                                  'boxtimes;'=>'⊠',
                                  'boxuL;'=>'╛',
                                  'boxuR;'=>'╘',
                                  'boxul;'=>'┘',
                                  'boxur;'=>'└',
                                  'boxv;'=>'│',
                                  'boxvH;'=>'╪',
                                  'boxvL;'=>'╡',
                                  'boxvR;'=>'╞',
                                  'boxvh;'=>'┼',
                                  'boxvl;'=>'┤',
                                  'boxvr;'=>'├',
                                  'bprime;'=>'‵',
                                  'breve;'=>'˘',
                                  'brvbar'=>'¦',
                                  'brvbar;'=>'¦',
                                  'bscr;'=>'풷',
                                  'bsemi;'=>'⁏',
                                  'bsim;'=>'∽',
                                  'bsime;'=>'⋍',
                                  'bsol;'=>'\\',
                                  'bsolb;'=>'⧅',
                                  'bsolhsub;'=>'⟈',
                                  'bull;'=>'•',
                                  'bullet;'=>'•',
                                  'bump;'=>'≎',
                                  'bumpE;'=>'⪮',
                                  'bumpe;'=>'≏',
                                  'bumpeq;'=>'≏',
                                  'cacute;'=>'ć',
                                  'cap;'=>'∩',
                                  'capand;'=>'⩄',
                                  'capbrcup;'=>'⩉',
                                  'capcap;'=>'⩋',
                                  'capcup;'=>'⩇',
                                  'capdot;'=>'⩀',
                                  'caps;'=>'∩︀',
                                  'caret;'=>'⁁',
                                  'caron;'=>'ˇ',
                                  'ccaps;'=>'⩍',
                                  'ccaron;'=>'č',
                                  'ccedil'=>'ç',
                                  'ccedil;'=>'ç',
                                  'ccirc;'=>'ĉ',
                                  'ccups;'=>'⩌',
                                  'ccupssm;'=>'⩐',
                                  'cdot;'=>'ċ',
                                  'cedil'=>'¸',
                                  'cedil;'=>'¸',
                                  'cemptyv;'=>'⦲',
                                  'cent'=>'¢',
                                  'cent;'=>'¢',
                                  'centerdot;'=>'·',
                                  'cfr;'=>'픠',
                                  'chcy;'=>'ч',
                                  'check;'=>'✓',
                                  'checkmark;'=>'✓',
                                  'chi;'=>'χ',
                                  'cir;'=>'○',
                                  'cirE;'=>'⧃',
                                  'circ;'=>'ˆ',
                                  'circeq;'=>'≗',
                                  'circlearrowleft;'=>'↺',
                                  'circlearrowright;'=>'↻',
                                  'circledR;'=>'®',
                                  'circledS;'=>'Ⓢ',
                                  'circledast;'=>'⊛',
                                  'circledcirc;'=>'⊚',
                                  'circleddash;'=>'⊝',
                                  'cire;'=>'≗',
                                  'cirfnint;'=>'⨐',
                                  'cirmid;'=>'⫯',
                                  'cirscir;'=>'⧂',
                                  'clubs;'=>'♣',
                                  'clubsuit;'=>'♣',
                                  'colon;'=>':',
                                  'colone;'=>'≔',
                                  'coloneq;'=>'≔',
                                  'comma;'=>',',
                                  'commat;'=>'@',
                                  'comp;'=>'∁',
                                  'compfn;'=>'∘',
                                  'complement;'=>'∁',
                                  'complexes;'=>'ℂ',
                                  'cong;'=>'≅',
                                  'congdot;'=>'⩭',
                                  'conint;'=>'∮',
                                  'copf;'=>'핔',
                                  'coprod;'=>'∐',
                                  'copy'=>'©',
                                  'copy;'=>'©',
                                  'copysr;'=>'℗',
                                  'crarr;'=>'↵',
                                  'cross;'=>'✗',
                                  'cscr;'=>'풸',
                                  'csub;'=>'⫏',
                                  'csube;'=>'⫑',
                                  'csup;'=>'⫐',
                                  'csupe;'=>'⫒',
                                  'ctdot;'=>'⋯',
                                  'cudarrl;'=>'⤸',
                                  'cudarrr;'=>'⤵',
                                  'cuepr;'=>'⋞',
                                  'cuesc;'=>'⋟',
                                  'cularr;'=>'↶',
                                  'cularrp;'=>'⤽',
                                  'cup;'=>'∪',
                                  'cupbrcap;'=>'⩈',
                                  'cupcap;'=>'⩆',
                                  'cupcup;'=>'⩊',
                                  'cupdot;'=>'⊍',
                                  'cupor;'=>'⩅',
                                  'cups;'=>'∪︀',
                                  'curarr;'=>'↷',
                                  'curarrm;'=>'⤼',
                                  'curlyeqprec;'=>'⋞',
                                  'curlyeqsucc;'=>'⋟',
                                  'curlyvee;'=>'⋎',
                                  'curlywedge;'=>'⋏',
                                  'curren'=>'¤',
                                  'curren;'=>'¤',
                                  'curvearrowleft;'=>'↶',
                                  'curvearrowright;'=>'↷',
                                  'cuvee;'=>'⋎',
                                  'cuwed;'=>'⋏',
                                  'cwconint;'=>'∲',
                                  'cwint;'=>'∱',
                                  'cylcty;'=>'⌭',
                                  'dArr;'=>'⇓',
                                  'dHar;'=>'⥥',
                                  'dagger;'=>'†',
                                  'daleth;'=>'ℸ',
                                  'darr;'=>'↓',
                                  'dash;'=>'‐',
                                  'dashv;'=>'⊣',
                                  'dbkarow;'=>'⤏',
                                  'dblac;'=>'˝',
                                  'dcaron;'=>'ď',
                                  'dcy;'=>'д',
                                  'dd;'=>'ⅆ',
                                  'ddagger;'=>'‡',
                                  'ddarr;'=>'⇊',
                                  'ddotseq;'=>'⩷',
                                  'deg'=>'°',
                                  'deg;'=>'°',
                                  'delta;'=>'δ',
                                  'demptyv;'=>'⦱',
                                  'dfisht;'=>'⥿',
                                  'dfr;'=>'픡',
                                  'dharl;'=>'⇃',
                                  'dharr;'=>'⇂',
                                  'diam;'=>'⋄',
                                  'diamond;'=>'⋄',
                                  'diamondsuit;'=>'♦',
                                  'diams;'=>'♦',
                                  'die;'=>'¨',
                                  'digamma;'=>'ϝ',
                                  'disin;'=>'⋲',
                                  'div;'=>'÷',
                                  'divide'=>'÷',
                                  'divide;'=>'÷',
                                  'divideontimes;'=>'⋇',
                                  'divonx;'=>'⋇',
                                  'djcy;'=>'ђ',
                                  'dlcorn;'=>'⌞',
                                  'dlcrop;'=>'⌍',
                                  'dollar;'=>'$',
                                  'dopf;'=>'핕',
                                  'dot;'=>'˙',
                                  'doteq;'=>'≐',
                                  'doteqdot;'=>'≑',
                                  'dotminus;'=>'∸',
                                  'dotplus;'=>'∔',
                                  'dotsquare;'=>'⊡',
                                  'doublebarwedge;'=>'⌆',
                                  'downarrow;'=>'↓',
                                  'downdownarrows;'=>'⇊',
                                  'downharpoonleft;'=>'⇃',
                                  'downharpoonright;'=>'⇂',
                                  'drbkarow;'=>'⤐',
                                  'drcorn;'=>'⌟',
                                  'drcrop;'=>'⌌',
                                  'dscr;'=>'풹',
                                  'dscy;'=>'ѕ',
                                  'dsol;'=>'⧶',
                                  'dstrok;'=>'đ',
                                  'dtdot;'=>'⋱',
                                  'dtri;'=>'▿',
                                  'dtrif;'=>'▾',
                                  'duarr;'=>'⇵',
                                  'duhar;'=>'⥯',
                                  'dwangle;'=>'⦦',
                                  'dzcy;'=>'џ',
                                  'dzigrarr;'=>'⟿',
                                  'eDDot;'=>'⩷',
                                  'eDot;'=>'≑',
                                  'eacute'=>'é',
                                  'eacute;'=>'é',
                                  'easter;'=>'⩮',
                                  'ecaron;'=>'ě',
                                  'ecir;'=>'≖',
                                  'ecirc'=>'ê',
                                  'ecirc;'=>'ê',
                                  'ecolon;'=>'≕',
                                  'ecy;'=>'э',
                                  'edot;'=>'ė',
                                  'ee;'=>'ⅇ',
                                  'efDot;'=>'≒',
                                  'efr;'=>'픢',
                                  'eg;'=>'⪚',
                                  'egrave'=>'è',
                                  'egrave;'=>'è',
                                  'egs;'=>'⪖',
                                  'egsdot;'=>'⪘',
                                  'el;'=>'⪙',
                                  'elinters;'=>'⏧',
                                  'ell;'=>'ℓ',
                                  'els;'=>'⪕',
                                  'elsdot;'=>'⪗',
                                  'emacr;'=>'ē',
                                  'empty;'=>'∅',
                                  'emptyset;'=>'∅',
                                  'emptyv;'=>'∅',
                                  'emsp13;'=>' ',
                                  'emsp14;'=>' ',
                                  'emsp;'=>' ',
                                  'eng;'=>'ŋ',
                                  'ensp;'=>' ',
                                  'eogon;'=>'ę',
                                  'eopf;'=>'핖',
                                  'epar;'=>'⋕',
                                  'eparsl;'=>'⧣',
                                  'eplus;'=>'⩱',
                                  'epsi;'=>'ε',
                                  'epsilon;'=>'ε',
                                  'epsiv;'=>'ϵ',
                                  'eqcirc;'=>'≖',
                                  'eqcolon;'=>'≕',
                                  'eqsim;'=>'≂',
                                  'eqslantgtr;'=>'⪖',
                                  'eqslantless;'=>'⪕',
                                  'equals;'=>'=',
                                  'equest;'=>'≟',
                                  'equiv;'=>'≡',
                                  'equivDD;'=>'⩸',
                                  'eqvparsl;'=>'⧥',
                                  'erDot;'=>'≓',
                                  'erarr;'=>'⥱',
                                  'escr;'=>'ℯ',
                                  'esdot;'=>'≐',
                                  'esim;'=>'≂',
                                  'eta;'=>'η',
                                  'eth'=>'ð',
                                  'eth;'=>'ð',
                                  'euml'=>'ë',
                                  'euml;'=>'ë',
                                  'euro;'=>'€',
                                  'excl;'=>'!',
                                  'exist;'=>'∃',
                                  'expectation;'=>'ℰ',
                                  'exponentiale;'=>'ⅇ',
                                  'fallingdotseq;'=>'≒',
                                  'fcy;'=>'ф',
                                  'female;'=>'♀',
                                  'ffilig;'=>'ﬃ',
                                  'fflig;'=>'ﬀ',
                                  'ffllig;'=>'ﬄ',
                                  'ffr;'=>'픣',
                                  'filig;'=>'ﬁ',
                                  'fjlig;'=>'fj',
                                  'flat;'=>'♭',
                                  'fllig;'=>'ﬂ',
                                  'fltns;'=>'▱',
                                  'fnof;'=>'ƒ',
                                  'fopf;'=>'핗',
                                  'forall;'=>'∀',
                                  'fork;'=>'⋔',
                                  'forkv;'=>'⫙',
                                  'fpartint;'=>'⨍',
                                  'frac12'=>'½',
                                  'frac12;'=>'½',
                                  'frac13;'=>'⅓',
                                  'frac14'=>'¼',
                                  'frac14;'=>'¼',
                                  'frac15;'=>'⅕',
                                  'frac16;'=>'⅙',
                                  'frac18;'=>'⅛',
                                  'frac23;'=>'⅔',
                                  'frac25;'=>'⅖',
                                  'frac34'=>'¾',
                                  'frac34;'=>'¾',
                                  'frac35;'=>'⅗',
                                  'frac38;'=>'⅜',
                                  'frac45;'=>'⅘',
                                  'frac56;'=>'⅚',
                                  'frac58;'=>'⅝',
                                  'frac78;'=>'⅞',
                                  'frasl;'=>'⁄',
                                  'frown;'=>'⌢',
                                  'fscr;'=>'풻',
                                  'gE;'=>'≧',
                                  'gEl;'=>'⪌',
                                  'gacute;'=>'ǵ',
                                  'gamma;'=>'γ',
                                  'gammad;'=>'ϝ',
                                  'gap;'=>'⪆',
                                  'gbreve;'=>'ğ',
                                  'gcirc;'=>'ĝ',
                                  'gcy;'=>'г',
                                  'gdot;'=>'ġ',
                                  'ge;'=>'≥',
                                  'gel;'=>'⋛',
                                  'geq;'=>'≥',
                                  'geqq;'=>'≧',
                                  'geqslant;'=>'⩾',
                                  'ges;'=>'⩾',
                                  'gescc;'=>'⪩',
                                  'gesdot;'=>'⪀',
                                  'gesdoto;'=>'⪂',
                                  'gesdotol;'=>'⪄',
                                  'gesl;'=>'⋛︀',
                                  'gesles;'=>'⪔',
                                  'gfr;'=>'픤',
                                  'gg;'=>'≫',
                                  'ggg;'=>'⋙',
                                  'gimel;'=>'ℷ',
                                  'gjcy;'=>'ѓ',
                                  'gl;'=>'≷',
                                  'glE;'=>'⪒',
                                  'gla;'=>'⪥',
                                  'glj;'=>'⪤',
                                  'gnE;'=>'≩',
                                  'gnap;'=>'⪊',
                                  'gnapprox;'=>'⪊',
                                  'gne;'=>'⪈',
                                  'gneq;'=>'⪈',
                                  'gneqq;'=>'≩',
                                  'gnsim;'=>'⋧',
                                  'gopf;'=>'하',
                                  'grave;'=>'`',
                                  'gscr;'=>'ℊ',
                                  'gsim;'=>'≳',
                                  'gsime;'=>'⪎',
                                  'gsiml;'=>'⪐',
                                  'gt'=>'>',
                                  'gt;'=>'>',
                                  'gtcc;'=>'⪧',
                                  'gtcir;'=>'⩺',
                                  'gtdot;'=>'⋗',
                                  'gtlPar;'=>'⦕',
                                  'gtquest;'=>'⩼',
                                  'gtrapprox;'=>'⪆',
                                  'gtrarr;'=>'⥸',
                                  'gtrdot;'=>'⋗',
                                  'gtreqless;'=>'⋛',
                                  'gtreqqless;'=>'⪌',
                                  'gtrless;'=>'≷',
                                  'gtrsim;'=>'≳',
                                  'gvertneqq;'=>'≩︀',
                                  'gvnE;'=>'≩︀',
                                  'hArr;'=>'⇔',
                                  'hairsp;'=>' ',
                                  'half;'=>'½',
                                  'hamilt;'=>'ℋ',
                                  'hardcy;'=>'ъ',
                                  'harr;'=>'↔',
                                  'harrcir;'=>'⥈',
                                  'harrw;'=>'↭',
                                  'hbar;'=>'ℏ',
                                  'hcirc;'=>'ĥ',
                                  'hearts;'=>'♥',
                                  'heartsuit;'=>'♥',
                                  'hellip;'=>'…',
                                  'hercon;'=>'⊹',
                                  'hfr;'=>'픥',
                                  'hksearow;'=>'⤥',
                                  'hkswarow;'=>'⤦',
                                  'hoarr;'=>'⇿',
                                  'homtht;'=>'∻',
                                  'hookleftarrow;'=>'↩',
                                  'hookrightarrow;'=>'↪',
                                  'hopf;'=>'학',
                                  'horbar;'=>'―',
                                  'hscr;'=>'풽',
                                  'hslash;'=>'ℏ',
                                  'hstrok;'=>'ħ',
                                  'hybull;'=>'⁃',
                                  'hyphen;'=>'‐',
                                  'iacute'=>'í',
                                  'iacute;'=>'í',
                                  'ic;'=>'⁣',
                                  'icirc'=>'î',
                                  'icirc;'=>'î',
                                  'icy;'=>'и',
                                  'iecy;'=>'е',
                                  'iexcl'=>'¡',
                                  'iexcl;'=>'¡',
                                  'iff;'=>'⇔',
                                  'ifr;'=>'픦',
                                  'igrave'=>'ì',
                                  'igrave;'=>'ì',
                                  'ii;'=>'ⅈ',
                                  'iiiint;'=>'⨌',
                                  'iiint;'=>'∭',
                                  'iinfin;'=>'⧜',
                                  'iiota;'=>'℩',
                                  'ijlig;'=>'ĳ',
                                  'imacr;'=>'ī',
                                  'image;'=>'ℑ',
                                  'imagline;'=>'ℐ',
                                  'imagpart;'=>'ℑ',
                                  'imath;'=>'ı',
                                  'imof;'=>'⊷',
                                  'imped;'=>'Ƶ',
                                  'in;'=>'∈',
                                  'incare;'=>'℅',
                                  'infin;'=>'∞',
                                  'infintie;'=>'⧝',
                                  'inodot;'=>'ı',
                                  'int;'=>'∫',
                                  'intcal;'=>'⊺',
                                  'integers;'=>'ℤ',
                                  'intercal;'=>'⊺',
                                  'intlarhk;'=>'⨗',
                                  'intprod;'=>'⨼',
                                  'iocy;'=>'ё',
                                  'iogon;'=>'į',
                                  'iopf;'=>'핚',
                                  'iota;'=>'ι',
                                  'iprod;'=>'⨼',
                                  'iquest'=>'¿',
                                  'iquest;'=>'¿',
                                  'iscr;'=>'풾',
                                  'isin;'=>'∈',
                                  'isinE;'=>'⋹',
                                  'isindot;'=>'⋵',
                                  'isins;'=>'⋴',
                                  'isinsv;'=>'⋳',
                                  'isinv;'=>'∈',
                                  'it;'=>'⁢',
                                  'itilde;'=>'ĩ',
                                  'iukcy;'=>'і',
                                  'iuml'=>'ï',
                                  'iuml;'=>'ï',
                                  'jcirc;'=>'ĵ',
                                  'jcy;'=>'й',
                                  'jfr;'=>'픧',
                                  'jmath;'=>'ȷ',
                                  'jopf;'=>'핛',
                                  'jscr;'=>'풿',
                                  'jsercy;'=>'ј',
                                  'jukcy;'=>'є',
                                  'kappa;'=>'κ',
                                  'kappav;'=>'ϰ',
                                  'kcedil;'=>'ķ',
                                  'kcy;'=>'к',
                                  'kfr;'=>'픨',
                                  'kgreen;'=>'ĸ',
                                  'khcy;'=>'х',
                                  'kjcy;'=>'ќ',
                                  'kopf;'=>'한',
                                  'kscr;'=>'퓀',
                                  'lAarr;'=>'⇚',
                                  'lArr;'=>'⇐',
                                  'lAtail;'=>'⤛',
                                  'lBarr;'=>'⤎',
                                  'lE;'=>'≦',
                                  'lEg;'=>'⪋',
                                  'lHar;'=>'⥢',
                                  'lacute;'=>'ĺ',
                                  'laemptyv;'=>'⦴',
                                  'lagran;'=>'ℒ',
                                  'lambda;'=>'λ',
                                  'lang;'=>'⟨',
                                  'langd;'=>'⦑',
                                  'langle;'=>'⟨',
                                  'lap;'=>'⪅',
                                  'laquo'=>'«',
                                  'laquo;'=>'«',
                                  'larr;'=>'←',
                                  'larrb;'=>'⇤',
                                  'larrbfs;'=>'⤟',
                                  'larrfs;'=>'⤝',
                                  'larrhk;'=>'↩',
                                  'larrlp;'=>'↫',
                                  'larrpl;'=>'⤹',
                                  'larrsim;'=>'⥳',
                                  'larrtl;'=>'↢',
                                  'lat;'=>'⪫',
                                  'latail;'=>'⤙',
                                  'late;'=>'⪭',
                                  'lates;'=>'⪭︀',
                                  'lbarr;'=>'⤌',
                                  'lbbrk;'=>'❲',
                                  'lbrace;'=>'{',
                                  'lbrack;'=>'[',
                                  'lbrke;'=>'⦋',
                                  'lbrksld;'=>'⦏',
                                  'lbrkslu;'=>'⦍',
                                  'lcaron;'=>'ľ',
                                  'lcedil;'=>'ļ',
                                  'lceil;'=>'⌈',
                                  'lcub;'=>'{',
                                  'lcy;'=>'л',
                                  'ldca;'=>'⤶',
                                  'ldquo;'=>'“',
                                  'ldquor;'=>'„',
                                  'ldrdhar;'=>'⥧',
                                  'ldrushar;'=>'⥋',
                                  'ldsh;'=>'↲',
                                  'le;'=>'≤',
                                  'leftarrow;'=>'←',
                                  'leftarrowtail;'=>'↢',
                                  'leftharpoondown;'=>'↽',
                                  'leftharpoonup;'=>'↼',
                                  'leftleftarrows;'=>'⇇',
                                  'leftrightarrow;'=>'↔',
                                  'leftrightarrows;'=>'⇆',
                                  'leftrightharpoons;'=>'⇋',
                                  'leftrightsquigarrow;'=>'↭',
                                  'leftthreetimes;'=>'⋋',
                                  'leg;'=>'⋚',
                                  'leq;'=>'≤',
                                  'leqq;'=>'≦',
                                  'leqslant;'=>'⩽',
                                  'les;'=>'⩽',
                                  'lescc;'=>'⪨',
                                  'lesdot;'=>'⩿',
                                  'lesdoto;'=>'⪁',
                                  'lesdotor;'=>'⪃',
                                  'lesg;'=>'⋚︀',
                                  'lesges;'=>'⪓',
                                  'lessapprox;'=>'⪅',
                                  'lessdot;'=>'⋖',
                                  'lesseqgtr;'=>'⋚',
                                  'lesseqqgtr;'=>'⪋',
                                  'lessgtr;'=>'≶',
                                  'lesssim;'=>'≲',
                                  'lfisht;'=>'⥼',
                                  'lfloor;'=>'⌊',
                                  'lfr;'=>'픩',
                                  'lg;'=>'≶',
                                  'lgE;'=>'⪑',
                                  'lhard;'=>'↽',
                                  'lharu;'=>'↼',
                                  'lharul;'=>'⥪',
                                  'lhblk;'=>'▄',
                                  'ljcy;'=>'љ',
                                  'll;'=>'≪',
                                  'llarr;'=>'⇇',
                                  'llcorner;'=>'⌞',
                                  'llhard;'=>'⥫',
                                  'lltri;'=>'◺',
                                  'lmidot;'=>'ŀ',
                                  'lmoust;'=>'⎰',
                                  'lmoustache;'=>'⎰',
                                  'lnE;'=>'≨',
                                  'lnap;'=>'⪉',
                                  'lnapprox;'=>'⪉',
                                  'lne;'=>'⪇',
                                  'lneq;'=>'⪇',
                                  'lneqq;'=>'≨',
                                  'lnsim;'=>'⋦',
                                  'loang;'=>'⟬',
                                  'loarr;'=>'⇽',
                                  'lobrk;'=>'⟦',
                                  'longleftarrow;'=>'⟵',
                                  'longleftrightarrow;'=>'⟷',
                                  'longmapsto;'=>'⟼',
                                  'longrightarrow;'=>'⟶',
                                  'looparrowleft;'=>'↫',
                                  'looparrowright;'=>'↬',
                                  'lopar;'=>'⦅',
                                  'lopf;'=>'핝',
                                  'loplus;'=>'⨭',
                                  'lotimes;'=>'⨴',
                                  'lowast;'=>'∗',
                                  'lowbar;'=>'_',
                                  'loz;'=>'◊',
                                  'lozenge;'=>'◊',
                                  'lozf;'=>'⧫',
                                  'lpar;'=>'(',
                                  'lparlt;'=>'⦓',
                                  'lrarr;'=>'⇆',
                                  'lrcorner;'=>'⌟',
                                  'lrhar;'=>'⇋',
                                  'lrhard;'=>'⥭',
                                  'lrm;'=>'‎',
                                  'lrtri;'=>'⊿',
                                  'lsaquo;'=>'‹',
                                  'lscr;'=>'퓁',
                                  'lsh;'=>'↰',
                                  'lsim;'=>'≲',
                                  'lsime;'=>'⪍',
                                  'lsimg;'=>'⪏',
                                  'lsqb;'=>'[',
                                  'lsquo;'=>'‘',
                                  'lsquor;'=>'‚',
                                  'lstrok;'=>'ł',
                                  'lt'=>'<',
                                  'lt;'=>'<',
                                  'ltcc;'=>'⪦',
                                  'ltcir;'=>'⩹',
                                  'ltdot;'=>'⋖',
                                  'lthree;'=>'⋋',
                                  'ltimes;'=>'⋉',
                                  'ltlarr;'=>'⥶',
                                  'ltquest;'=>'⩻',
                                  'ltrPar;'=>'⦖',
                                  'ltri;'=>'◃',
                                  'ltrie;'=>'⊴',
                                  'ltrif;'=>'◂',
                                  'lurdshar;'=>'⥊',
                                  'luruhar;'=>'⥦',
                                  'lvertneqq;'=>'≨︀',
                                  'lvnE;'=>'≨︀',
                                  'mDDot;'=>'∺',
                                  'macr'=>'¯',
                                  'macr;'=>'¯',
                                  'male;'=>'♂',
                                  'malt;'=>'✠',
                                  'maltese;'=>'✠',
                                  'map;'=>'↦',
                                  'mapsto;'=>'↦',
                                  'mapstodown;'=>'↧',
                                  'mapstoleft;'=>'↤',
                                  'mapstoup;'=>'↥',
                                  'marker;'=>'▮',
                                  'mcomma;'=>'⨩',
                                  'mcy;'=>'м',
                                  'mdash;'=>'—',
                                  'measuredangle;'=>'∡',
                                  'mfr;'=>'픪',
                                  'mho;'=>'℧',
                                  'micro'=>'µ',
                                  'micro;'=>'µ',
                                  'mid;'=>'∣',
                                  'midast;'=>'*',
                                  'midcir;'=>'⫰',
                                  'middot'=>'·',
                                  'middot;'=>'·',
                                  'minus;'=>'−',
                                  'minusb;'=>'⊟',
                                  'minusd;'=>'∸',
                                  'minusdu;'=>'⨪',
                                  'mlcp;'=>'⫛',
                                  'mldr;'=>'…',
                                  'mnplus;'=>'∓',
                                  'models;'=>'⊧',
                                  'mopf;'=>'핞',
                                  'mp;'=>'∓',
                                  'mscr;'=>'퓂',
                                  'mstpos;'=>'∾',
                                  'mu;'=>'μ',
                                  'multimap;'=>'⊸',
                                  'mumap;'=>'⊸',
                                  'nGg;'=>'⋙̸',
                                  'nGt;'=>'≫⃒',
                                  'nGtv;'=>'≫̸',
                                  'nLeftarrow;'=>'⇍',
                                  'nLeftrightarrow;'=>'⇎',
                                  'nLl;'=>'⋘̸',
                                  'nLt;'=>'≪⃒',
                                  'nLtv;'=>'≪̸',
                                  'nRightarrow;'=>'⇏',
                                  'nVDash;'=>'⊯',
                                  'nVdash;'=>'⊮',
                                  'nabla;'=>'∇',
                                  'nacute;'=>'ń',
                                  'nang;'=>'∠⃒',
                                  'nap;'=>'≉',
                                  'napE;'=>'⩰̸',
                                  'napid;'=>'≋̸',
                                  'napos;'=>'ŉ',
                                  'napprox;'=>'≉',
                                  'natur;'=>'♮',
                                  'natural;'=>'♮',
                                  'naturals;'=>'ℕ',
                                  'nbsp'=>' ',
                                  'nbsp;'=>' ',
                                  'nbump;'=>'≎̸',
                                  'nbumpe;'=>'≏̸',
                                  'ncap;'=>'⩃',
                                  'ncaron;'=>'ň',
                                  'ncedil;'=>'ņ',
                                  'ncong;'=>'≇',
                                  'ncongdot;'=>'⩭̸',
                                  'ncup;'=>'⩂',
                                  'ncy;'=>'н',
                                  'ndash;'=>'–',
                                  'ne;'=>'≠',
                                  'neArr;'=>'⇗',
                                  'nearhk;'=>'⤤',
                                  'nearr;'=>'↗',
                                  'nearrow;'=>'↗',
                                  'nedot;'=>'≐̸',
                                  'nequiv;'=>'≢',
                                  'nesear;'=>'⤨',
                                  'nesim;'=>'≂̸',
                                  'nexist;'=>'∄',
                                  'nexists;'=>'∄',
                                  'nfr;'=>'픫',
                                  'ngE;'=>'≧̸',
                                  'nge;'=>'≱',
                                  'ngeq;'=>'≱',
                                  'ngeqq;'=>'≧̸',
                                  'ngeqslant;'=>'⩾̸',
                                  'nges;'=>'⩾̸',
                                  'ngsim;'=>'≵',
                                  'ngt;'=>'≯',
                                  'ngtr;'=>'≯',
                                  'nhArr;'=>'⇎',
                                  'nharr;'=>'↮',
                                  'nhpar;'=>'⫲',
                                  'ni;'=>'∋',
                                  'nis;'=>'⋼',
                                  'nisd;'=>'⋺',
                                  'niv;'=>'∋',
                                  'njcy;'=>'њ',
                                  'nlArr;'=>'⇍',
                                  'nlE;'=>'≦̸',
                                  'nlarr;'=>'↚',
                                  'nldr;'=>'‥',
                                  'nle;'=>'≰',
                                  'nleftarrow;'=>'↚',
                                  'nleftrightarrow;'=>'↮',
                                  'nleq;'=>'≰',
                                  'nleqq;'=>'≦̸',
                                  'nleqslant;'=>'⩽̸',
                                  'nles;'=>'⩽̸',
                                  'nless;'=>'≮',
                                  'nlsim;'=>'≴',
                                  'nlt;'=>'≮',
                                  'nltri;'=>'⋪',
                                  'nltrie;'=>'⋬',
                                  'nmid;'=>'∤',
                                  'nopf;'=>'핟',
                                  'not'=>'¬',
                                  'not;'=>'¬',
                                  'notin;'=>'∉',
                                  'notinE;'=>'⋹̸',
                                  'notindot;'=>'⋵̸',
                                  'notinva;'=>'∉',
                                  'notinvb;'=>'⋷',
                                  'notinvc;'=>'⋶',
                                  'notni;'=>'∌',
                                  'notniva;'=>'∌',
                                  'notnivb;'=>'⋾',
                                  'notnivc;'=>'⋽',
                                  'npar;'=>'∦',
                                  'nparallel;'=>'∦',
                                  'nparsl;'=>'⫽⃥',
                                  'npart;'=>'∂̸',
                                  'npolint;'=>'⨔',
                                  'npr;'=>'⊀',
                                  'nprcue;'=>'⋠',
                                  'npre;'=>'⪯̸',
                                  'nprec;'=>'⊀',
                                  'npreceq;'=>'⪯̸',
                                  'nrArr;'=>'⇏',
                                  'nrarr;'=>'↛',
                                  'nrarrc;'=>'⤳̸',
                                  'nrarrw;'=>'↝̸',
                                  'nrightarrow;'=>'↛',
                                  'nrtri;'=>'⋫',
                                  'nrtrie;'=>'⋭',
                                  'nsc;'=>'⊁',
                                  'nsccue;'=>'⋡',
                                  'nsce;'=>'⪰̸',
                                  'nscr;'=>'퓃',
                                  'nshortmid;'=>'∤',
                                  'nshortparallel;'=>'∦',
                                  'nsim;'=>'≁',
                                  'nsime;'=>'≄',
                                  'nsimeq;'=>'≄',
                                  'nsmid;'=>'∤',
                                  'nspar;'=>'∦',
                                  'nsqsube;'=>'⋢',
                                  'nsqsupe;'=>'⋣',
                                  'nsub;'=>'⊄',
                                  'nsubE;'=>'⫅̸',
                                  'nsube;'=>'⊈',
                                  'nsubset;'=>'⊂⃒',
                                  'nsubseteq;'=>'⊈',
                                  'nsubseteqq;'=>'⫅̸',
                                  'nsucc;'=>'⊁',
                                  'nsucceq;'=>'⪰̸',
                                  'nsup;'=>'⊅',
                                  'nsupE;'=>'⫆̸',
                                  'nsupe;'=>'⊉',
                                  'nsupset;'=>'⊃⃒',
                                  'nsupseteq;'=>'⊉',
                                  'nsupseteqq;'=>'⫆̸',
                                  'ntgl;'=>'≹',
                                  'ntilde'=>'ñ',
                                  'ntilde;'=>'ñ',
                                  'ntlg;'=>'≸',
                                  'ntriangleleft;'=>'⋪',
                                  'ntrianglelefteq;'=>'⋬',
                                  'ntriangleright;'=>'⋫',
                                  'ntrianglerighteq;'=>'⋭',
                                  'nu;'=>'ν',
                                  'num;'=>'#',
                                  'numero;'=>'№',
                                  'numsp;'=>' ',
                                  'nvDash;'=>'⊭',
                                  'nvHarr;'=>'⤄',
                                  'nvap;'=>'≍⃒',
                                  'nvdash;'=>'⊬',
                                  'nvge;'=>'≥⃒',
                                  'nvgt;'=>'>⃒',
                                  'nvinfin;'=>'⧞',
                                  'nvlArr;'=>'⤂',
                                  'nvle;'=>'≤⃒',
                                  'nvlt;'=>'<⃒',
                                  'nvltrie;'=>'⊴⃒',
                                  'nvrArr;'=>'⤃',
                                  'nvrtrie;'=>'⊵⃒',
                                  'nvsim;'=>'∼⃒',
                                  'nwArr;'=>'⇖',
                                  'nwarhk;'=>'⤣',
                                  'nwarr;'=>'↖',
                                  'nwarrow;'=>'↖',
                                  'nwnear;'=>'⤧',
                                  'oS;'=>'Ⓢ',
                                  'oacute'=>'ó',
                                  'oacute;'=>'ó',
                                  'oast;'=>'⊛',
                                  'ocir;'=>'⊚',
                                  'ocirc'=>'ô',
                                  'ocirc;'=>'ô',
                                  'ocy;'=>'о',
                                  'odash;'=>'⊝',
                                  'odblac;'=>'ő',
                                  'odiv;'=>'⨸',
                                  'odot;'=>'⊙',
                                  'odsold;'=>'⦼',
                                  'oelig;'=>'œ',
                                  'ofcir;'=>'⦿',
                                  'ofr;'=>'픬',
                                  'ogon;'=>'˛',
                                  'ograve'=>'ò',
                                  'ograve;'=>'ò',
                                  'ogt;'=>'⧁',
                                  'ohbar;'=>'⦵',
                                  'ohm;'=>'Ω',
                                  'oint;'=>'∮',
                                  'olarr;'=>'↺',
                                  'olcir;'=>'⦾',
                                  'olcross;'=>'⦻',
                                  'oline;'=>'‾',
                                  'olt;'=>'⧀',
                                  'omacr;'=>'ō',
                                  'omega;'=>'ω',
                                  'omicron;'=>'ο',
                                  'omid;'=>'⦶',
                                  'ominus;'=>'⊖',
                                  'oopf;'=>'할',
                                  'opar;'=>'⦷',
                                  'operp;'=>'⦹',
                                  'oplus;'=>'⊕',
                                  'or;'=>'∨',
                                  'orarr;'=>'↻',
                                  'ord;'=>'⩝',
                                  'order;'=>'ℴ',
                                  'orderof;'=>'ℴ',
                                  'ordf'=>'ª',
                                  'ordf;'=>'ª',
                                  'ordm'=>'º',
                                  'ordm;'=>'º',
                                  'origof;'=>'⊶',
                                  'oror;'=>'⩖',
                                  'orslope;'=>'⩗',
                                  'orv;'=>'⩛',
                                  'oscr;'=>'ℴ',
                                  'oslash'=>'ø',
                                  'oslash;'=>'ø',
                                  'osol;'=>'⊘',
                                  'otilde'=>'õ',
                                  'otilde;'=>'õ',
                                  'otimes;'=>'⊗',
                                  'otimesas;'=>'⨶',
                                  'ouml'=>'ö',
                                  'ouml;'=>'ö',
                                  'ovbar;'=>'⌽',
                                  'par;'=>'∥',
                                  'para'=>'¶',
                                  'para;'=>'¶',
                                  'parallel;'=>'∥',
                                  'parsim;'=>'⫳',
                                  'parsl;'=>'⫽',
                                  'part;'=>'∂',
                                  'pcy;'=>'п',
                                  'percnt;'=>'%',
                                  'period;'=>'.',
                                  'permil;'=>'‰',
                                  'perp;'=>'⊥',
                                  'pertenk;'=>'‱',
                                  'pfr;'=>'픭',
                                  'phi;'=>'φ',
                                  'phiv;'=>'ϕ',
                                  'phmmat;'=>'ℳ',
                                  'phone;'=>'☎',
                                  'pi;'=>'π',
                                  'pitchfork;'=>'⋔',
                                  'piv;'=>'ϖ',
                                  'planck;'=>'ℏ',
                                  'planckh;'=>'ℎ',
                                  'plankv;'=>'ℏ',
                                  'plus;'=>'+',
                                  'plusacir;'=>'⨣',
                                  'plusb;'=>'⊞',
                                  'pluscir;'=>'⨢',
                                  'plusdo;'=>'∔',
                                  'plusdu;'=>'⨥',
                                  'pluse;'=>'⩲',
                                  'plusmn'=>'±',
                                  'plusmn;'=>'±',
                                  'plussim;'=>'⨦',
                                  'plustwo;'=>'⨧',
                                  'pm;'=>'±',
                                  'pointint;'=>'⨕',
                                  'popf;'=>'핡',
                                  'pound'=>'£',
                                  'pound;'=>'£',
                                  'pr;'=>'≺',
                                  'prE;'=>'⪳',
                                  'prap;'=>'⪷',
                                  'prcue;'=>'≼',
                                  'pre;'=>'⪯',
                                  'prec;'=>'≺',
                                  'precapprox;'=>'⪷',
                                  'preccurlyeq;'=>'≼',
                                  'preceq;'=>'⪯',
                                  'precnapprox;'=>'⪹',
                                  'precneqq;'=>'⪵',
                                  'precnsim;'=>'⋨',
                                  'precsim;'=>'≾',
                                  'prime;'=>'′',
                                  'primes;'=>'ℙ',
                                  'prnE;'=>'⪵',
                                  'prnap;'=>'⪹',
                                  'prnsim;'=>'⋨',
                                  'prod;'=>'∏',
                                  'profalar;'=>'⌮',
                                  'profline;'=>'⌒',
                                  'profsurf;'=>'⌓',
                                  'prop;'=>'∝',
                                  'propto;'=>'∝',
                                  'prsim;'=>'≾',
                                  'prurel;'=>'⊰',
                                  'pscr;'=>'퓅',
                                  'psi;'=>'ψ',
                                  'puncsp;'=>' ',
                                  'qfr;'=>'픮',
                                  'qint;'=>'⨌',
                                  'qopf;'=>'핢',
                                  'qprime;'=>'⁗',
                                  'qscr;'=>'퓆',
                                  'quaternions;'=>'ℍ',
                                  'quatint;'=>'⨖',
                                  'quest;'=>'?',
                                  'questeq;'=>'≟',
                                  'quot'=>'"',
                                  'quot;'=>'"',
                                  'rAarr;'=>'⇛',
                                  'rArr;'=>'⇒',
                                  'rAtail;'=>'⤜',
                                  'rBarr;'=>'⤏',
                                  'rHar;'=>'⥤',
                                  'race;'=>'∽̱',
                                  'racute;'=>'ŕ',
                                  'radic;'=>'√',
                                  'raemptyv;'=>'⦳',
                                  'rang;'=>'⟩',
                                  'rangd;'=>'⦒',
                                  'range;'=>'⦥',
                                  'rangle;'=>'⟩',
                                  'raquo'=>'»',
                                  'raquo;'=>'»',
                                  'rarr;'=>'→',
                                  'rarrap;'=>'⥵',
                                  'rarrb;'=>'⇥',
                                  'rarrbfs;'=>'⤠',
                                  'rarrc;'=>'⤳',
                                  'rarrfs;'=>'⤞',
                                  'rarrhk;'=>'↪',
                                  'rarrlp;'=>'↬',
                                  'rarrpl;'=>'⥅',
                                  'rarrsim;'=>'⥴',
                                  'rarrtl;'=>'↣',
                                  'rarrw;'=>'↝',
                                  'ratail;'=>'⤚',
                                  'ratio;'=>'∶',
                                  'rationals;'=>'ℚ',
                                  'rbarr;'=>'⤍',
                                  'rbbrk;'=>'❳',
                                  'rbrace;'=>'}',
                                  'rbrack;'=>']',
                                  'rbrke;'=>'⦌',
                                  'rbrksld;'=>'⦎',
                                  'rbrkslu;'=>'⦐',
                                  'rcaron;'=>'ř',
                                  'rcedil;'=>'ŗ',
                                  'rceil;'=>'⌉',
                                  'rcub;'=>'}',
                                  'rcy;'=>'р',
                                  'rdca;'=>'⤷',
                                  'rdldhar;'=>'⥩',
                                  'rdquo;'=>'”',
                                  'rdquor;'=>'”',
                                  'rdsh;'=>'↳',
                                  'real;'=>'ℜ',
                                  'realine;'=>'ℛ',
                                  'realpart;'=>'ℜ',
                                  'reals;'=>'ℝ',
                                  'rect;'=>'▭',
                                  'reg'=>'®',
                                  'reg;'=>'®',
                                  'rfisht;'=>'⥽',
                                  'rfloor;'=>'⌋',
                                  'rfr;'=>'픯',
                                  'rhard;'=>'⇁',
                                  'rharu;'=>'⇀',
                                  'rharul;'=>'⥬',
                                  'rho;'=>'ρ',
                                  'rhov;'=>'ϱ',
                                  'rightarrow;'=>'→',
                                  'rightarrowtail;'=>'↣',
                                  'rightharpoondown;'=>'⇁',
                                  'rightharpoonup;'=>'⇀',
                                  'rightleftarrows;'=>'⇄',
                                  'rightleftharpoons;'=>'⇌',
                                  'rightrightarrows;'=>'⇉',
                                  'rightsquigarrow;'=>'↝',
                                  'rightthreetimes;'=>'⋌',
                                  'ring;'=>'˚',
                                  'risingdotseq;'=>'≓',
                                  'rlarr;'=>'⇄',
                                  'rlhar;'=>'⇌',
                                  'rlm;'=>'‏',
                                  'rmoust;'=>'⎱',
                                  'rmoustache;'=>'⎱',
                                  'rnmid;'=>'⫮',
                                  'roang;'=>'⟭',
                                  'roarr;'=>'⇾',
                                  'robrk;'=>'⟧',
                                  'ropar;'=>'⦆',
                                  'ropf;'=>'핣',
                                  'roplus;'=>'⨮',
                                  'rotimes;'=>'⨵',
                                  'rpar;'=>')',
                                  'rpargt;'=>'⦔',
                                  'rppolint;'=>'⨒',
                                  'rrarr;'=>'⇉',
                                  'rsaquo;'=>'›',
                                  'rscr;'=>'퓇',
                                  'rsh;'=>'↱',
                                  'rsqb;'=>']',
                                  'rsquo;'=>'’',
                                  'rsquor;'=>'’',
                                  'rthree;'=>'⋌',
                                  'rtimes;'=>'⋊',
                                  'rtri;'=>'▹',
                                  'rtrie;'=>'⊵',
                                  'rtrif;'=>'▸',
                                  'rtriltri;'=>'⧎',
                                  'ruluhar;'=>'⥨',
                                  'rx;'=>'℞',
                                  'sacute;'=>'ś',
                                  'sbquo;'=>'‚',
                                  'sc;'=>'≻',
                                  'scE;'=>'⪴',
                                  'scap;'=>'⪸',
                                  'scaron;'=>'š',
                                  'sccue;'=>'≽',
                                  'sce;'=>'⪰',
                                  'scedil;'=>'ş',
                                  'scirc;'=>'ŝ',
                                  'scnE;'=>'⪶',
                                  'scnap;'=>'⪺',
                                  'scnsim;'=>'⋩',
                                  'scpolint;'=>'⨓',
                                  'scsim;'=>'≿',
                                  'scy;'=>'с',
                                  'sdot;'=>'⋅',
                                  'sdotb;'=>'⊡',
                                  'sdote;'=>'⩦',
                                  'seArr;'=>'⇘',
                                  'searhk;'=>'⤥',
                                  'searr;'=>'↘',
                                  'searrow;'=>'↘',
                                  'sect'=>'§',
                                  'sect;'=>'§',
                                  'semi;'=>';',
                                  'seswar;'=>'⤩',
                                  'setminus;'=>'∖',
                                  'setmn;'=>'∖',
                                  'sext;'=>'✶',
                                  'sfr;'=>'픰',
                                  'sfrown;'=>'⌢',
                                  'sharp;'=>'♯',
                                  'shchcy;'=>'щ',
                                  'shcy;'=>'ш',
                                  'shortmid;'=>'∣',
                                  'shortparallel;'=>'∥',
                                  'shy'=>'­',
                                  'shy;'=>'­',
                                  'sigma;'=>'σ',
                                  'sigmaf;'=>'ς',
                                  'sigmav;'=>'ς',
                                  'sim;'=>'∼',
                                  'simdot;'=>'⩪',
                                  'sime;'=>'≃',
                                  'simeq;'=>'≃',
                                  'simg;'=>'⪞',
                                  'simgE;'=>'⪠',
                                  'siml;'=>'⪝',
                                  'simlE;'=>'⪟',
                                  'simne;'=>'≆',
                                  'simplus;'=>'⨤',
                                  'simrarr;'=>'⥲',
                                  'slarr;'=>'←',
                                  'smallsetminus;'=>'∖',
                                  'smashp;'=>'⨳',
                                  'smeparsl;'=>'⧤',
                                  'smid;'=>'∣',
                                  'smile;'=>'⌣',
                                  'smt;'=>'⪪',
                                  'smte;'=>'⪬',
                                  'smtes;'=>'⪬︀',
                                  'softcy;'=>'ь',
                                  'sol;'=>'/',
                                  'solb;'=>'⧄',
                                  'solbar;'=>'⌿',
                                  'sopf;'=>'핤',
                                  'spades;'=>'♠',
                                  'spadesuit;'=>'♠',
                                  'spar;'=>'∥',
                                  'sqcap;'=>'⊓',
                                  'sqcaps;'=>'⊓︀',
                                  'sqcup;'=>'⊔',
                                  'sqcups;'=>'⊔︀',
                                  'sqsub;'=>'⊏',
                                  'sqsube;'=>'⊑',
                                  'sqsubset;'=>'⊏',
                                  'sqsubseteq;'=>'⊑',
                                  'sqsup;'=>'⊐',
                                  'sqsupe;'=>'⊒',
                                  'sqsupset;'=>'⊐',
                                  'sqsupseteq;'=>'⊒',
                                  'squ;'=>'□',
                                  'square;'=>'□',
                                  'squarf;'=>'▪',
                                  'squf;'=>'▪',
                                  'srarr;'=>'→',
                                  'sscr;'=>'퓈',
                                  'ssetmn;'=>'∖',
                                  'ssmile;'=>'⌣',
                                  'sstarf;'=>'⋆',
                                  'star;'=>'☆',
                                  'starf;'=>'★',
                                  'straightepsilon;'=>'ϵ',
                                  'straightphi;'=>'ϕ',
                                  'strns;'=>'¯',
                                  'sub;'=>'⊂',
                                  'subE;'=>'⫅',
                                  'subdot;'=>'⪽',
                                  'sube;'=>'⊆',
                                  'subedot;'=>'⫃',
                                  'submult;'=>'⫁',
                                  'subnE;'=>'⫋',
                                  'subne;'=>'⊊',
                                  'subplus;'=>'⪿',
                                  'subrarr;'=>'⥹',
                                  'subset;'=>'⊂',
                                  'subseteq;'=>'⊆',
                                  'subseteqq;'=>'⫅',
                                  'subsetneq;'=>'⊊',
                                  'subsetneqq;'=>'⫋',
                                  'subsim;'=>'⫇',
                                  'subsub;'=>'⫕',
                                  'subsup;'=>'⫓',
                                  'succ;'=>'≻',
                                  'succapprox;'=>'⪸',
                                  'succcurlyeq;'=>'≽',
                                  'succeq;'=>'⪰',
                                  'succnapprox;'=>'⪺',
                                  'succneqq;'=>'⪶',
                                  'succnsim;'=>'⋩',
                                  'succsim;'=>'≿',
                                  'sum;'=>'∑',
                                  'sung;'=>'♪',
                                  'sup1'=>'¹',
                                  'sup1;'=>'¹',
                                  'sup2'=>'²',
                                  'sup2;'=>'²',
                                  'sup3'=>'³',
                                  'sup3;'=>'³',
                                  'sup;'=>'⊃',
                                  'supE;'=>'⫆',
                                  'supdot;'=>'⪾',
                                  'supdsub;'=>'⫘',
                                  'supe;'=>'⊇',
                                  'supedot;'=>'⫄',
                                  'suphsol;'=>'⟉',
                                  'suphsub;'=>'⫗',
                                  'suplarr;'=>'⥻',
                                  'supmult;'=>'⫂',
                                  'supnE;'=>'⫌',
                                  'supne;'=>'⊋',
                                  'supplus;'=>'⫀',
                                  'supset;'=>'⊃',
                                  'supseteq;'=>'⊇',
                                  'supseteqq;'=>'⫆',
                                  'supsetneq;'=>'⊋',
                                  'supsetneqq;'=>'⫌',
                                  'supsim;'=>'⫈',
                                  'supsub;'=>'⫔',
                                  'supsup;'=>'⫖',
                                  'swArr;'=>'⇙',
                                  'swarhk;'=>'⤦',
                                  'swarr;'=>'↙',
                                  'swarrow;'=>'↙',
                                  'swnwar;'=>'⤪',
                                  'szlig'=>'ß',
                                  'szlig;'=>'ß',
                                  'target;'=>'⌖',
                                  'tau;'=>'τ',
                                  'tbrk;'=>'⎴',
                                  'tcaron;'=>'ť',
                                  'tcedil;'=>'ţ',
                                  'tcy;'=>'т',
                                  'tdot;'=>'⃛',
                                  'telrec;'=>'⌕',
                                  'tfr;'=>'픱',
                                  'there4;'=>'∴',
                                  'therefore;'=>'∴',
                                  'theta;'=>'θ',
                                  'thetasym;'=>'ϑ',
                                  'thetav;'=>'ϑ',
                                  'thickapprox;'=>'≈',
                                  'thicksim;'=>'∼',
                                  'thinsp;'=>' ',
                                  'thkap;'=>'≈',
                                  'thksim;'=>'∼',
                                  'thorn'=>'þ',
                                  'thorn;'=>'þ',
                                  'tilde;'=>'˜',
                                  'times'=>'×',
                                  'times;'=>'×',
                                  'timesb;'=>'⊠',
                                  'timesbar;'=>'⨱',
                                  'timesd;'=>'⨰',
                                  'tint;'=>'∭',
                                  'toea;'=>'⤨',
                                  'top;'=>'⊤',
                                  'topbot;'=>'⌶',
                                  'topcir;'=>'⫱',
                                  'topf;'=>'핥',
                                  'topfork;'=>'⫚',
                                  'tosa;'=>'⤩',
                                  'tprime;'=>'‴',
                                  'trade;'=>'™',
                                  'triangle;'=>'▵',
                                  'triangledown;'=>'▿',
                                  'triangleleft;'=>'◃',
                                  'trianglelefteq;'=>'⊴',
                                  'triangleq;'=>'≜',
                                  'triangleright;'=>'▹',
                                  'trianglerighteq;'=>'⊵',
                                  'tridot;'=>'◬',
                                  'trie;'=>'≜',
                                  'triminus;'=>'⨺',
                                  'triplus;'=>'⨹',
                                  'trisb;'=>'⧍',
                                  'tritime;'=>'⨻',
                                  'trpezium;'=>'⏢',
                                  'tscr;'=>'퓉',
                                  'tscy;'=>'ц',
                                  'tshcy;'=>'ћ',
                                  'tstrok;'=>'ŧ',
                                  'twixt;'=>'≬',
                                  'twoheadleftarrow;'=>'↞',
                                  'twoheadrightarrow;'=>'↠',
                                  'uArr;'=>'⇑',
                                  'uHar;'=>'⥣',
                                  'uacute'=>'ú',
                                  'uacute;'=>'ú',
                                  'uarr;'=>'↑',
                                  'ubrcy;'=>'ў',
                                  'ubreve;'=>'ŭ',
                                  'ucirc'=>'û',
                                  'ucirc;'=>'û',
                                  'ucy;'=>'у',
                                  'udarr;'=>'⇅',
                                  'udblac;'=>'ű',
                                  'udhar;'=>'⥮',
                                  'ufisht;'=>'⥾',
                                  'ufr;'=>'픲',
                                  'ugrave'=>'ù',
                                  'ugrave;'=>'ù',
                                  'uharl;'=>'↿',
                                  'uharr;'=>'↾',
                                  'uhblk;'=>'▀',
                                  'ulcorn;'=>'⌜',
                                  'ulcorner;'=>'⌜',
                                  'ulcrop;'=>'⌏',
                                  'ultri;'=>'◸',
                                  'umacr;'=>'ū',
                                  'uml'=>'¨',
                                  'uml;'=>'¨',
                                  'uogon;'=>'ų',
                                  'uopf;'=>'핦',
                                  'uparrow;'=>'↑',
                                  'updownarrow;'=>'↕',
                                  'upharpoonleft;'=>'↿',
                                  'upharpoonright;'=>'↾',
                                  'uplus;'=>'⊎',
                                  'upsi;'=>'υ',
                                  'upsih;'=>'ϒ',
                                  'upsilon;'=>'υ',
                                  'upuparrows;'=>'⇈',
                                  'urcorn;'=>'⌝',
                                  'urcorner;'=>'⌝',
                                  'urcrop;'=>'⌎',
                                  'uring;'=>'ů',
                                  'urtri;'=>'◹',
                                  'uscr;'=>'퓊',
                                  'utdot;'=>'⋰',
                                  'utilde;'=>'ũ',
                                  'utri;'=>'▵',
                                  'utrif;'=>'▴',
                                  'uuarr;'=>'⇈',
                                  'uuml'=>'ü',
                                  'uuml;'=>'ü',
                                  'uwangle;'=>'⦧',
                                  'vArr;'=>'⇕',
                                  'vBar;'=>'⫨',
                                  'vBarv;'=>'⫩',
                                  'vDash;'=>'⊨',
                                  'vangrt;'=>'⦜',
                                  'varepsilon;'=>'ϵ',
                                  'varkappa;'=>'ϰ',
                                  'varnothing;'=>'∅',
                                  'varphi;'=>'ϕ',
                                  'varpi;'=>'ϖ',
                                  'varpropto;'=>'∝',
                                  'varr;'=>'↕',
                                  'varrho;'=>'ϱ',
                                  'varsigma;'=>'ς',
                                  'varsubsetneq;'=>'⊊︀',
                                  'varsubsetneqq;'=>'⫋︀',
                                  'varsupsetneq;'=>'⊋︀',
                                  'varsupsetneqq;'=>'⫌︀',
                                  'vartheta;'=>'ϑ',
                                  'vartriangleleft;'=>'⊲',
                                  'vartriangleright;'=>'⊳',
                                  'vcy;'=>'в',
                                  'vdash;'=>'⊢',
                                  'vee;'=>'∨',
                                  'veebar;'=>'⊻',
                                  'veeeq;'=>'≚',
                                  'vellip;'=>'⋮',
                                  'verbar;'=>'|',
                                  'vert;'=>'|',
                                  'vfr;'=>'픳',
                                  'vltri;'=>'⊲',
                                  'vnsub;'=>'⊂⃒',
                                  'vnsup;'=>'⊃⃒',
                                  'vopf;'=>'핧',
                                  'vprop;'=>'∝',
                                  'vrtri;'=>'⊳',
                                  'vscr;'=>'퓋',
                                  'vsubnE;'=>'⫋︀',
                                  'vsubne;'=>'⊊︀',
                                  'vsupnE;'=>'⫌︀',
                                  'vsupne;'=>'⊋︀',
                                  'vzigzag;'=>'⦚',
                                  'wcirc;'=>'ŵ',
                                  'wedbar;'=>'⩟',
                                  'wedge;'=>'∧',
                                  'wedgeq;'=>'≙',
                                  'weierp;'=>'℘',
                                  'wfr;'=>'픴',
                                  'wopf;'=>'함',
                                  'wp;'=>'℘',
                                  'wr;'=>'≀',
                                  'wreath;'=>'≀',
                                  'wscr;'=>'퓌',
                                  'xcap;'=>'⋂',
                                  'xcirc;'=>'◯',
                                  'xcup;'=>'⋃',
                                  'xdtri;'=>'▽',
                                  'xfr;'=>'픵',
                                  'xhArr;'=>'⟺',
                                  'xharr;'=>'⟷',
                                  'xi;'=>'ξ',
                                  'xlArr;'=>'⟸',
                                  'xlarr;'=>'⟵',
                                  'xmap;'=>'⟼',
                                  'xnis;'=>'⋻',
                                  'xodot;'=>'⨀',
                                  'xopf;'=>'합',
                                  'xoplus;'=>'⨁',
                                  'xotime;'=>'⨂',
                                  'xrArr;'=>'⟹',
                                  'xrarr;'=>'⟶',
                                  'xscr;'=>'퓍',
                                  'xsqcup;'=>'⨆',
                                  'xuplus;'=>'⨄',
                                  'xutri;'=>'△',
                                  'xvee;'=>'⋁',
                                  'xwedge;'=>'⋀',
                                  'yacute'=>'ý',
                                  'yacute;'=>'ý',
                                  'yacy;'=>'я',
                                  'ycirc;'=>'ŷ',
                                  'ycy;'=>'ы',
                                  'yen'=>'¥',
                                  'yen;'=>'¥',
                                  'yfr;'=>'픶',
                                  'yicy;'=>'ї',
                                  'yopf;'=>'핪',
                                  'yscr;'=>'퓎',
                                  'yucy;'=>'ю',
                                  'yuml'=>'ÿ',
                                  'yuml;'=>'ÿ',
                                  'zacute;'=>'ź',
                                  'zcaron;'=>'ž',
                                  'zcy;'=>'з',
                                  'zdot;'=>'ż',
                                  'zeetrf;'=>'ℨ',
                                  'zeta;'=>'ζ',
                                  'zfr;'=>'픷',
                                  'zhcy;'=>'ж',
                                  'zigrarr;'=>'⇝',
                                  'zopf;'=>'핫',
                                  'zscr;'=>'퓏',
                                  'zwj;'=>'‍',
                                  'zwnj;'=>'‌');

 protected static $entityReplacementTable=array(0x0D => "\n",           # 0x000A LINE FEED (LF)
                                                0x80 => "€",            # 0x20AC EURO SIGN
                                                0x81 => "\xEF\xBF\xBD", # 0xFFFD REPLACEMENT CHARACTER
                                                0x82 => "‚",            # 0x201A SINGLE LOW-9 QUOTATION MRK
                                                0x83 => "ƒ",            # 0x0192 LATIN SMALL LETTER F WITH HOOK
                                                0x84 => "„",            # 0x201E DOUBLE LOW-9 QUOTATION MARK
                                                0x85 => "…",            # 0x2026 HORIZONTAL ELLIPSIS
                                                0x86 => "†",            # 0x2020 DAGGER
                                                0x87 => "‡",            # 0x2021 DOUBLE DAGGER
                                                0x88 => "ˆ",            # 0x02C6 MODIFIER LETTER CIRCUMFLEX ACCENT
                                                0x89 => "‰",            # 0x2030 PER MILLE SIGN
                                                0x8A => "Š",            # 0x0160 LATIN CAPITAL LETTER S WITH CARON
                                                0x8B => "‹",            # 0x2039 SINGLE LEFT-POINTING ANGLE QUOTATION MARK
                                                0x8C => "Œ",            # 0x0152 LATIN CAPITAL LIGATURE OE
                                                0x8D => "\xEF\xBF\xBD", # 0xFFFD REPLACEMENT CHARACTER
                                                0x8E => "Ž",            # 0x017D LATIN CAPITAL LETTER Z WITH CARON
                                                0x8F => "\xEF\xBF\xBD", # 0xFFFD REPLACEMENT CHARACTER
                                                0x90 => "\xEF\xBF\xBD", # 0xFFFD REPLACEMENT CHARACTER
                                                0x91 => "‘",            # 0x2018 LEFT SINGLE QUOTATION MARK
                                                0x92 => "’",            # 0x2019 RIGHT SINGLE QUOTATION MARK
                                                0x93 => "“",            # 0x201C LEFT DOUBLE QUOTATION MARK
                                                0x94 => "”",            # 0x201D RIGHT DOUBLE QUOTATION MARK
                                                0x95 => "•",            # 0x2022 BULLET
                                                0x96 => "–",            # 0x2013 EN DASH
                                                0x97 => "—",            # 0x2014 EM DASH
                                                0x98 => "˜",            # 0x02DC SMALL TILDE
                                                0x99 => "™",            # 0x2122 TRADE MARK SIGN
                                                0x9A => "š",            # 0x0161 LATIN SMALL LETTER S WITH CARON
                                                0x9B => "›",            # 0x203A SINGLE RIGHT-POINTING ANGLE QUOTATION MARK
                                                0x9C => "œ",            # 0x0153 LATIN SMALL LIGATURE OE
                                                0x9D => "\xEF\xBF\xBD", # 0xFFFD REPLACEMENT CHARACTER
                                                0x9E => "ž",            # 0x017E LATIN SMALL LETTER Z WITH CARON
                                                0x9F => "Ÿ"             # 0x0178 LATIN CAPITAL LETTER Y WITH DIAERESIS
                                               );

 # Used by some insertion modes to
 # return to the previous insertion mode.
 protected static $oMode=null;

 # Current integer byte position.
 protected static $pointer=0;

 # Toggle used by the tree builder to turn quirks mode on.
 # Can either be true, false, or 'limited'.
 protected static $quirksMode=false;

 # Elements that have special processing instructions. Used by the tree
 # builder.
 protected static $specialElements=array('html' =>  array('address','applet','area','article','aside',
                                                        'base','basefont','bgsound','blockquote',
                                                        'body','br','button','caption','center','col',
                                                        'colgroup','command','dd','details','dir','div',
                                                        'dl','dt','embed','fieldset','figcaption','figure',
                                                        'footer','form','frame','frameset','h1','h2','h3',
                                                        'h4','h5','h6','head','header','hgroup','hr','html',
                                                        'iframe','img','input','isindex','li','link',
                                                        'listing','marquee','main','menu','meta','nav','noembed',
                                                        'noframes','noscript','object','ol','p','param',
                                                        'plaintext','pre','script','section','select',
                                                        'style','summary','table','tbody','td','textarea',
                                                        'tfoot','th','thead','title','tr','ul','wbr','xmp',
                                                        '#document','#document-fragment'),
                                       'mathml' => array('mi','mo','mn','ms','mtext','annotation-xml'),
                                       'svg' =>    array('foreignObject','desc','title'));

 # Stack of open elements.
 protected static $stack=array();

 # Size of static::$stack.
 protected static $stackSize=0;

 # Controls the primary operation of the tokenizer.
 protected static $state='data';

 # Current non-emitted token.
 protected static $token=array();

 # The last open element in the stack.
 protected static $currentNode=null;

 # The name of the last open element in the stack.
 protected static $currentNodeName=null;

 # Bunches of reusable characters.
 const ALPHA           = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
 const UPPER_ALPHA     = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
 const LOWER_ALPHA     = 'abcdefghijklmnopqrstuvwxyz';
 const DIGIT           = '0123456789';
 const HEX             = '0123456789ABCDEFabcdef';
 const WHITESPACE      = "\t\n\x0c ";
 # Regex used when selecting next, previous, etc. non-whitespace text nodes and
 # when collapsing whitespace when pretty printing in HTML5::serialize().
 const WHITESPACEREGEX = '/^[ \t\n\r\x0c\x85               　]+$/S';

 # Parses the HTML document and returns a DOMDocument.
 # @param $data The string data to parse.
 static function parse($data)
 {
  # Set the error handler.
  set_error_handler(array(__CLASS__,'errorHandler'),error_reporting());

  # If there's no input data send a fatal error.
  if(!is_string($data))
   return static::fatalError('string expected',__METHOD__,gettype($data));

  # Process the input stream.
  static::processInputStream($data);

  //while(static::tokenize($data)===true){}
  static::tokenize($data);

  # Reset the class.
  static::$active=array();
  static::$activeSize=0;
  static::$context=null;
  static::$data=null;
  static::$EOF=0;
  static::$buffer='';
  static::$attributenamebuffer='';
  static::$form=null;
  static::$framesetOk=true;
  static::$head=null;
  static::$pendingTableCharacterTokens=array();
  static::$mode='initial';
  static::$oMode=null;
  static::$pointer=0;
  static::$quirksMode=false;
  static::$stack=array();
  static::$stackSize=0;
  static::$state='data';
  static::$token=array();
  static::$currentNode=null;
  static::$currentNodeName=null;

  restore_error_handler();

  # Fix the DOM before outputting.
  return static::fixDOM();
 }

 static function parseFragment($data,$dom=null,$context=null)
 {
  # If the provided DOM is null then any context element would return errors because
  # of its nonexistence in the DOM. Prevent that by nullifying the context.
  if(is_null($dom))
   $context=null;

  # Create a new Document node, and mark it as being an HTML document.
  # If a DOMDocument isn't supplied then create one.
  static::$DOM=($dom->nodeType==XML_DOCUMENT_NODE) ? $dom : DOMImplementation::createDocument();
  static::$DOMFragment=static::$DOM->createDocumentFragment();
  # If there is a context element, and the Document of the context element is in
  # quirks mode, then let the Document be in quirks mode. Otherwise, if there is a
  # context element, and the Document of the context element is in limited-quirks
  # mode, then let the Document be in limited-quirks mode. Otherwise, leave the
  # Document in no-quirks mode.

  # Cannot check whether the context element is in quirks mode. The default
  # value for static::$quirksMode is false anyway.

  # DEVIATION: The spec's version of parsing fragments isn't remotely useful in the
  # context this library is intended for use in. This implementation uses a
  # DOMDocumentFragment for inserting nodes into. There's no need to have a
  # different process for when there isn't a context. There will always be one.
  if(is_null($context))
  {
   $context=static::$DOMFragment;
   # With a document fragment the state will always be 'data'.
   static::$state='data';
  }
  else
  {
   # Change the tokenization stage based upon what the context element is.
   $name=$context->nodeName;
   if($name=='title' || $name=='textarea')
    static::$state='RCDATA';
   elseif($name=='style' || $name=='xmp' || $name=='iframe' || $name=='noembed' || $name=='noframes')
    static::$state='RAWTEXT';
   elseif($name=='script')
    static::$state='script';
   elseif($name=='plaintext')
    static::$state='plaintext';
   else
    static::$state='data';
  }

  # Create a new HTML parser, and associate it with the just created Document node.
  static::$fragment=true;
  static::$context=$context;

  # DEVIATION: This implementation uses a DOMDocumentFragment for inserting nodes
  # into. There's no need to make a dummy html element.

  # Push the context onto the stack, so it can be referenced as the context element.
  static::stackPush($context);
  # Reset the parser's insertion mode appropriately.
  static::resetInsertionMode();

  # Set the parser's form element pointer to the nearest node to the context element
  # that is a form element (going straight up the ancestor chain, and including the
  # element itself, if it is a form element), or, if there is no such form element,
  # to null.
  static::$form=($context && static::hasAncestor('form',$context)) ? $context : null;

  # Place into the input stream for the HTML parser just created the input. The
  # encoding confidence is irrelevant. Start the parser and let it run until it has
  # consumed all the characters just inserted into the input stream.
  # NOTE: The encoding confidence is ignored because everything is converted to
  # UTF-8.

  static::parse($data);

  # If there is a context element, return the child nodes of root, in tree order.
  # DEVIATION: Returns a document fragment instead.
  $output=static::$DOMFragment;
  static::$DOMFragment=null;
  static::$fragment=false;
  static::$context=null;

  return $output;
 }

 # Fix id attributes and join adjacent text nodes. To be used after the DOM is
 # manipulated and before outputting.
 protected static function fixDOM($dom=null)
 {
  if(!$dom)
   $dom=&static::$DOM;

  # Fix id attributes so they may be selected by the DOM.
  if(!static::$fragment) $dom->relaxNGValidateSource(static::$relaxNG);

  # Normalize the document before outputting.
  $dom->normalize();

  $output=$dom;
  static::$DOM=null;

  return $output;
 }

 protected static function tokenize($data)
 {
  while(true)
  {
   if(static::$debug)
    echo "state: ".static::$state."\n";

   switch(static::$state)
   {
    case 'data':
    {
     $char=static::consume();

     if($char=='&')
     {
      static::emitToken(array('type'=>'character',
                              'data'=>static::consumeEntity()));
     }
     elseif($char=='<')
      static::$state='tag open';
     elseif($char===false)
     {
      static::emitToken(array('type'=>'eof')); # EOF
      return false;
     }
     elseif($char=="\t" || $char=="\n" || $char=="\x0c" || $char==' ')
     {
      static::emitToken(array('type'=>'character',
                              'data'=>$char));
     }
     # Optimization. Consume as many characters that don't match the other checked
     # characters if they exist instead of looping around here again and again.
     else
     {
      static::emitToken(array('type'=>'character',
                              'data'=>$char.static::consumeUntil('&<')));
     }
    }
    break;

    case 'RCDATA':
    {
     $char=static::consume();

     if($char=='&')
     {
      static::emitToken(array('type'=>'character',
                              'data'=>static::consumeEntity()));
     }
     elseif($char=='<')
      static::$state='RCDATA less-than sign';
     elseif($char===false)
     {
      static::emitToken(array('type'=>'eof')); # EOF
      return false;
     }
     else
     {
      # Optimization. Consume as many characters that don't match the other checked
      # characters if they exist instead of looping around here again and again.
      static::emitToken(array('type'=>'character',
                              'data'=>$char.static::consumeUntil('&<')));
     }
    }
    break;

    case 'RAWTEXT':
    {
     $char=static::consume();

     if($char=='<')
      static::$state='RAWTEXT less-than sign';
     elseif($char===false) # EOF
     {
      static::emitToken(array('type'=>'eof'));
      return false;
     }
     else
     {
      # Optimization. Consume as many characters that don't match the other checked
      # characters if they exist instead of looping around here again and again.
      static::emitToken(array('type'=>'character',
                              'data'=>$char.static::consumeUntil('<')));
     }
    }
    break;

    case 'script data':
    {
     $char=static::consume();
     if($char=='<')
      static::$state='script data less-than sign';
     elseif($char===false) # EOF
     {
      static::emitToken(array('type'=>'eof'));
      return false;
     }
     else
     {
      # Optimization. Consume as many characters that don't match the other checked
      # characters if they exist instead of looping around here again and again.
      static::emitToken(array('type'=>'character',
                              'data'=>$char.static::consumeUntil('<')));
     }
    }
    break;

    case 'PLAINTEXT':
    {
     $char=static::consume();

     if($char===false) # EOF
     {
      static::emitToken(array('type'=>'eof'));
      return false;
     }
     else
     {
      # Optimization. Consume as many characters that don't match the other checked
      # characters if they exist instead of looping around here again and again.
      static::emitToken(array('type'=>'character',
                              'data'=>$char.static::consumeUntil('')));
     }
    }
    break;

    case 'tag open':
    {
     $char=static::consume();

     if($char=='!')
      static::$state='markup declaration open';
     elseif($char=='/')
      static::$state='end tag open';
     elseif(ctype_alpha($char)) # [A-Za-z]
     {
      # Faster to just strtolower everything than to check separately
      # for capital and lowercase.
      static::$token=array('type'=>'start tag',
                           'name'=>strtolower($char));

      static::$state='tag name';
     }
     elseif($char=='?')
     {
      static::parseError('tag name expected','?');
      static::$state='bogus comment';
     }
     else
     {
      if($char!==false)
       static::parseError('tag name expected',$char);
      else
       static::parseError('unexpected eof tag name');

      static::$state='data';
      static::emitToken(array('type'=>'character',
                              'data'=>'<'));
      static::unconsume();
     }
    }
    break;

    case 'end tag open':
    {
     $char=static::consume();

     if(ctype_alpha($char)) # [A-Za-z]
     {
      # Faster to just strtolower everything than to check separately
      # for capital and lowercase.
      static::$token=array('type'=>'end tag',
                           'name'=>strtolower($char));

      static::$state='tag name';
     }
     elseif($char=='>')
     {
      static::parseError('tag name expected','>');
      static::$state='data';
     }
     elseif($char===false) # EOF
     {
      static::parseError('unexpected eof tag name');

      static::$state='data';
      static::emitToken(array('type'=>'character',
                              'data'=>'</'));
      static::unconsume();
     }
     else
     {
      static::parseError('tag name expected',$char);
      static::$state='bogus comment';
     }
    }
    break;

    case 'tag name':
    {
     $char=static::consume();

     if($char=="\t" || $char=="\n" || $char=="\x0c" || $char==' ')
      static::$state='before attribute name';
     elseif($char=='/')
      static::$state='self-closing start tag';
     elseif($char=='>')
     {
      static::$state='data';
      static::emitToken(static::$token);
     }
     elseif($char===false) # EOF
     {
      static::parseError('unexpected eof tag name');
      static::$state='data';
      static::unconsume();
     }
     else
     {
      # Faster to just strtolower everything than to check separately
      # for capital letters.
      # Optimization. Consume as many characters that don't match the other checked
      # characters if they exist instead of looping around here again and again.
      static::$token['name'].=strtolower($char).static::consumeUntil(static::WHITESPACE."/>");
     }
    }
    break;

    case 'RCDATA less-than sign':
    {
     $char=static::consume();

     if($char=='/')
     {
      static::$buffer='';
      static::$state='RCDATA end tag open';
     }
     else
     {
      static::$state='RCDATA';
      static::emitToken(array('type'=>'character',
                            'data'=>'<'));
      static::unconsume();
     }
    }
    break;

    case 'RCDATA end tag open':
    {
     $char=static::consume();

     if(ctype_alpha($char)) # [A-Za-z]
     {
      # Faster to just strtolower everything than to check separately
      # for capital and lowercase.
      static::$token=array('type'=>'end tag',
                         'name'=>strtolower($char));
      static::$buffer.=$char;
      static::$state='RCDATA end tag name';
     }
     else
     {
      static::$state='RCDATA';
      static::emitToken(array('type'=>'character',
                            'data'=>'</'));
      static::unconsume();
     }
    }
    break;

    case 'RCDATA end tag name':
    {
     $char=static::consume();

     if(ctype_alpha($char)) # [A-Za-z]
     {
      # Faster to just strtolower everything than to check separately
      # for capital and lowercase.
      static::$token['name'].=strtolower($char);
      static::$buffer.=$char;
     }
     # If the current token is an appropriate end tag token.
     # Optimization. MUCH faster to check this first.
     elseif(static::$token['name']==static::$currentNodeName)
     {
      if($char=="\t" || $char=="\n" || $char=="\x0c" || $char==' ')
       static::$state='before attribute name';
      elseif($char=='/')
       static::$state='self-closing start tag';
      elseif($char=='>')
      {
       static::$state='data';
       static::emitToken(static::$token);
      }
     }
     else
     {
      static::$state='RCDATA';
      static::emitToken(array('type'=>'character',
                              'data'=>"</".static::$buffer));
      static::unconsume();
     }
    }
    break;

    case 'RAWTEXT less-than sign':
    {
     $char=static::consume();

     if($char=='/')
     {
      static::$buffer='';
      static::$state='RAWTEXT end tag open';
     }
     else
     {
      static::$state='RAWTEXT';
      static::emitToken(array('type'=>'character',
                            'data'=>'<'));
      static::unconsume();
     }
    }
    break;

    case 'RAWTEXT end tag open':
    {
     $char=static::consume();
     if(ctype_alpha($char)) # [A-Za-z]
     {
      # Faster to just strtolower everything than to check separately
      # for capital and lowercase.
      # Optimization. Consume as many alpha characters as possible.
      static::$token=array('type'=>'end tag',
                           'name'=>strtolower($char.static::consumeWhile(static::ALPHA)));
      static::$buffer.=$char;
      static::$state='RAWTEXT end tag name';
     }
     else
     {
      static::$state='RAWTEXT';
      static::emitToken(array('type'=>'character',
                            'data'=>'</'));
      static::unconsume();
     }
    }
    break;

    case 'RAWTEXT end tag name':
    {
     $char=static::consume();

     if(ctype_alpha($char)) # [A-Za-z]
     {
      # Faster to just strtolower everything than to check separately
      # for capital and lowercase.
      # Optimization. Consume as many alpha characters as possible.
      static::$token['name'].=strtolower($char.static::consumeWhile(static::ALPHA));
      static::$buffer.=$char;
     }
     # If the current token is an appropriate end tag token.
     # Optimization. MUCH faster to check this first.
     elseif(static::$token['name']==static::$currentNodeName)
     {
      if($char=="\t" || $char=="\n" || $char=="\x0c" || $char==' ')
       static::$state='before attribute name';
      elseif($char=='/')
       static::$state='self-closing start tag';
      elseif($char=='>')
      {
       static::$state='data';
       static::emitToken(static::$token);
      }
     }
     else
     {
      static::$state='RAWTEXT';
      static::emitToken(array('type'=>'character',
                            'data'=>"</".static::$buffer));
      static::unconsume();
     }
    }
    break;

    case 'script data less-than sign':
    {
     $char=static::consume();

     if($char=='/')
     {
      static::$buffer='';
      static::$state='script data end tag open';
     }
     elseif($char=='!')
     {
      static::$state='script data escape start';
      static::emitToken(array('type'=>'character',
                              'data'=>'<!'));
     }
     else
     {
      static::$state='script data';
      static::emitToken(array('type'=>'character',
                             'data'=>'<'));
      static::unconsume();
     }
    }
    break;

    case 'script data end tag open':
    {
     $char=static::consume();

     if(ctype_alpha($char)) # [A-Za-z]
     {
      # Faster to just strtolower everything than to check separately
      # for capital and lowercase.
      static::$token=array('type'=>'end tag',
                         'name'=>strtolower($char));
      static::$buffer.=$char;
      static::$state='script data end tag name';
     }
     else
     {
      static::$state='script data';
      static::emitToken(array('type'=>'character',
                            'data'=>'</'));
      static::unconsume();
     }
    }
    break;

    case 'script data end tag name':
    {
     $char=static::consume();

     if(ctype_alpha($char)) # [A-Za-z]
     {
      # Faster to just strtolower everything than to check separately
      # for capital and lowercase.
      static::$token['name'].=strtolower($char);
      static::$buffer.=$char;
     }
     # If the current token is an appropriate end tag token.
     # Optimization. MUCH faster to check this first.
     elseif(static::$token['name']==static::$currentNodeName)
     {
      if($char=="\t" || $char=="\n" || $char=="\x0c" || $char==' ')
       static::$state='before attribute name';
      elseif($char=='/')
       static::$state='self-closing start tag';
      elseif($char=='>')
      {
       static::$state='data';
       static::emitToken(static::$token);
      }
     }
     else
     {
      static::$state='script data';
      static::emitToken(array('type'=>'character',
                            'data'=>"</".static::$buffer));
      static::unconsume();
     }
    }
    break;

    case 'script data escape start':
    {
     $char=static::consume();

     if($char=='-')
     {
      static::$state='script data escape start dash';
      static::emitToken(array('type'=>'character',
                              'data'=>'-'));
     }
     else
     {
      static::$state='script data';
      static::unconsume();
     }
    }
    break;

    case 'script data escape start dash':
    {
     $char=static::consume();

     if($char=='-')
     {
      static::$state='script data escaped dash dash';
      static::emitToken(array('type'=>'character',
                              'data'=>'-'));
     }
     else
     {
      static::$state='script data';
      static::unconsume();
     }
    }
    break;

    case 'script data escaped':
    {
     $char=static::consume();

     if($char=='-')
     {
      static::$state='script data escaped dash';
      static::emitToken(array('type'=>'character',
                              'data'=>'-'));
     }
     elseif($char=='<')
      static::$state='script data escaped less-than sign';
     elseif($char===false) # EOF
     {
      static::parseError('unexpected eof escaped script data');
      static::$state='data';
      static::unconsume();
     }
     else
     {
      # Optimization. Consume as many characters that don't match the other checked
      # characters if they exist instead of looping around here again and again.
      static::emitToken(array('type'=>'character',
                              'data'=>$char.static::consumeUntil('-<')));
     }
    }
    break;

    case 'script data escaped dash':
    {
     $char=static::consume();

     if($char=='-')
     {
      static::$state='script data escaped dash dash';
      static::emitToken(array('type'=>'character',
                            'data'=>'-'));
     }
     elseif($char=='<')
      static::$state='script data escaped less-than sign';
     elseif($char===false) # EOF
     {
      static::parseError('unexpected eof escaped script data');
      static::$state='data';
      static::unconsume();
     }
     else
     {
      static::$state='script data escaped';
      static::emitToken(array('type'=>'character',
                            'data'=>$char));
     }
    }
    break;

    case 'script data escaped dash dash':
    {
     $char=static::consume();

     if($char=='-')
      static::emitToken(array('type'=>'character',
                              'data'=>'-'));
     elseif($char=='<')
      static::$state='script data escaped less-than sign';
     elseif($char=='>')
     {
      static::$state='script data';
      static::emitToken(array('type'=>'character',
                            'data'=>'>'));
     }
     elseif($char===false) # EOF
     {
      static::parseError('unexpected eof escaped script data');
      static::$state='data';
      static::unconsume();
     }
     else
     {
      static::$state='script data escaped';
      static::emitToken(array('type'=>'character',
                              'data'=>$char));
     }
    }
    break;

    case 'script data escaped less-than sign':
    {
     $char=static::consume();

     if($char=='/')
     {
      static::$buffer='';
      static::$state='script data escaped end tag open';
     }
     # Faster to just strtolower everything than to check separately
     # for capital and lowercase.
     elseif(ctype_alpha($char)) # [A-Za-z]
     {
      static::$buffer=strtolower($char);
      static::$state='script data double escape start';
      static::emitToken(array('type'=>'character',
                              'data'=>'<'.$char));
     }
     else
     {
      static::$state='script data escaped';
      static::emitToken(array('type'=>'character',
                            'data'=>'<'));
      static::unconsume();
     }
    }
    break;

    case 'script data escaped end tag open':
    {
     $char=static::consume();
     # Faster to just strtolower everything than to check separately
     # for capital and lowercase.
     if(ctype_alpha($char)) # [A-Za-z]
     {
      static::$token=array('type'=>'end tag',
                           'name'=>strtolower($char));
      static::$buffer.=$char;
      static::$state='script data escaped end tag name';
     }
     else
     {
      static::$state='script data escaped';
      static::emitToken(array('type'=>'character',
                            'data'=>'</'));
      static::unconsume();
     }
    }
    break;

    case 'script data escaped end tag name':
    {
     $char=static::consume();
     # Faster to just strtolower everything than to check separately
     # for capital and lowercase.
     if(ctype_alpha($char)) # [A-Za-z]
     {
      static::$token['name'].=strtolower($char);
      static::$buffer.=$char;
     }
     # If the current token is an appropriate end tag token.
     # Optimization. MUCH faster to check this first.
     elseif(static::$token['name']==static::$currentNodeName)
     {
      if($char=="\t" || $char=="\n" || $char=="\x0c" || $char==' ')
       static::$state='before attribute name';
      elseif($char=='/')
       static::$state='self-closing start tag';
      elseif($char=='>')
      {
       static::$state='data';
       static::emitToken(static::$token);
      }
     }
     else
     {
      static::$state='script data escaped';
      static::emitToken(array('type'=>'character',
                            'data'=>"</".static::$buffer));
      static::unconsume();
     }
    }
    break;

    case 'script data double escape start':
    {
     $char=static::consume();

     if($char=="\t" || $char=="\n" || $char=="\x0c" || $char==' ' || $char=='/' || $char=='>')
     {
      static::$state=(static::$buffer=='script') ? 'script data double escaped' : 'script data escaped';
      static::emitToken(array('type'=>'character',
                              'data'=>$char));
     }
     # Faster to just strtolower everything than to check separately
     # for capital and lowercase.
     elseif(ctype_alpha($char)) # [A-Za-z]
     {
      # Go ahead and consume everything that's ASCII alpha so this doesn't have to
      # repeatedly loop back.
      $char.=static::consumeWhile(static::ALPHA);

      # Append the lowercase version to the buffer.
      static::$buffer.=strtolower($char);
      # Emit upper and lower as character tokens.
      static::emitToken(array('type'=>'character',
                              'data'=>$char));
     }
     else
     {
      static::$state='script data escaped';
      static::unconsume();
     }
    }
    break;

    case 'script data double escaped':
    {
     $char=static::consume();

     if($char=='-')
     {
      static::$state='script data double escaped dash';
      static::emitToken(array('type'=>'character',
                            'data'=>'-'));
     }
     elseif($char=='<')
     {
      static::$state='script data double escaped less-than sign';
      static::emitToken(array('type'=>'character',
                            'data'=>'<'));
     }
     elseif($char===false) # EOF
     {
      static::parseError('unexpected eof double escaped script data');
      static::$state='data';
      static::unconsume();
     }
     else
     {
      # Optimization. Consume as many characters that don't match the other checked
      # characters if they exist instead of looping around here again and again.
      static::emitToken(array('type'=>'character',
                            'data'=>$char.static::consumeUntil('-<')));
     }
    }
    break;

    case 'script data double escaped dash':
    {
     $char=static::consume();

     if($char=='-')
     {
      static::$state='script data double escaped dash dash';
      static::emitToken(array('type'=>'character',
                            'data'=>'-'));
     }
     elseif($char=='<')
     {
      static::$state='script data double escaped less-than sign';
      static::emitToken(array('type'=>'character',
                            'data'=>'<'));
     }
     elseif($char===false) # EOF
     {
      static::parseError('unexpected eof double escaped script data');
      static::$state='data';
      static::unconsume();
     }
     else
     {
      static::$state='script data double escaped';
      static::emitToken(array('type'=>'character',
                            'data'=>$char));
     }
    }
    break;

    case 'script data double escaped dash dash':
    {
     $char=static::consume();

     if($char=='-')
     {
      static::emitToken(array('type'=>'character',
                            'data'=>'-'));
     }
     elseif($char=='<')
     {
      static::$state='script data double escaped less-than sign';
      static::emitToken(array('type'=>'character',
                            'data'=>'<'));
     }
     elseif($char=='>')
     {
      static::$state='script data';
      static::emitToken(array('type'=>'character',
                            'data'=>'>'));
     }
     elseif($char===false) # EOF
     {
      static::parseError('unexpected eof double escaped script data');
      static::$state='data';
      static::unconsume();
     }
     else
     {
      static::$state='script data double escaped';
      static::emitToken(array('type'=>'character',
                            'data'=>$char));
     }
    }
    break;

    case 'script data double escape less-than sign':
    {
     $char=static::consume();

     if($char=='/')
     {
      static::$buffer='';
      static::$state='script data double escape end';
      static::emitToken(array('type'=>'character',
                            'data'=>'/'));
     }
     else
     {
      static::$state='script data double escaped';
      static::unconsume();
     }
    }
    break;

    case 'script data double escape end':
    {
     $char=static::consume();

     if($char=="\t" || $char=="\n" || $char=="\x0c" || $char==' ' || $char=='/' || $char=='>')
     {
      static::$state=(static::$buffer=='script') ? 'script data escaped' : 'script data double escaped';
      static::emitToken(array('type'=>'character',
                            'data'=>$char));
     }
     # Faster to just strtolower everything than to check separately
     # for capital and lowercase.
     elseif(ctype_alpha($char)) # [A-Za-z]
     {
      static::$token['name'].=strtolower($char.static::consumeWhile(static::ALPHA));
      static::$buffer.=$char;
     }
     else
     {
      static::$state='script data double escaped';
      static::unconsume();
     }
    }
    break;

    case 'before attribute name':
    {
     $char=static::consume();

     if($char=="\t" || $char=="\n" || $char=="\x0c" || $char==' ')
      continue;
     elseif($char=='/')
      static::$state='self-closing start tag';
     elseif($char=='>')
     {
      static::$state='data';
      static::emitToken(static::$token);
     }
     # Faster to use ctype_upper than < & >.
     elseif(ctype_upper($char)) # [A-Z]
     {
      static::$attributenamebuffer=strtolower($char.static::consumeWhile(static::UPPER_ALPHA));
      static::$state='attribute name';
     }
     elseif($char===false) # EOF
     {
      static::parseError('unexpected eof attribute name');
      static::$state='data';
      static::unconsume();
     }
     elseif($char=='"' || $char=="'" || $char=='<' || $char=='=')
      static::parseError('attribute name expected',$char);
     else
     {
      # Optimization that makes checking for attribute name validity simpler.
      static::$attributenamebuffer=$char;
      static::$state='attribute name';
     }
    }
    break;

    case 'attribute name':
    {
     $char=static::consume();

     # The spec states to check the validity of the attribute name before
     # leaving the attribute name state or before emitting a token. Since
     # in this implementation the attribute name is stored in a buffer it's
     # only added if it is valid.

     # Conceded it was best to check the validity of the attribute name
     # within each if statement. Any other method was either much slower
     # or too cumbersome. It's repetitive code, but oh well.
     if($char=="\t" || $char=="\n" || $char=="\x0c" || $char==' ')
     {
      if(isset(static::$token['attributes'][static::$attributenamebuffer]))
       static::parseError('attribute exists',static::$attributenamebuffer);
      else
       static::$token['attributes'][static::$attributenamebuffer]=null;

      static::$state='after attribute name';
     }
     elseif($char=='/')
     {
      if(isset(static::$token['attributes'][static::$attributenamebuffer]))
       static::parseError('attribute exists',static::$attributenamebuffer);
      else
       static::$token['attributes'][static::$attributenamebuffer]=null;

      static::$state='self-closing start tag';
     }
     elseif($char=='=')
     {
      if(isset(static::$token['attributes'][static::$attributenamebuffer]))
       static::parseError('attribute exists',static::$attributenamebuffer);
      else
       static::$token['attributes'][static::$attributenamebuffer]=null;

      static::$state='before attribute value';
     }
     elseif($char=='>')
     {
      if(isset(static::$token['attributes'][static::$attributenamebuffer]))
       static::parseError('attribute exists',static::$attributenamebuffer);
      else
       static::$token['attributes'][static::$attributenamebuffer]=null;

      static::$state='data';
      static::emitToken(static::$token);
     }
     # Faster to use ctype_upper than < & >.
     # Optimization. Consume as many characters that don't match the other checked
     # characters if they exist instead of looping around here again and again.
     elseif(ctype_upper($char)) # [A-Z]
      static::$attributenamebuffer.=strtolower($char.static::consumeUntil(static::WHITESPACE."/=>\"'<"));
     elseif($char===false) # EOF
     {
      static::parseError('unexpected eof attribute name');
      static::$state='data';
      static::unconsume();
     }
     else
     {
      if($char=='"' || $char=="'" || $char=='<')
       static::parseError('attribute name expected',$char);

      # Optimization. Consume as many characters that don't match the other checked
      # characters if they exist instead of looping around here again and again.
      static::$attributenamebuffer.=$char.static::consumeUntil(static::WHITESPACE."/=>\"'<".static::UPPER_ALPHA);
     }
    }
    break;

    case 'after attribute name':
    {
     $char=static::consume();

     if($char=="\t" || $char=="\n" || $char=="\x0c" || $char==' ')
      continue;
     elseif($char=='/')
      static::$state='self-closing start tag';
     elseif($char=='=')
      static::$state='before attribute value';
     elseif($char=='>')
     {
      if(isset(static::$token['attributes'][static::$attributenamebuffer]))
       static::parseError('attribute exists',static::$attributenamebuffer);
      else
       static::$token['attributes'][static::$attributenamebuffer]=null;

      static::$state='data';
      static::emitToken(static::$token);
     }
     elseif($char===false) # EOF
     {
      static::parseError('unexpected eof attribute value tag end');
      static::$state='data';
      static::unconsume();
     }
     else
     {
      if($char=='"' || $char=="'" || $char=='<')
       static::parseError('attribute value tag end expected',$char);

      static::$attributenamebuffer=$char;
      static::$state='attribute name';
     }
    }
    break;

    case 'before attribute value':
    {
     $char=static::consume();

     if($char=="\t" || $char=="\n" || $char=="\x0c" || $char==' ')
      continue;
     elseif($char=='"')
      static::$state='attribute value (double-quoted)';
     elseif($char=='&')
     {
      static::$state='attribute value (unquoted)';
      static::unconsume();
     }
     elseif($char=="'")
      static::$state='attribute value (single-quoted)';
     elseif($char=='>')
     {
      static::parseError('attribute value expected',$char);
      static::$state='data';
      static::emitToken(static::$token);
     }
     elseif($char===false) # EOF
     {
      static::parseError('unexpected eof attribute value');
      static::$state='data';
      static::unconsume();
     }
     else
     {
      if($char=='<' || $char=='=' || $char=='`')
       static::parseError('attribute value expected',$char);

      static::$token['attributes'][static::$attributenamebuffer].=$char;
      static::$state='attribute value (unquoted)';
     }
    }
    break;

    case 'attribute value (double-quoted)':
    {
     $char=static::consume();
     if($char=='"')
     {
      static::$state='after attribute value (quoted)';

      # Set the attribute name to an empty string instead of null.
      $currentAttribute=static::$token['attributes'][static::$attributenamebuffer];
      if(is_null(static::$token['attributes'][static::$attributenamebuffer]))
       static::$token['attributes'][static::$attributenamebuffer]='';
     }

     # Instead of going to a separate state to consume the reference then
     # returning back to the this state just do it all here. Quicker.
     # Performs the actions of the 'character reference in attribute value'
     # state.
     elseif($char=='&')
      static::$token['attributes'][static::$attributenamebuffer].=static::consumeEntity('"',true);
     elseif($char===false) # EOF
     {
      static::parseError('double-quoted attribute value expected eof');
      static::$state='data';
      static::unconsume();
     }
     # Optimization. Consume as many characters that don't match the other checked
     # characters if they exist instead of looping around here again and again.
     else
      static::$token['attributes'][static::$attributenamebuffer].=$char.static::consumeUntil('"&');
    }
    break;

    case 'attribute value (single-quoted)':
    {
     $char=static::consume();

     if($char=="'")
     {
      static::$state='after attribute value (quoted)';

      # Set the attribute name to an empty string instead of null.
      static::$token['attributes'][static::$attributenamebuffer]='';
     }
     # Instead of going to a separate state to consume the reference then
     # returning back to the this state just do it all here. Quicker.
     # Performs the actions of the 'character reference in attribute value'
     # state.
     elseif($char=='&')
      static::$token['attributes'][static::$attributenamebuffer].=static::consumeEntity("'",true);
     elseif($char===false) # EOF
     {
      static::parseError('single-quoted attribute value expected eof');
      static::$state='data';
      static::unconsume();
     }
     # Optimization. Consume as many characters that don't match the other checked
     # characters if they exist instead of looping around here again and again.
     else
      static::$token['attributes'][static::$attributenamebuffer].=$char.static::consumeUntil("'&");
    }
    break;

    case 'attribute value (unquoted)':
    {
     $char=static::consume();

     if($char=="\t" || $char=="\n" || $char=="\x0c" || $char==' ')
      static::$state='before attribute name';
     # Instead of going to a separate state to consume the reference then
     # returning back to the this state just do it all here. Quicker.
     # Performs the actions of the 'character reference in attribute value'
     # state.
     elseif($char=='&')
      static::$token['attributes'][static::$attributenamebuffer].=static::consumeEntity('>',true);
     elseif($char=='>')
     {
      static::$state='data';
      static::emitToken(static::$token);
     }
     elseif($char===false) # EOF
     {
      static::parseError('unexpected eof unquoted attribute value');
      static::$state='data';
      static::unconsume();
     }
     else
     {
      if($char=='"' || $char=="'" || $char=='<' || $char=='=' || $char=='`')
       static::parseError('unquoted attribute value expected',$char);

      # Optimization. Consume as many characters that don't match the other checked
      # characters if they exist instead of looping around here again and again.
      static::$token['attributes'][static::$attributenamebuffer].=$char.static::consumeUntil(static::WHITESPACE."&\"'<>=`");
     }
    }
    break;

    case 'after attribute value (quoted)':
    {
     $char=static::consume();

     if($char=="\t" || $char=="\n" || $char=="\x0c" || $char==' ')
      static::$state='before attribute name';
     elseif($char=='/')
      static::$state='self-closing start tag';
     elseif($char=='>')
     {
      static::$state='data';
      static::emitToken(static::$token);
     }
     elseif($char===false) # EOF
     {
      static::parseError('unexpected eof attribute name tag end');
      static::$state='data';
      static::unconsume();
     }
     else
     {
      static::parseError('attribute name tag end expected',$char);
      static::$state='before attribute name';
      static::unconsume();
     }
    }
    break;

    case 'self-closing start tag':
    {
     $char=static::consume();

     if($char=='>')
     {
      static::$token['selfClosing']=true;
      static::$state='data';
      static::emitToken(static::$token);
     }
     elseif($char===false) # EOF
     {
      static::parseError('unexpected eof attribute name tag end');
      static::$state='data';
      static::unconsume();
     }
     else
     {
      static::parseError('attribute name tag end expected',$char);
      static::$state='before attribute name';
      static::unconsume();
     }
    }
    break;

    case 'bogus comment':
    {
     # Consume every character up to and including the first greater than sign.
     # Data for token contains the character which caused the state machine to
     # switch into the bogus comment state, in other words the last character
     # within $char. Data then includes the characters except the trailing '>'.
     $char=$char.static::consumeUntil('>'); # Consumes everything to '>'.
     $check=static::consume(); # Consumes the greater than sign.

     # If not EOF emit comment token. If EOF emit empty comment token,
     # switch to the data state, and unconsume the character.
     if($check!==false)
     {
      static::$state='data';
      static::emitToken(array('type'=>'comment',
                              'data'=>$char));
     }
     else
     {
      static::$state='data';
      static::emitToken(array('type'=>'comment',
                            'data'=>''));
      static::unconsume();
     }
    }
    break;

    case 'markup declaration open':
    {
     # If the next 2 characters are -- consume those characters, create a
     # comment token, and switch to the comment start state.

     if(static::peek(2)=='--')
     {
      static::consume(2);
      static::$token=array('type'=>'comment',
                           'data'=>'');
      static::$state='comment start';
     }
     # Otherwise if the next 7 characters case-insensitively equal 'doctype'
     # then consume those 7 characters and switch to the doctype state.
     else if(strtolower(static::peek(7))=='doctype')
     {
      static::consume(7);
      static::$state='DOCTYPE';
     }
     # Otherwise if the last open element in the stack is not in the HTML
     # namespace and the next seven characters are a case-sensitive match
     # for the string "[CDATA[" then consume those characters and switch
     # to the CDATA section state.

     # TODO: After tree building is implemented check for namespaces here.
     elseif(static::peek(7)=='[CDATA[')
     {
      static::consume(7);
      static::$state='CDATA section';
     }
     # Otherwise trigger a parse error. Switch to the bogus comment state.
     else
     {
      static::parseError('doctype dashes cdata expected',$char);
      static::$state='bogus comment';
     }
    }
    break;

    case 'comment start':
    {
     $char=static::consume();

     if($char=='-')
      static::$state='comment start dash';
     elseif($char=='>')
     {
      static::parseError('comment expected','>');
      static::$token['data'].='>';
      static::$state='data';
      static::emitToken(static::$token);
     }
     elseif($char===false) # EOF
     {
      static::parseError('unexpected eof comment');
      static::$state='data';
      static::emitToken(static::$token);
      static::unconsume();
     }
     else
     {
      static::$token['data'].=$char;
      static::$state='comment';
     }
    }
    break;

    case 'comment start dash':
    {
     $char=static::consume();

     if($char=='-')
      static::$state='comment end';
     elseif($char=='>')
     {
      static::parseError('comment expected','>');
      static::$state='data';
      static::emitToken(static::$token);
     }
     elseif($char===false) # EOF
     {
      static::parseError('unexpected eof comment');
      static::$state='data';
      static::emitToken(static::$token);
      static::unconsume();
     }
     else
     {
      static::$token['data'].="-".$char;
      static::$state='comment';
     }
    }
    break;

    case 'comment':
    {
     $char=static::consume();

     if($char=='-')
      static::$state='comment end dash';
     elseif($char===false) # EOF
     {
      static::parseError('unexpected eof comment');
      static::$state='data';
      static::emitToken(static::$token);
      static::unconsume();
     }
     # Optimization. Consume as many characters that don't match the other checked
     # characters if they exist instead of looping around here again and again.
     else
      static::$token['data'].=$char.static::consumeUntil('-');
    }
    break;

    case 'comment end dash':
    {
     $char=static::consume();

     if($char=='-')
      static::$state='comment end';
     elseif($char===false) # EOF
     {
      static::parseError('unexpected eof comment');
      static::$state='data';
      static::emitToken(static::$token);
      static::unconsume();
     }
     else
     {
      static::$token['data'].="-".$char;
      static::$state='comment';
     }
    }
    break;

    case 'comment end':
    {
     $char=static::consume();

     if($char=='>')
     {
      static::$state='data';
      static::emitToken(static::$token);
     }
     elseif($char=='!')
     {
      static::parseError('comment end expected','!');
      static::$state='comment end bang';
     }
     elseif($char=='-')
     {
      static::parseError('comment end expected','-');
      static::$token['data'].='-';
     }
     elseif($char===false) # EOF
     {
      static::parseError('unexpected eof comment end');
      static::$state='data';
      static::emitToken(static::$token);
      static::unconsume();
     }
     else
     {
      static::parseError('comment end expected',$char);
      static::$token['data'].="--".$char;
      static::$state='comment';
     }
    }
    break;

    case 'comment end bang':
    {
     $char=static::consume();

     if($char=='-')
     {
      static::$token['data'].="--!";
      static::$state='comment end dash';
     }
     elseif($char=='>')
     {
      static::$state='data';
      static::emitToken(static::$token);
     }
     elseif($char===false) # EOF
     {
      static::parseError('unexpected eof comment end');
      static::$state='data';
      static::emitToken(static::$token);
      static::unconsume();
     }
     else
     {
      static::$token['data'].="--!".$char;
      static::$state='comment';
     }
    }
    break;

    case 'DOCTYPE':
    {
     $char=static::consume();

     if($char=="\t" || $char=="\n" || $char=="\x0c" || $char==' ')
      static::$state='before DOCTYPE name';
     elseif($char===false) # EOF
     {
      static::parseError('unexpected eof doctype name');
      static::$state='data';
      static::emitToken(static::$token);
      static::unconsume();
     }
     else
     {
      # Spec states to trigger a parse error here, but it's unnecessary since
      # the same damn error's going to be triggered in the 'before DOCTYPE name'
      # state.
      static::$state='before DOCTYPE name';
      static::unconsume();
     }
    }
    break;

    case 'before DOCTYPE name':
    {
     $char=static::consume();

     if($char=="\t" || $char=="\n" || $char=="\x0c" || $char==' ')
      continue;
     elseif($char=='>')
     {
      static::parseError('DOCTYPE name expected','>');
      static::$state='data';
      static::emitToken(array('type'=>'DOCTYPE',
                              'quirksMode'=>true));
      # NOTE: Don't want quirks, but leaving this here for the moment.
     }
     elseif($char===false) # EOF
     {
      static::parseError('unexpected eof doctype name');
      static::$state='data';
      static::emitToken(array('type'=>'DOCTYPE',
                              'quirksMode'=>true));
      # NOTE: Don't want quirks, but leaving this here for the moment.

      static::unconsume();
     }
     else
     {
      # Optimization. Faster to strtolower everything than to check
      # for capital letters first as it takes less time to change the
      # case than it does to check for it.
      static::$token=array('type'=>'DOCTYPE',
                           'name'=>strtolower($char));

      static::$state='DOCTYPE name';
     }
    }
    break;

    case 'DOCTYPE name':
    {
     $char=static::consume();

     if($char=="\t" || $char=="\n" || $char=="\x0c" || $char==' ')
      static::$state='after DOCTYPE name';
     elseif($char=='>')
     {
      static::$state='data';
      static::emitToken(static::$token);
     }
     elseif($char===false) # EOF
     {
      static::parseError('unexpected eof doctype name');
      static::$token['quirksMode']=true;
      # NOTE: Don't want quirks, but leaving this here for the moment.
      static::$state='data';
      static::emitToken(static::$token);
      static::unconsume();
     }
     # Optimization. Faster to strtolower everything than to check
     # for capital letters first as it takes less time to change the
     # case than it does to check for it.

     # Optimization. Consume as many characters that don't match the other checked
     # characters if they exist instead of looping around here again and again.
     # Strtolower that, too.
     else
      static::$token['name'].=strtolower($char.static::consumeUntil(static::WHITESPACE.'>'));
    }
    break;

    case 'after DOCTYPE name':
    {
     $char=static::consume();

     if($char=="\t" || $char=="\n" || $char=="\x0c" || $char==' ')
      continue;
     elseif($char=='>')
     {
      static::$state='data';
      static::emitToken(static::$token);
     }
     elseif($char===false)
     {
      static::parseError('unexpected eof doctype keyword end tag');
      static::$token['quirksMode']=true;
      # NOTE: Don't want quirks, but leaving this here for the moment.
      static::$state='data';
      static::emitToken(static::$token);
      static::unconsume();
     }
     else
     {
      # Optimization. More times than not there's not going to be 'publicID' here, so
      # checking just the current input character first is quicker in most cases.
      if(strtolower($char)=='p')
      {
       if(strtolower($char.static::peek(5))=='public')
       {
        static::consume(5);
        static::$state='after DOCTYPE public keyword';
       }
      }
      # Optimization. More times than not there's not going to be 'systemID' here, so
      # checking just the current input character first is quicker in most cases.
      elseif(strtolower($char)=='s')
      {
       if(strtolower($char.static::peek(5))=='systemID')
       {
        static::consume(5);
        static::$state='after DOCTYPE system keyword';
       }
      }
      else
      {
       static::parseError('doctype keyword end tag expected',$char);
       static::$token['quirksMode']=true;
       # NOTE: Don't want quirks, but leaving this here for the moment.
       static::$state='bogus DOCTYPE';
      }
     }
    }
    break;

    case 'after DOCTYPE public keyword':
    {
     $char=static::consume();

     if($char=="\t" || $char=="\n" || $char=="\x0c" || $char==' ')
      static::$state='before DOCTYPE public identifier';
     elseif($char=='"')
     {
      static::parseError('doctype public identifier expected','"');
      static::$token['publicID']="";
      static::$state='DOCTYPE public identifier (double-quoted)';
     }
     elseif($char=="'")
     {
      static::parseError('doctype public identifier expected',"'");
      static::$token['publicID']="";
      static::$state='DOCTYPE public identifier (single-quoted)';
     }
     elseif($char=='>')
     {
      static::parseError('doctype public identifier expected','>');
      static::$state='data';
      static::$token['quirksMode']=true;
      # NOTE: Don't want quirks, but leaving this here for the moment.
      static::emitToken(static::$token);
     }
     elseif($char===false) # EOF
     {
      static::parseError('unexpected eof doctype public identifier');
      static::$token['quirksMode']=true;
      # NOTE: Don't want quirks, but leaving this here for the moment.
      static::$state='data';
      static::emitToken(static::$token);
      static::unconsume();
     }
     else
     {
      static::parseError('doctype public identifier expected',$char);
      static::$token['quirksMode']=true;
      # NOTE: Don't want quirks, but leaving this here for the moment.
      static::$state='bogus DOCTYPE';
     }
    }
    break;

    case 'before DOCTYPE public identifier':
    {
     $char=static::consume();

     if($char=="\t" || $char=="\n" || $char=="\x0c" || $char==' ')
      continue;
     elseif($char=='"')
     {
      static::$token['publicID']="";
      static::$state='DOCTYPE public identifier (double-quoted)';
     }
     elseif($char=="'")
     {
      static::$token['publicID']="";
      static::$state='DOCTYPE public identifier (single-quoted)';
     }
     elseif($char=='>')
     {
      static::parseError('doctype public identifier expected','>');
      static::$token['quirksMode']=true;
      # NOTE: Don't want quirks, but leaving this here for the moment.
      static::$state='data';
     }
     elseif($char===false) # EOF
     {
      static::parseError('unexpected eof doctype public identifier');
      static::$token['quirksMode']=true;
      # NOTE: Don't want quirks, but leaving this here for the moment.
      static::$state='data';
      static::emitToken(static::$token);
      static::unconsume();
     }
     else
     {
      static::parseError('doctype public identifier expected',$char);
      static::$token['quirksMode']=true;
      # NOTE: Don't want quirks, but leaving this here for the moment.
      static::$state='bogus DOCTYPE';
     }
    }
    break;

    case 'DOCTYPE public identifier (double-quoted)':
    {
     $char=static::consume();

     if($char=='"')
      static::$state='after DOCTYPE public identifier';
     elseif($char=='>')
     {
      static::parseError('double-quoted doctype public identifier expected','>');
      static::$token['quirksMode']=true;
      # NOTE: Don't want quirks, but leaving this here for the moment.
      static::$state='data';
      static::emitToken(static::$token);
     }
     elseif($char===false) # EOF
     {
      static::parseError('unexpected eof doctype public identifier');
      static::$token['quirksMode']=true;
      # NOTE: Don't want quirks, but leaving this here for the moment.
      static::$state='data';
      static::emitToken(static::$token);
      static::unconsume();
     }
     # Optimization. Consume as many characters that don't match the other checked
     # characters if they exist instead of looping around here again and again.
     else
      static::$token['publicID'].=$char.static::consumeUntil('">');
    }
    break;

    case 'DOCTYPE public identifier (single-quoted)':
    {
     $char=static::consume();

     if($char=="'")
      static::$state='after DOCTYPE public identifier';
     elseif($char=='>')
     {
      static::parseError('single-quoted doctype public identifier expected','>');
      static::$token['quirksMode']=true;
      # NOTE: Don't want quirks, but leaving this here for the moment.
      static::$state='data';
      static::emitToken(static::$token);
     }
     elseif($char===false) # EOF
     {
      static::parseError('single-quoted doctype public identifier expected eof');
      static::$token['quirksMode']=true;
      # NOTE: Don't want quirks, but leaving this here for the moment.
      static::$state='data';
      static::emitToken(static::$token);
      static::unconsume();
     }
     # Optimization. Consume as many characters that don't match the other checked
     # characters if they exist instead of looping around here again and again.
     else
      static::$token['publicID'].=$char.static::consumeUntil("'>");
    }
    break;

    case 'after DOCTYPE public identifier':
    {
     $char=static::consume();

     if($char=="\t" || $char=="\n" || $char=="\x0c" || $char==' ')
      static::$state='between DOCTYPE public and system identifiers';
     elseif($char=='>')
     {
      static::$state='data';
      static::emitToken(static::$token);
     }
     elseif($char=='"')
     {
      static::parseError('doctype system identifier expected','"');
      static::$token['systemID']="";
      static::$state='DOCTYPE system identifier (double-quoted)';
     }
     elseif($char=="'")
     {
      static::parseError('doctype system identifier expected',"'");
      static::$token['systemID']="";
      static::$state='DOCTYPE system identifier (single-quoted)';
     }
     elseif($char===false) # EOF
     {
      static::parseError('unexpected eof doctype system identifier');
      static::$token['quirksMode']=true;
      # NOTE: Don't want quirks, but leaving this here for the moment.
      static::$state='data';
      static::emitToken(static::$token);
      static::unconsume();
     }
     else
     {
      static::parseError('doctype system identifier expected',$char);
      static::$token['quirksMode']=true;
      # NOTE: Don't want quirks, but leaving this here for the moment.
      static::$state='bogus DOCTYPE';
     }
    }
    break;

    case 'between DOCTYPE public and system identifiers':
    {
     $char=static::consume();

     if($char=="\t" || $char=="\n" || $char=="\x0c" || $char==' ')
      continue;
     elseif($char=='>')
     {
      static::$state='data';
      static::emitToken(static::$token);
     }
     elseif($char=='"')
     {
      static::$token['systemID']="";
      static::$state='DOCTYPE system identifier (double-quoted)';
     }
     elseif($char=="'")
     {
      static::$token['systemID']="";
      static::$state='DOCTYPE system identifier (single-quoted)';
     }
     elseif($char===false) # EOF
     {
      static::parseError('unexpected eof doctype system identifier');
      static::$token['quirksMode']=true;
      # NOTE: Don't want quirks, but leaving this here for the moment.
      static::$state='data';
      static::emitToken(static::$token);
      static::unconsume();
     }
     else
     {
      static::parseError('doctype system identifier expected',$char);
      static::$token['quirksMode']=true;
      # NOTE: Don't want quirks, but leaving this here for the moment.
      static::$state='bogus DOCTYPE';
     }
    }
    break;

    case 'after DOCTYPE system keyword':
    {
     $char=static::consume();

     if($char=="\t" || $char=="\n" || $char=="\x0c" || $char==' ')
      static::$state='before DOCTYPE system identifier';
     elseif($char=='"')
     {
      static::parseError('doctype system identifier expected','"');
      static::$token['systemID']="";
      static::$state='DOCTYPE system identifier (double-quoted)';
     }
     elseif($char=="'")
     {
      static::parseError('doctype system identifier expected',"'");
      static::$token['systemID']="";
      static::$state='DOCTYPE system identifier (single-quoted)';
     }
     elseif($char=='>')
     {
      static::parseError('doctype system identifier expected','>');
      static::$token['quirksMode']=true;
      # NOTE: Don't want quirks, but leaving this here for the moment.
      static::$state='data';
      static::emitToken(static::$token);
     }
     elseif($char===false)
     {
      static::parseError('unexpected eof DOCTYPE system identifier');
      static::$token['quirksMode']=true;
      # NOTE: Don't want quirks, but leaving this here for the moment.
      static::$state='data';
      static::emitToken(static::$token);
      static::unconsume();
     }
     else
     {
      static::parseError('doctype system identifier expected',$char);
      static::$token['quirksMode']=true;
      # NOTE: Don't want quirks, but leaving this here for the moment.
      static::$state='bogus DOCTYPE';
     }
    }
    break;

    case 'before DOCTYPE system identifier':
    {
     $char=static::consume();

     if($char=="\t" || $char=="\n" || $char=="\x0c" || $char==' ')
      continue;
     elseif($char=='"')
     {
      static::$token['systemID']="";
      static::$state='DOCTYPE system identifier (double-quoted)';
     }
     elseif($char=="'")
     {
      static::$token['systemID']="";
      static::$state='DOCTYPE system identifier (single-quoted)';
     }
     elseif($char=='>')
     {
      static::parseError('doctype system identifier expected','>');
      static::$token['quirksMode']=true;
      # NOTE: Don't want quirks, but leaving this here for the moment.
      static::$state='data';
      static::emitToken(static::$token);
     }
     elseif($char===false) # EOF
     {
      static::parseError('unexpected eof DOCTYPE system identifier');
      static::$token['quirksMode']=true;
      # NOTE: Don't want quirks, but leaving this here for the moment.
      static::$state='data';
      static::emitToken(static::$token);
      static::unconsume();
     }
     else
     {
      static::parseError('doctype system identifier expected',$char);
      static::$token['quirksMode']=true;
      # NOTE: Don't want quirks, but leaving this here for the moment.
      static::$state='bogus DOCTYPE';
     }
    }
    break;

    case 'DOCTYPE system identifier (double-quoted)':
    {
     $char=static::consume();

     if($char=='"')
      static::$state='after DOCTYPE system identifier';
     elseif($char=='>')
     {
      static::parseError('double-quoted doctype system identifier expected','>');
      static::$token['quirksMode']=true;
      # NOTE: Don't want quirks, but leaving this here for the moment.
      static::$state='data';
      static::emitToken(static::$token);
     }
     elseif($char===false) # EOF
     {
      static::parseError('double-quoted doctype system identifier expected eof');
      static::$token['quirksMode']=true;
      # NOTE: Don't want quirks, but leaving this here for the moment.
      static::$state='data';
      static::emitToken(static::$token);
      static::unconsume();
     }
     # Optimization. Consume as many characters that don't match the other checked
     # characters if they exist instead of looping around here again and again.
     else
      static::$token['systemID'].=$char.static::consumeUntil('">');
    }
    break;

    case 'DOCTYPE system identifier (single-quoted)':
    {
     $char=static::consume();

     if($char=="'")
      static::$state='after DOCTYPE system identifier';
     elseif($char=='>')
     {
      static::parseError('single-quoted doctype system identifier expected','>');
      static::$token['quirksMode']=true;
      # NOTE: Don't want quirks, but leaving this here for the moment.
      static::$state='data';
      static::emitToken(static::$token);
     }
     elseif($char===false) # EOF
     {
      static::parseError('single-quoted doctype system identifier expected eof');
      static::$token['quirksMode']=true;
      # NOTE: Don't want quirks, but leaving this here for the moment.
      static::$state='data';
      static::emitToken(static::$token);
      static::unconsume();
     }
     # Optimization. Consume as many characters that don't match the other checked
     # characters if they exist instead of looping around here again and again.
     else
      static::$token['systemID'].=$char.static::consumeUntil("'>");
    }
    break;

    case 'after DOCTYPE system identifier':
    {
     $char=static::consume();

     if($char=="\t" || $char=="\n" || $char=="\x0c" || $char==' ')
      continue;
     elseif($char=='>')
     {
      static::$state='data';
      static::emitToken(static::$token);
     }
     elseif($char===false) # EOF
     {
      static::parseError('unexpected eof tag end');
      static::$token['quirksMode']=true;
      # NOTE: Don't want quirks, but leaving this here for the moment.
      static::$state='data';
      static::emitToken(static::$token);
      static::unconsume();
     }
     else
     {
      static::parseError('tag end expected',$char);
      static::$state='bogus DOCTYPE';
     }
    }
    break;

    case 'bogus DOCTYPE':
    {
     # Optimization. Consume as many characters that don't match the other checked
     # characters if they exist instead of looping around here again and again.
     static::consumeUntil('>');
     $char=static::consume();

     if($char===false)
     {
      static::$state='data';
      static::emitToken(static::$token);
      static::unconsume();
     }
     elseif($char=='>')
     {
      static::$state='data';
      static::emitToken(static::$token);
     }
    }
    break;

    case 'CDATA section':
    {
     # Consume every character up until the next occurrence of ']]>' or EOF.
     # Emit the consumed characters except the ']]>'.
     $char='';
     while(true)
     {
      # Grab everything up until a ']' or EOF.
      $char.=static::consumeUntil(']');
      $temp=static::peek(3);

      if($temp===false) # EOF
      {
       static::unconsume();
       # Emit consumed characters as a character token then break out of the while loop.
       static::emitToken(array('type'=>'character',
                             'data'=>$char));
       break 3;
      }
      elseif($temp==']]>')
      {
       # Emit consumed characters as a character token then break out of the while loop.
       static::emitToken(array('type'=>'character',
                             'data'=>$char));
       break;
      }
      # If ']]>' or EOF not encountered then consume the next character and start over.
      else
       $char.=static::consume();
     }

     # Lastly switch to the data state.
     static::$state='data';
    }
    break;

   }
  }
 }

 # Method to print the DOM tree out as text.
 # @param $context Context node.
 # @param $options Optional options for the printer.
 static function serialize($context,$options=array())
 {
  $nodeType=$context->nodeType;
  if($nodeType!=XML_ELEMENT_NODE && $nodeType!=XML_DOCUMENT_NODE && $nodeType!=XML_DOCUMENT_FRAG_NODE && $nodeType!=XML_TEXT_NODE)
  {
   switch($nodeType)
   {
    case XML_ATTRIBUTE_NODE: $nodeType='DOMAttr';
    break;
    case XML_CDATA_SECTION_NODE: $nodeType='DOMCdataSection';
    break;
    case XML_ENTITY_REF_NODE: $nodeType='DOMEntityReference';
    break;
    case XML_ENTITY_NODE: $nodeType='DOMEntity';
    break;
    case XML_PI_NODE: $nodeType='DOMProcessingInstruction';
    break;
    case XML_COMMENT_NODE: $nodeType='DOMComment';
    break;
    case XML_DOCUMENT_TYPE_NODE: $nodeType='DOMDocumentType';
    break;
    case XML_NOTATION: $nodeType='DOMNotation';
    break;
    default: $nodeType='null';
   }

   static::fatalError('domelement document frag expected',__METHOD__,$nodeType);
  }

  $attributeQuotes='"';
  $prettyPrint=false;
  $indentSpaces=1;
  $indentStep=' ';

  if(isset($options['attributeQuotes']))
  {
   $type=gettype($options['attributeQuotes']);
   if($type!='boolean' && $type!='integer' && $type!='double' && $type!='string')
   {
    if($type=='object')
     $type=get_class($options['attributeQuotes']);
    static::fatalError('invalid option value type',__METHOD__,'attributeQuotes','string',$type);
   }

   $attributeQuotes=strtolower($options['attributeQuotes']);
   switch($attributeQuotes)
   {
    case '"':
    case 'double': $attributeQuotes='"';
    break;
    case "'":
    case 'single': $attributeQuotes="'";
    break;
    case 'none':
    case '0':
    case '': $attributeQuotes='';
    break;
    default: static::fatalError('invalid option value',__METHOD__,'attributeQuotes',"'double','single', or 'none'",$attributeQuotes);
   }
  }

  if(isset($options['prettyPrint']))
  {
   $type=gettype($options['prettyPrint']);
   if($type!='boolean' && $type!='integer' && $type!='double' && $type!='string')
   {
    if($type=='object')
     $type=get_class($options['prettyPrint']);
    static::fatalError('invalid option value type',__METHOD__,'prettyPrint','boolean',$type);
   }

   $prettyPrint=(bool)$options['prettyPrint'];
  }

  if(isset($options['indentSpaces']))
  {
   $type=gettype($options['indentSpaces']);
   if($type!='boolean' && $type!='integer' && $type!='double' && $type!='string')
   {
    if($type=='object')
     $type=get_class($options['indentSpaces']);
    static::fatalError('invalid option value type',__METHOD__,'indentSpaces','integer',$type);
   }

   $indentSpaces=(int)$options['indentSpaces'];
   $indentStep=str_repeat(' ',$indentSpaces);
  }

  if($nodeType!=XML_DOCUMENT_NODE && $nodeType!=XML_DOCUMENT_FRAG_NODE)
  {
   $frag=$context->ownerDocument->createDocumentFragment();
   $frag->appendChild($context->cloneNode(true));
   $context=$frag;
  }

  return static::serializer($context,$attributeQuotes,$prettyPrint,$indentSpaces,$indentStep);
 }

 # Private method used recursively to serialize a document or node.
 private static function serializer($context,$attributeQuotes,$prettyPrint,$indentSpaces,$indentStep)
 {
  static $foreignAncestor=false;
  static $foreignNode=null;
  static $scriptAncestor=false;
  static $scriptNode=null;

  if($prettyPrint)
  {
   static $indent='';
   static $preAncestor=false;
   static $preNode=null;
   static $headAncestor=false;
   static $headNode=null;
   static $inlineWithBlockElementSiblings=false;
   static $inlineWithBlockElementSiblingsParent=null;
   static $foreignAncestorWithBlockElementSiblings=false;
   static $inlineWithBlockElementDescendants=false;
   static $inlineWithBlockElementDescendantsNode=null;
   static $commentWithBlockElementSiblings=false;
   static $commentWithBlockElementSiblingsParent=null;
  }

  if (static::$debug) {
      echo "printing: ";
      echo $context->nodeName;
      echo "\n";
  }

  if($context->hasChildNodes())
  {
   $output="";

   foreach($context->childNodes as $index=>$node)
   {
    if($prettyPrint)
    {
     $blockElement=false;
     $modify=false;
    }

    switch($node->nodeType)
    {
     case XML_ELEMENT_NODE:
     {
      # If current node is an element in the HTML namespace, the MathML
      # namespace, or the SVG namespace, then let tagname be current node's
      # local name. Otherwise, let tagname be current node's qualified name.
      $namespace=$node->namespaceURI;

      if (static::$debug) {
          echo "namespace: ";
          echo $namespace;
          echo "\n";
      }

      if($namespace=='http://www.w3.org/1998/Math/MathML' || $namespace=='http://www.w3.org/2000/svg')
      {
       if(!$foreignAncestor)
        $foreignNode=$node;
       $foreignAncestor=true;
       # Using localName here because it "fixes" a bug where when manipulating the DOM
       # with SVG and MathML stuff it puts default namespace prefixes in. Nasty.
       $tagName=$node->localName;
      }
      elseif(is_null($namespace))
       $tagName=$node->tagName;
      else
       $tagName=$node->prefix.':'.$node->tagName;

      /* $colonPos = strpos($tagName, ':');
      if ($colonPos !== false) {
          $tagName = substr($tagName, $colonPos+1);
      }*/

      if(in_array($tagName,static::$scriptElements,true))
      {
       $scriptAncestor=true;
       $scriptNode=$node;
      }

      if($prettyPrint)
      {
       if(!$preAncestor)
       {
        if($scriptAncestor)
         $modify=true;

        if(in_array($tagName,static::$preElements,true))
        {
         $preAncestor=true;
         $modify=true;
         $preNode=$node;
        }

        if((!$foreignAncestor && !$blockElement && (($headAncestor && in_array($tagName,static::$headBlockElements)) || in_array($tagName,static::$blockElements))))
        {
         $blockElement=true;
         $modify=true;
        }

        if($headAncestor)
         $modify=true;
        elseif(!$headAncestor && $tagName==='head')
        {
         $headAncestor=true;
         $headNode=$node;
         $modify=true;
        }

        if(!$blockElement)
        {
         if(!$inlineWithBlockElementSiblings)
         {
          if(($headAncestor && static::hasSibling(static::$headBlockElements,$node)) || static::hasSibling(static::$blockElements,$node))
          {
           $modify=true;
           $inlineWithBlockElementSiblings=true;
           $inlineWithBlockElementSiblingsParent=$node->parentNode;
          }
         }
         else
         {
          if($node->parentNode->isSameNode($inlineWithBlockElementSiblingsParent))
           $modify=true;
          elseif(($headAncestor && static::hasSibling(static::$headBlockElements,$node)) || static::hasSibling(static::$blockElements,$node))
          {
           $modify=true;
           $inlineWithBlockElementSiblings=true;
           $inlineWithBlockElementSiblingsParent=$node->parentNode;
          }
          else
          {
           $inlineWithBlockElementSiblings=false;
           $inlineWithBlockElementSiblingsParent=null;
          }

          if(!$inlineWithBlockElementDescendants && static::hasDescendant(static::$blockElements,$node))
          {
           $modify=true;
           $inlineWithBlockElementDescendants=true;
           $inlineWithBlockElementDescendantsNode=$node;
          }
         }

         if ($foreignAncestorWithBlockElementSiblings) {
             $modify=true;
         } elseif ($foreignNode && $node->isSameNode($foreignNode) && ($inlineWithBlockElementSiblings || (in_array($node->parentNode->nodeName, static::$blockElements) && $node->isSameNode(static::firstNonWhitespaceTextNodeChild($node->parentNode)) && $node->isSameNode(static::lastNonWhitespaceTextNodeChild($node->parentNode))) || static::hasSibling(static::$blockElements,$foreignNode->parentNode))) {
             $modify = true;
             $foreignAncestorWithBlockElementSiblings = true;
         }
        }
       }

       if($modify)
       {
        $output.="\n".$indent;

        if($headAncestor && $tagName!=='head' && in_array($tagName,static::$headBlockElements) && !in_array(static::prevNonWhitespaceTextNodeChild($node)->nodeName,static::$headBlockElements))
         $output.="\n".$indent;
       }
      }

      $output.="<".$tagName;

      if($node->hasAttributes())
      {
       foreach($node->attributes as $index=>$attr)
       {
        # For each attribute that the element has, append a U+0020 SPACE
        # character, the attribute's serialized name as described below, a
        # U+003D EQUALS SIGN character (=), a U+0022 QUOTATION MARK character
        # ("), the attribute's value, escaped as described below in attribute
        # mode, and a second U+0022 QUOTATION MARK character (").

        $output.=' ';

        switch($attr->namespaceURI)
        {
         case null: $output.=$attr->name;
         break;
         case 'http://www.w3.org/XML/1998/namespace': $output.='xml:'.$attr->name;
         break;
         case 'http://www.w3.org/2000/xmlns/': $output.=($attr->name=='xmlns') ? 'xmlns' : 'xmlns:'.$attr->name;
         break;
         case 'http://www.w3.org/1999/xlink': $output.='xlink:'.$attr->name;
         break;
         default: $output.=$attr->prefix.':'.$attr->name;
        }

        if($foreignAncestor==true)
         $output.='="'.static::escapeString($attr->value,'"').'"';
        elseif($attr->value!=$attr->name)
         $output.='='.$attributeQuotes.static::escapeString($attr->value,$attributeQuotes).$attributeQuotes;
       }
      }

      if($foreignAncestor==true && !$node->hasChildNodes())
      {
       $output.='/>';
       if($node->isSameNode($foreignNode))
       {
        $foreignAncestor=false;
        $foreignNode=null;
        $foreignAncestorWithBlockElementSiblings=false;
       }

       goto serializeCleanUp;
      }

      # Append a U+003E GREATER-THAN SIGN character (>).
      $output.='>';

      if($foreignAncestor==false)
      {
       # If current node is an area, base, basefont, bgsound, br, col, command,
       # embed, frame, hr, img, input, keygen, link, meta, param, source, track
       # or wbr element, then continue on to the next child node at this point.
       if(in_array($tagName,static::$selfClosingElements))
       {
        if($prettyPrint)
        {
         # Make the markup easier to read by adding additional whitespace.
         if($blockElement && (($headAncestor && in_array($tagName,static::$headBlockElements)) || in_array($tagName,static::$spacedBlockElements)))
         {
          $nextChildName = static::nextNonWhitespaceTextNodeChild($node);
          if ($nextChildName) {
              $nextChildName = $nextChildName->nodeName;

              if(strpos($nextChildName,'#')===false && static::lastNonWhitespaceTextNodeChild($node->parentNode)!==$node)
              {
               if($tagName=='h1' || $tagName=='h2' || $tagName=='h3' || $tagName=='h4' || $tagName=='h5' || $tagName=='h6')
               {
                if($nextChildName!='h1' && $nextChildName!='h2' && $nextChildName!='h3' && $nextChildName!='h4' && $nextChildName!='h5' && $nextChildName!='h6')
                 $output.="\n";
               }
               elseif($nextChildName!=$tagName)
                $output.="\n";
              }
             }
          }
        }

        goto serializeCleanUp;
       }
      }

      if($prettyPrint && $modify)
       $indent.=$indentStep;

      # Append the value of running the HTML fragment serialization algorithm
      # on the current node element (thus recursing into this algorithm for
      # that element), followed by a U+003C LESS-THAN SIGN character (<), a
      # U+002F SOLIDUS character (/), tagname again, and finally a U+003E
      # GREATER-THAN SIGN character (>).
      $output.=static::serializer($node,$attributeQuotes,$prettyPrint,$indentSpaces,$indentStep);

      if($prettyPrint && $modify)
      {
       $indent=substr($indent,0,0-$indentSpaces);

       if(!$preAncestor &&
          (!$foreignAncestor && ($tagName=='head' || static::hasDescendant(static::$blockElements,$node))) ||
          (static::hasSibling(static::$blockElements,$node) && static::hasChild(['math','svg'], $node)) ||
          ($foreignAncestorWithBlockElementSiblings && static::hasDescendant(function($context)
          {return ($context->nodeType==XML_ELEMENT_NODE) ? true : false;},$node))) {
              $output.="\n".$indent;
          }
      }

      $output.="</".$tagName.">";

      if($prettyPrint)
      {
       # Make the markup easier to read by adding additional whitespace.
       if($blockElement && (($headAncestor && in_array($tagName,static::$headBlockElements)) || in_array($tagName,static::$spacedBlockElements)))
       {
        $nextChildName = static::nextNonWhitespaceTextNodeChild($node);
        if ($nextChildName) {
            $nextChildName = $nextChildName->nodeName;

            if(strpos($nextChildName,'#text')===false && !static::lastNonWhitespaceTextNodeChild($node->parentNode)->isSameNode($node))
            {

             if($tagName=='h1' || $tagName=='h2' || $tagName=='h3' || $tagName=='h4' || $tagName=='h5' || $tagName=='h6')
             {
              if($nextChildName!='h1' && $nextChildName!='h2' && $nextChildName!='h3' && $nextChildName!='h4' && $nextChildName!='h5' && $nextChildName!='h6')
               $output.="\n";
             }
             elseif($nextChildName!==$tagName)
              $output.="\n";
            }
           }
        }
      }

      serializeCleanUp:
      if($scriptAncestor && $node->isSameNode($scriptNode))
      {
       $scriptAncestor=false;
       $scriptNode=null;
      }

      if($prettyPrint)
      {
       if($preAncestor && $node->isSameNode($preNode))
       {
        $preAncestor=false;
        $preNode=null;
        break;
       }

       if(!$preAncestor)
       {
        if(!$foreignAncestor)
        {
         if($headAncestor && $node->isSameNode($headNode))
         {
          $headAncestor=false;
          $headNode=null;
          break;
         }

         if($inlineWithBlockElementSiblings &&
            $node->parentNode!=$inlineWithBlockElementSiblingsParent)
         {
          $inlineWithBlockElementSiblings=false;
          $inlineWithBlockElementSiblingsParent=null;
         }

         if($commentWithBlockElementSiblings &&
            $node->parentNode!=$commentWithBlockElementSiblingsParent)
         {
          $commentWithBlockElementSiblings=false;
          $commentWithBlockElementSiblingsParent=null;
         }

         if($inlineWithBlockElementDescendants && $node->isSameNode($inlineWithBlockElementDescendantsNode))
         {
          $inlineWithBlockElementDescendants=false;
          $inlineWithBlockElementDescendantsNode=null;
         }

        }
        elseif($node->isSameNode($foreignNode))
        {
         $isForeign=false;
         $foreignNode=null;
         $foreignAncestor=false;
         $foreignAncestorWithBlockElementSiblings=false;
        }
       }
      }
      elseif($foreignAncestor && $node->isSameNode($foreignNode))
      {
       $isForeign=false;
       $foreignNode=null;
       $foreignAncestor=false;
       $foreignAncestorWithBlockElementSiblings=false;
      }
     }
     break;

     case XML_TEXT_NODE:
     {
      $nodeData=$node->data;

      if($prettyPrint && !$preAncestor && !$scriptAncestor)
      {
       if(($foreignAncestor || in_array($node->parentNode->nodeName,static::$blockElements)) &&
          static::hasSibling(static::$blockElements,$node) &&
          preg_match(static::WHITESPACEREGEX,$nodeData)>0)
        continue 2;

       $data=preg_replace(array('/[\n\r]/','/( ){2,}/'),array('','$1'),str_replace("\t",'    ',$nodeData));
       if($data=='')
        continue 2;
       if($data!=$nodeData)
        $nodeData=$data;
      }

      if($scriptAncestor)
      {
       # If the script ancestor is a script element and the node data isn't escaped then escape the data.
       $scriptNodeName=$scriptNode->nodeName;
       if($scriptNodeName==='script' && strpos(trim($nodeData),'<!--')!==0)
        $nodeData=static::escapeString($nodeData);

       # Escape strings that look like the script node's end tag.
       $endTag='</'.$scriptNodeName.'>';
       if(strpos($nodeData,$endTag)!==false)
        $nodeData=str_replace($endTag,'&lt;'.$scriptNodeName.'&gt;',$nodeData);
      }
      else
       $nodeData=static::escapeString($nodeData);

      $output.=$nodeData;
     }
     break;

     case XML_CDATA_SECTION_NODE:
     {
      if($prettyPrint && !$preAncestor)
      {
       if(!$modify)
       {
        if($headAncestor || $inlineWithBlockElementSiblings || $commentWithBlockElementSiblings)
         $modify=true;
        elseif(static::hasSibling(static::$blockElements,$node))
        {
         $modify=true;
         $commentWithBlockElementSiblings=true;
         $commentWithBlockElementSiblingsParent=$node->parentNode;
        }
       }

       if($modify)
        $output.="\n".$indent;
      }
      $output.=static::escapeString($node->data);
     }
     break;

     case XML_COMMENT_NODE:
     {
      if($prettyPrint && !$preAncestor)
      {
       if(!$modify)
       {
        if($headAncestor || $inlineWithBlockElementSiblings || $commentWithBlockElementSiblings)
         $modify=true;
        elseif(static::hasSibling(static::$blockElements,$node))
        {
         $modify=true;
         $commentWithBlockElementSiblings=true;
         $commentWithBlockElementSiblingsParent=$node->parentNode;
        }
       }

       if($modify)
        $output.="\n".$indent;
      }
      $output.="<!--".$node->data."-->";
     }
     break;

     case XML_ENTITY_REF_NODE: $output.='&'.$node->nodeName.';';
     break;

     case XML_PI_NODE:
     {
      if($prettyPrint && !$preAncestor)
      {
       if(!$modify)
       {
        if($headAncestor || $inlineWithBlockElementSiblings || $commentWithBlockElementSiblings)
         $modify=true;
        elseif(static::hasSibling(static::$blockElements,$node))
        {
         $modify=true;
         $commentWithBlockElementSiblings=true;
         $commentWithBlockElementSiblingsParent=$node->parentNode;
        }
       }

       if($modify)
        $output.="\n".$indent;
      }
      $output.="<?".$node->target." ".$node->data.">";
     }
     break;

     case XML_DOCUMENT_TYPE_NODE: $output.="<!DOCTYPE ".$node->name.">";
     break;
    }
   }

   return $output;
  }
  else
   return false;
 }

 public static function hasAncestor($needle,$context,&$match=null)
 {
  if(!$context->nodeType)
   static::fatalError('domnode expected',__METHOD__,'null');

  $callback=static::getSelectorCallback($needle,__METHOD__);

  while($context=$context->parentNode)
  {
   hasAncestorLoop:
   $result=$callback($context);

   if(is_bool($result))
   {
    if($result)
    {
     $match=$context;
     return true;
    }
   }
   elseif(is_int($result))
   {
    if($result)
     $match=$context;
    return (bool)$result;
   }
   elseif($result instanceof DOMNode)
   {
    $context=$result;
    goto hasAncestorLoop;
   }
  }

  return false;
 }

 public static function hasDescendant($needle,$context,&$match=null)
 {
  $nodeType=$context->nodeType;
  if(!$context->nodeType)
   static::fatalError('domelement document frag expected',__METHOD__,$nodeType);

  if(!$context->hasChildNodes())
   return false;

  $callback=static::getSelectorCallback($needle,__METHOD__);
  $context=$context->firstChild;
  do
  {
   //hasDescendantLoop:
   $result=$callback($context);

   if(is_bool($result))
   {
    if($result)
    {
     $match=$context;
     return true;
    }
   }
   elseif(is_int($result))
   {
    if($result)
     $match=$context;
    return (bool)$result;
   }
   elseif($result instanceof DOMNode)
   {
    $context=$result;
    //goto hasDescendantLoop;
   }

   if(static::hasDescendant($callback,$context,$match))
    return true;
  }
  while($context=$context->nextSibling);

  return false;
 }

 public static function hasChild($needle,$context,&$match=null)
 {
  $nodeType=$context->nodeType;
  if(!$context->nodeType)
   static::fatalError('domelement document frag expected',__METHOD__,$nodeType);

  if(!$context->hasChildNodes())
   return false;

  $callback=static::getSelectorCallback($needle,__METHOD__);
  $context=$context->firstChild;
  if($context)
  {
   do
   {
    hasChildLoop:
    $result=$callback($context);

    if(is_bool($result))
    {
     if($result)
     {
      $match=$context;
      return true;
     }
    }
    elseif(is_int($result))
    {
     if($result)
      $match=$context;
     return (bool)$result;
    }
    elseif($result instanceof DOMNode)
    {
     $context=$result;
     goto hasChildLoop;
    }
   }
   while($context=$context->nextSibling);
  }

  return false;
 }

 public static function hasChildReverse($needle,$context,&$match=null)
 {
  $nodeType=$context->nodeType;
  if(!$context->nodeType)
   static::fatalError('domelement document frag expected',__METHOD__,$nodeType);

  if(!$context->hasChildNodes())
   return false;

  $callback=static::getSelectorCallback($needle,__METHOD__);
  $context=$context->lastChild;
  if($context)
  {
   do
   {
    hasChildReverseLoop:
    $result=$callback($context);

    if(is_bool($result))
    {
     if($result)
     {
      $match=$context;
      return true;
     }
    }
    elseif(is_int($result))
    {
     if($result)
      $match=$context;
     return (bool)$result;
    }
    elseif($result instanceof DOMNode)
    {
     $context=$result;
     goto hasChildReverseLoop;
    }
   }
   while($context=$context->previousSibling);
  }

  return false;
 }

 public static function hasSibling($needle,$context,&$match=null)
 {
  if(!$context->nodeType)
   static::fatalError('domnode expected',__METHOD__,'null');

  $callback=static::getSelectorCallback($needle,__METHOD__);

  $original=$context;
  $context=$context->parentNode->firstChild;

  do
  {
   hasSiblingLoop:
   if($context===$original)
    continue;

   $result=$callback($context);

   if(is_bool($result))
   {
    if($result)
    {
     $match=$context;
     return true;
    }
   }
   elseif(is_int($result))
   {
    if($result)
     $match=$context;
    return (bool)$result;
   }
   elseif($result instanceof DOMNode)
   {
    $context=$result;
    goto hasSiblingLoop;
   }
  }
  while($context=$context->nextSibling);

  return false;
 }

 public static function hasPrecedingSibling($needle,$context,&$match=null)
 {
  if(!$context->nodeType)
   static::fatalError('domnode expected',__METHOD__,'null');

  $callback=static::getSelectorCallback($needle,__METHOD__);

  while($context=$context->previousSibling)
  {
   hasPrecedingSiblingLoop:
   $result=$callback($context);

   if(is_bool($result))
   {
    if($result)
    {
     $match=$context;
     return true;
    }
   }
   elseif(is_int($result))
   {
    if($result)
     $match=$context;
    return (bool)$result;
   }
   elseif($result instanceof DOMNode)
   {
    $context=$result;
    goto hasPrecedingSiblingLoop;
   }
  }

  return false;
 }

 public static function hasFollowingSibling($context,$needle,&$match=null)
 {
  if(!$context->nodeType)
   static::fatalError('domnode expected',__METHOD__,'null');

  $callback=static::getSelectorCallback($needle,__METHOD__);

  while($context=$context->nextSibling)
  {
   hasFollowingSiblingLoop:
   $result=$callback($context);

   if(is_bool($result))
   {
    if($result)
    {
     $match=$context;
     return true;
    }
   }
   elseif(is_int($result))
   {
    if($result)
     $match=$context;
    return (bool)$result;
   }
   elseif($result instanceof DOMNode)
   {
    $context=$result;
    goto hasFollowingSiblingLoop;
   }
  }

  return false;
 }

 public static function prevNonWhiteSpaceTextNodeChild($node)
 {
  if(!$node->nodeType)
   static::fatalError('domnode expected',__METHOD__,'null');

  while($node=$node->previousSibling)
  {
   if($node->nodeType==XML_TEXT_NODE && preg_match(static::WHITESPACEREGEX,$node->data)>0)
    continue;

   return $node;
  }
  return null;
 }

 public static function nextNonWhiteSpaceTextNodeChild($node)
 {
  if(!$node->nodeType)
   static::fatalError('domnode expected',__METHOD__,'null');

  while($node=$node->nextSibling)
  {
   if($node->nodeType==XML_TEXT_NODE && preg_match(static::WHITESPACEREGEX,$node->data)>0)
    continue;

   return $node;
  }
  return null;
 }

 public static function lastNonWhitespaceTextNodeChild($node)
 {
  $nodeType=$node->nodeType;
  if($nodeType!=XML_ELEMENT_NODE && $nodeType!=XML_DOCUMENT_NODE && $nodeType!=XML_DOCUMENT_FRAG_NODE)
  {
   if(!$nodeType)
    static::fatalError('domelement document frag expected',__METHOD__,'null');

   return false;
  }

  $node=$node->lastChild;
  do
  {
   if($node->nodeType==XML_TEXT_NODE && preg_match(static::WHITESPACEREGEX,$node->data)>0)
    continue;

   return $node;
  }
  while($node=$node->previousSibling);

  return null;
 }

 public static function firstNonWhitespaceTextNodeChild($node)
 {
  $nodeType=$node->nodeType;

  if($nodeType!=XML_ELEMENT_NODE && $nodeType!=XML_DOCUMENT_NODE && $nodeType!=XML_DOCUMENT_FRAG_NODE)
  {
   switch($nodeType)
   {
    case XML_ATTRIBUTE_NODE: $nodeType='DOMAttr';
    break;
    case XML_TEXT_NODE: $nodeType='DOMText';
    break;
    case XML_CDATA_SECTION_NODE: $nodeType='DOMCdataSection';
    break;
    case XML_ENTITY_REF_NODE: $nodeType='DOMEntityReference';
    break;
    case XML_ENTITY_NODE: $nodeType='DOMEntity';
    break;
    case XML_PI_NODE: $nodeType='DOMProcessingInstruction';
    break;
    case XML_COMMENT_NODE: $nodeType='DOMComment';
    break;
    case XML_DOCUMENT_TYPE_NODE: $nodeType='DOMDocumentType';
    break;
    case XML_NOTATION: $nodeType='DOMNotation';
    break;
    default: $nodeType='null';
   }

   static::fatalError('domelement document frag expected',__METHOD__,$nodeType);
  }

  $node=$node->firstChild;
  do
  {
   if($node->nodeType==XML_TEXT_NODE && preg_match(static::WHITESPACEREGEX,$node->data)>0)
    continue;

   return $node;

   break;
  }
  while($node=$node->nextSibling);

  return false;
 }

 protected static function getSelectorCallback($needle,$method=null,$type=null)
 {
  if(!$type)
   $type=gettype($needle);
  switch($type)
  {
   case 'boolean':
   case 'integer':
   case 'double':
   case 'string':
   {
    return function($context) use($needle)
     {return $context->nodeName==(string)$needle;};
   }
   break;

   case 'array':
   {
    return function($context) use($needle)
     {return in_array($context->nodeName,$needle);};
   }
   break;

   case 'object':
   {
    if(!$needle instanceof Closure)
     static::fatalError('closure expected',$method,get_class($needle));
   }

   case 'closure': return $needle;
   break;

   case 'default': static::fatalError('string array closure expected',$method,$type);
  }
 }

 # Walk through the DOM and perform actions.
 # @param $context Node to check.
 # @param $callback Callback function to check with.
 public static function walk($callback,$context)
 {
  $nodeType=$context->nodeType;
  if($nodeType!=XML_ELEMENT_NODE && $nodeType!=XML_DOCUMENT_NODE && $nodeType!=XML_DOCUMENT_FRAG_NODE)
   return;

  $node=$context->firstChild;
  if($node)
  {
   do
   {
    walkLoop:
    //$next=$node->nextSibling;
    $result=$callback($node);

    if(!$result)
     return;
    elseif(!$result->isSameNode($node))
    {
     $node=$result;
     //if($result===$next)
      goto walkLoop;
    }

    if($node->nodeType==XML_ELEMENT_NODE)
     static::walk($callback,$node);
   }
   while($node=$node->nextSibling);
  }
 }

 # Walk sideways through the DOM and perform actions.
 # @param $context Node to check.
 # @param $callback Callback function to check with.
 public static function sideWalk($callback,$context)
 {
  $nodeType=$context->nodeType;
  if($nodeType!=XML_ELEMENT_NODE && $nodeType!=XML_DOCUMENT_NODE && $nodeType!=XML_DOCUMENT_FRAG_NODE)
   static::fatalError('domdocument expected',__METHOD__);

  while($node=$node->nextSibling)
  {
   sideWalkLoop:
   $result=$callback($node);
   if(!$result)
    return;
   elseif(!$result->isSameNode($node))
   {
    $node=$result;
    goto sideWalkLoop;
   }
  }
 }

 # Walk through the provided node's children and perform actions.
 # @param $context Node to check.
 # @param $callback Callback function to check with.
 public static function stroll($callback,$context)
 {
  $nodeType=$context->nodeType;
  if($nodeType!=XML_ELEMENT_NODE && $nodeType!=XML_DOCUMENT_NODE && $nodeType!=XML_DOCUMENT_FRAG_NODE)
   static::fatalError('domdocument expected',__METHOD__);

  $node=$context->firstChild;
  if($node)
  {
   do
   {
    strollLoop:
    $result=$callback($node);
    if(!$result)
     return;
    elseif(!$result->isSameNode($node))
    {
     $node=$result;
     goto strollLoop;
    }
   }
   while($node=$node->nextSibling);
  }
 }

 public static function moonWalk($callback,$context)
 {
  if(!$context->nodeType)
   static::fatalError('domnode expected',__METHOD__,'null');

  while($context=$context->parentNode)
  {
   moonWalkLoop:
   $result=$callback($context);
   if(!$result)
    return;
   elseif(!$result->isSameNode($context))
   {
    $context=$result;
    goto moonWalkLoop;
   }
  }
 }

 protected static function escapeString($string,$attrMode=false)
 {
  if($string=='')
   return $string;

  # preg_replaces are necessary because the document is unicode. There's no
  # mb_str_replace, and using regular str_replaces can present problems.
  $string=preg_replace(array('/&/u','/\xa0/u'),array('&amp;','&nbsp;'),$string);
  //$string=str_replace(array('&',"\xa0"),array('&amp;','&nbsp;'),$string);

  if($attrMode===false)
   $string=preg_replace(array('/</u','/>/u'),array('&lt;','&gt;'),$string);
  elseif($attrMode=='"')
   $string=preg_replace('/"/u','&quot;',$string);
  elseif($attrMode=="'")
   $string=preg_replace('/\'/u','&apos;',$string);
  elseif($attrMode==='' || is_null($attrMode))
   $string=preg_replace(array('/ /u','/</u','/>/u'),array('&#32;','&lt;','&gt;'),$string);

  return $string;
 }

 # Method to process the input stream.
 # @param $data The data stream to process.
 protected static function processInputStream($data)
 {
  # The spec states to optionally detect the character encoding and then use
  # it. It also states that user agents must support at least UTF-8 and
  # Windows-1252. This will only ever support UTF-8, so we will make sure the
  # input will be UTF-8 before continuing.
  $data=mb_convert_encoding($data,'UTF-8',mb_detect_encoding($data));
  mb_internal_encoding('UTF-8');

  # Remove byte order mark if present.
  if(substr($data,0,3)==="\xEF\xBB\xBF")
   $data=substr($data,3);

  # Write errors if control or permanently undefined unicode characters are
  # present. The spec states to just trigger parse errors, but I'm also
  # removing them from the document. It's stupid and inefficient to leave
  # them in there and work around them if they're invalid since the class
  # won't work with dynamically changing documents. The spec specifies to do
  # U+0001 to U+0008 in this step. I'm starting from U+0000 because NULL
  # characters should be removed as well.
  $count=0;
  $data=preg_replace_callback('/(?:[\x00-\x08\x0B\x0E-\x1F\x7F]|\xC2[\x80-\x9F]|\xED(?:\xA0[\x80-\xFF]|[\xA1-\xBE][\x00-\xFF]|\xBF[\x00-\xBF])|\xEF\xB7[\x90-\xAF]|\xEF\xBF[\xBE\xBF]|[\xF0-\xF4][\x8F-\xBF]\xBF[\xBE\xBF])/',
                              function($matches) use(&$count)
                              {
                               $count++;
                               return '';
                              },$data);

  for($loop=0;$loop<$count;$loop++)
   {static::parseError('control or noncharacters');}

  # Normalize line breaks. Convert CRLF and CR to LF.
  # Break the document into a unicode friendly array of single characters for
  # tokenization.
  static::$data=preg_split('/(?<!^)(?!$)/u',str_replace(array("\r\n","\r"),"\n",$data));

  # Set EOF to the string length of the document.
  static::$EOF=sizeof(static::$data);
 }

 # Returns the next character(s). Consumes them by moving the pointer ahead a specified number of steps.
 # @param $length Number of characters to grab.
 # @param $consume Flag specifying whether to consume. Defaults to true.
 protected static function consume($length=1)
 {
  if($length<=0)
   static::fatalError('invalid consume length',__METHOD__);
  if(static::$pointer+1>static::$EOF)
   return false;

  $output='';
  $end=static::$pointer+$length;
  for($loop=static::$pointer;$loop<$end;$loop++)
   {$output.=static::$data[$loop];}
  static::$pointer=$end;
  return $output;
 }

 # Returns the next character(s). It does not move the pointer ahead.
 protected static function peek($length=1)
 {
  if($length<=0)
   static::fatalError('invalid peek length',__METHOD__);
  if(static::$pointer+1>static::$EOF)
   return false;

  $output='';
  $end=static::$pointer+$length;
  for($loop=static::$pointer;$loop<$end;$loop++)
   {$output.=static::$data[$loop];}
  return $output;
 }

 # Unconsumes the current consume character.
 protected static function unconsume($length=1)
  {if(static::$pointer<static::$EOF) static::$pointer-=$length;}

 # Finds the length of the initial segment of a string consisting entirely of
 # characters contained within a given mask. Exists here because PHP's strspn
 # isn't unicode friendly, and there's no mbstring alternative.
 protected static function mb_strspn($match,$start=0,$length=0)
 {
  $output=0;

  # Break the matching characters into an array of characters. Unicode friendly.
  $match=preg_split('/(?<!^)(?!$)/u',$match);
  while(true)
  {
   $char=static::$data[$start];
   if($char=='')
    break;
   if(!in_array($char,$match))
    break;

   $output++;
   $start++;

   if($output==$length)
    break;
  }
  return $output;
 }

 # Find length of initial segment not matching mask. Exists here because PHP's
 # strcspn isn't unicode friendly, and there's no mbstring alternative.
 protected static function mb_strcspn($match,$start=0,$length=0)
 {
  $output=0;

  # Break the matching characters into an array of characters. Unicode friendly.
  $match=preg_split('/(?<!^)(?!$)/u',$match);
  while(true)
  {
   if (!isset(static::$data[$start]) || static::$data[$start] == '' || in_array(static::$data[$start],$match)) {
       break;
   }

   $output++;
   $start++;

   if($output==$length)
    break;
  }
  return $output;
 }

 protected static function consumeWhile($match,$limit=0)
 {
  if(static::$pointer>static::$EOF)
   return false;

  $length=static::mb_strspn($match,static::$pointer,$limit);

  $output='';
  $end=static::$pointer+$length;
  for($loop=static::$pointer;$loop<$end;$loop++)
   {$output.=static::$data[$loop];}

  static::$pointer+=$length;

  return $output;
 }

 protected static function consumeUntil($match,$limit=0)
 {
  if(static::$pointer>static::$EOF)
   return false;

  $length=static::mb_strcspn($match,static::$pointer,$limit);

  $output='';
  $end=static::$pointer+$length;
  for($loop=static::$pointer;$loop<$end;$loop++)
   {$output.=static::$data[$loop];}

  static::$pointer+=$length;

  return $output;
 }

 protected static function peekWhile($match,$limit=0)
 {
  if(static::$pointer>static::$EOF)
   return false;

  $length=static::mb_strspn($match,static::$pointer,$limit);

  $output='';
  $end=static::$pointer+$length;
  for($loop=static::$pointer;$loop<$end;$loop++)
   {$output.=static::$data[$loop];}

  return $output;
 }

 protected static function peekUntil($match,$limit=0)
 {
  if(static::$pointer>static::$EOF)
   return false;

  $length=static::mb_strcspn($match,static::$pointer,$limit);

  $output='';
  $end=static::$pointer+$length;
  for($loop=static::$pointer;$loop<$end;$loop++)
   {$output.=static::$data[$loop];}

  return $output;
 }

 # Consumes a character reference.
 protected static function consumeEntity($allowedChar=false,$inattr=false)
 {
  # Grab the next character without consuming.
  $char=static::peek();

  # Optimization: When the spec states to return nothing this function will
  # return '&' as every use of this function checks to see if it returns
  # nothing then tells it to substitute an ampersand. Common sense.

  # If the next character is one of: U+0009 CHARACTER TABULATION,
  # U+000A LINE FEED (LF), U+000C FORM FEED (FF), U+0020 SPACE,
  # U+003C LESS-THAN, U+0026 AMPERSAND, or EOF it's not a character
  # reference. Return nothing.
  if($char=="\x09" || $char=="\x0A" || $char=="\x0C" || $char=="\x20" || $char=='<' || $char=='&' || $char===false || $char==$allowedChar)
   return '&';

  switch($char)
  {
   case '#':
   {
    # If the next character is a number sign consume it.
    static::consume();

    # Grab the next character without consuming.
    $char=static::peek();
    if($char==='x' || $char==='X')
    {
     # If the next character is 'x' or 'X' consume it.
     static::consume();

     # Consume the following as a hexadecimal number.
     $number=static::consumeWhile(static::HEX);

     $hex=true;
    }
    else
    {
     # Consume the following as a decimal number.
     $number=static::consumeWhile(static::DIGIT);
     $hex=false;
    }

    if($number==='' || $number===false)
    {
     # If nothing is matched then trigger a parse error.
     static::parseError('numeric entity expected');

     # Return nothing.
     return '&';
    }
    else
    {
     # If the next character is a semicolon then consume it otherwise
     # trigger a parse error.
     $check=static::peek();
     if($check==';')
      static::consume();
     else
      static::parseError('semicolon terminator expected');

     # Interpret the number as either a hexadecimal or decimal number.
     $number=($hex) ? hexdec($number) : (int)$number;

     # If the number is a key in the above array then trigger a parse error.
     if(isset(static::$entityReplacementTable[$number]))
     {
      static::parseError('invalid entity');

      # Return the character which corresponds to the number key in the array.
      return static::$entityReplacementTable[$number];
     }
     elseif(($number>=0x0000 && $number<=0x0008) || $number===0x000B ||
            ($number>=0x000E && $number<=0x001F) ||
            ($number>=0x007F && $number<=0x009F) ||
            ($number>=0xD800 && $number<=0xDFFF) ||
            ($number & 0xFFFE)===0xFFFE || $number>0x10FFFF)
     {
      static::parseError('illegal codepoint');

      # Return a replacement character.
      return "\xEF\xBF\xBD";
     }

     # Return the character which corresponds to the numerical codepoint.
     return mb_convert_encoding(pack("N",$number),'UTF8','UCS-4BE');
    }
   }
   break;

   default:
   {
    # Named character references.

    # Grab as many alphanumeric characters as possible up until the string
    # length of the longest named character reference. Calculated using:
    # max(array_map('strlen',array_keys($namedrefs)));
    # No need to calculate it every time as the array is static.
    $char=static::peekWhile(static::DIGIT.static::ALPHA.';',32);

    # Lob a character off the end of the grabbed string until a match is found.
    $charArray=preg_split('/(?<!^)(?!$)/u',$char);
    $len=mb_strlen($char,'UTF-8');
    $key=$char;
    for($loop=$len;$loop>0;$loop--)
    {
     if(isset(static::$entities[$key]))
     {
      # If the string's last character is not a semicolon then trigger a parse
      # error.
      $end=end($charArray);
      if($end!==';')
      {
       static::parseError('semicolon terminator expected',$end);

       # If in an attribute and the next character matches [A-Za-z0-9=]
       # return nothing.
       if($inattr && preg_match('/[A-Za-z0-9=]/',$key))
        return '&';
      }

      # Consume the character reference now that we know everything's okay.
      static::consume($loop);

      # Return the character(s) the character reference references.
      return static::$entities[$key];
     }
     array_pop($charArray);
     $key=implode($charArray);
    }

    # If characters immediately after the ampersand match '[A-Za-z0-9]+;'
    # trigger an error.
    if(preg_match('/^[A-Za-z0-9]+;/',$char)>0)
     static::parseError('invalid named entity');

    # Consume nothing and return nothing if no valid named reference was found.
    return '&';
   }
  }
 }

 # Emits a token to the DOM tree.
 # @param $token The token to emit.
 # @param $mode Mode to emit the token under. Defaults to null.
 protected static function emitToken($token,$mode=null)
 {
  if(static::$debug) {
      echo "token: ";
      var_export($token);
      echo "\n";
  }

  # Unset and extract the array so tons of comparisons aren't done on slower arrays.
  $type = null;
  $data = null;
  $name = null;
  $attributes = null;
  $selfClosing = null;
  $quirksMode = null;
  $publicID = null;
  $systemID = null;

  extract($token);

  if(is_null($mode))
   $mode=static::$mode;
  # Optimization. There's no need to check if it's HTML or foreign content if
  # a mode is given. It'll always be HTML content.
  else
   goto htmlContent;

  # This looks thoroughly insane, but it's a lot faster than doing it the
  # conventional way.
  $currentNodeNamespace = (static::$currentNode) ? static::$currentNode->namespaceURI : null;

  if(static::$currentNode===false || $type=='eof' || $currentNodeNamespace==null)
   goto htmlContent;
  else
  {
   if($currentNodeNamespace=='http://www.w3.org/1998/Math/MathML')
   {
    if(((static::$currentNodeName=='mi' || static::$currentNodeName=='mo' || static::$currentNodeName=='mn' || static::$currentNodeName=='ms' || static::$currentNodeName=='mtext') &&
        ($type=='character' || ($type=='start tag' && ($name!='mglyph' && $name!='malignmark')))))
     goto htmlContent;
    elseif(static::$currentNodeName=='annotation-xml')
    {
     $currentNodeEncoding=strtolower(static::$currentNode->getAttribute('encoding'));
     if(($type=='start tag' && $name=='svg') || ($currentNodeEncoding=='text/html' || $currentNodeEncoding=='application/xhtml+xml'))
      goto htmlContent;
    }
   }
   elseif($currentNodeNamespace=='http://www.w3.org/2000/svg' && (static::$currentNodeName=='foreignObject' || static::$currentNodeName=='desc' || static::$currentNodeName=='title') &&
          ($type=='start tag' || $type=='character'))
    goto htmlContent;
  }

  # Foreign content. Algorithm goes here if none of the checks above go to
  # htmlContent.
  foreignContent:
  {
   static::$htmlContent=false;
   if($type=='character')
   {
    static::$currentNode->appendChild(static::$DOM->createTextNode($data));
    if($data!="\t" && $data!="\n" && $data!="\x0c" && $data!="\x0d" && $data!=' ')
     static::$framesetOk=false;
   }
   elseif($type=='comment')
   static::$currentNode->appendChild(static::$DOM->createComment($data));
   elseif($type=='DOCTYPE')
   {
    static::parseError('unexpected doctype',static::$currentNodeName);
    return false;
   }
   elseif($type=='start tag')
   {
    if(($name=='b' || $name=='big' || $name=='blockquote' || $name=='body' || $name=='br' || $name=='center' || $name=='code' || $name=='dd' || $name=='div' || $name=='dl' ||
        $name=='dt' || $name=='em' || $name=='embed' || $name=='h1' || $name=='h2' || $name=='h3' || $name=='h4' || $name=='h5' || $name=='h6' || $name=='head' || $name=='hr' ||
        $name=='i' || $name=='img' || $name=='li' || $name=='listing' || $name=='menu' || $name=='meta' || $name=='nobr' || $name=='ol' || $name=='p' || $name=='pre' ||
        $name=='ruby' || $name=='s' || $name=='small' || $name=='span' || $name=='strong' || $name=='strike' || $name=='sub' || $name=='sup' || $name=='table' || $name=='tt' ||
        $name=='u' || $name=='ul' || $name=='var') || ($name=='font' && (isset($attributes['color']) || isset($attributes['face']) || isset($attributes['size']))))
    {
     static::parseError('unexpected start tag',$name,static::$currentNodeName);

     # Pop an element from the stack of open elements, and then keep popping
     # more elements from the stack of open elements until the current node
     # is a MathML text integration point, an HTML integration point, or an
     # element in the HTML namespace.
     static::stackPop();
     while(true)
     {
      $namespace=static::$currentNodeName->namespaceURI;

      # HTML namespace.
      if($namespace==null)
       break;
      else
      {
       $nodeName=static::$currentNodeName;
       if($namespace=='http://www.w3.org/1998/Math/MathML')
       {
        # MathML text integration point.
        if($nodeName=='mi' || $nodeName=='mo' || $nodeName=='mn' || $nodeName=='ms' || $nodeName=='mtext')
         break;
        # HTML integration point.
        elseif($nodeName=='annotation-xml')
        {
         $encoding=strtolower($nodeName->getAttribute('encoding'));
         if($encoding=='text/html' || $encoding=='application/xhtml+xml')
          break;
        }
       }
       # HTML integration point.
       elseif($namespace=='http://www.w3.org/2000/svg' && ($nodeName=='foreignObject' || $nodeName=='desc' || $nodeName=='title'))
        break;
      }
      static::stackPop();
     }

     goto reprocessToken;
    }
    else
    {
     # If the current node is an element in the MathML namespace, adjust
     # MathML attributes for the token. (This fixes the case of MathML
     # attributes that are not all lowercase.)
     if($currentNodeNamespace=='http://www.w3.org/1998/Math/MathML')
     {
      if(isset($attributes['definitionurl']))
      {
       $token['attributes']['definitionURL']=$attributes['definitionurl'];
       unset($token['attributes']['definitionurl']);
      }
     }
     elseif($currentNodeNamespace=='http://www.w3.org/2000/svg')
     {
      # If the current node is an element in the SVG namespace, and the
      # token's tag name is one of the ones in the first column of the
      # following table, change the tag name to the name given in the
      # corresponding cell in the second column. (This fixes the case of SVG
      # elements that are not all lowercase.)
      if(isset(static::$svgElements[$name]))
      {
       $token['name']=static::$svgElements[$name];
       $name=$token['name'];
      }
      # If the current node is an element in the SVG namespace, adjust SVG
      # attributes for the token. (This fixes the case of SVG attributes that
      # are not all lowercase.)
      else
      {
       if(isset($attributes))
       {
        foreach($attributes as $key=>$value)
        {
         if(isset(static::$svgAttributes[$key]))
         {
          $token['attributes'][static::$svgAttributes[$key]]=$value;
          unset($token['attributes'][$key]);
         }
        }
       }
      }
     }
     # Optimization. Foreign attributes are adjusted as they're entered into
     # the DOM in this implementation.

     # Insert a foreign element for the token, in the same namespace as the
     # current node.
     $token['namespace']=$currentNodeNamespace;

     # If the token has its self-closing flag set, pop the current node off
     # the stack of open elements and acknowledge the token's self-closing
     # flag.
     # Can't acknowledge the self-closing flag.
     static::insertElement($token,(isset($token['selfClosing']) && $token['selfClosing']) ? false : true);
    }
   }
   # Scripting isn't supported in this implementation, so script end tags get
   # processed like any other end tag.
   elseif($type=='end tag')
   {
    # Check for attributes. If they exist trigger a parse error.
    if(is_array($attributes))
     static::parseError('attributes in end tag',$name);
    # Check for self-closing flag. If it exists trigger a parse error.
    if($selfClosing!==null)
     static::parseError('self-closing end tag',$name);

    # If node is not an element with the same tag name as the token, then
    # this is a parse error.
    if(static::$currentNodeName!=$name)
     static::parseError('unexpected end tag',$name,static::$currentNodeName);

    # Initialize node to be the current node (the bottommost node of the
    # stack).
    $node=static::$currentNode;
    $nodeName=static::$currentNodeName;

    # Loop:
    while(true)
    {
     # If node's tag name, converted to ASCII lowercase, is the same as the
     # tag name of the token, pop elements from the stack of open elements
     # until node has been popped from the stack, and then jump to the last
     # step of this list of steps.
     # No need to convert to lowercase here?
     if(static::$currentNodeName==$name)
     {
      # This has to be an error in the spec. If the element is popped from the
      # stack of open elements here then when told to process the token
      # according to the rules of the current insertion mode in HTML content
      # then the algorithm will attempt again to pop the element from the stack
      # of open elements.

      # This implementation goes straight to reprocessing the token.

      /* while(true)
      {
       $poppedNode=static::$currentNode;
       static::stackPop();
       if($poppedNode===$node)
        goto reprocessToken;
      } */

      goto reprocessToken;
     }

     # Set node to the previous entry in the stack of open elements.
     $node=static::$stack[static::$stackSize-2];

     # If node is not an element in the HTML namespace, return to the step
     # labeled loop.
     if(!is_null($node->namespaceURI))
      continue;

     # Otherwise, process the token according to the rules given in the
     # section corresponding to the current insertion mode in HTML content.
     goto reprocessToken;
    }
   }

   # Return here so the code below doesn't get processed.
   return true;
  }

  # Used for reprocessing tokens. Using a goto is a whole lot faster and uses
  # a lot less memory than recursively calling this method over and over again.
  # This won't ever be executed unless told to go to this point in the program.
  reprocessToken:
  $mode=static::$mode;

  htmlContent:
  {
   static::$htmlContent=true;

   # HTML content.
   switch($mode)
   {
    case 'initial':
    {
     if($type=='character' && ($data=="\t" || $data=="\n" || $data=="\x0c" || $data=="\x0d" || $data==' '))
      continue;
     # Too much work involved to allow for comments before the DOCTYPE using the DOM, so they're ignored.
     elseif($type=='comment')
      continue;
     elseif($type=='DOCTYPE')
     {
      if($name!='html' || isset($publicID) || (isset($systemID) && $systemID=='about:legacy-compat') ||
         !($name=='html' || $publicID=='-/W3C//DTD HTML 4.0//EN' || (!isset($systemID) || $systemID=='http://www.w3.org/TR/REC-html40/strict.dtd')) ||
         !($name=='html' || $publicID=='-//W3C//DTD HTML 4.01//EN' || (!isset($systemID) || $systemID=='http://www.w3.org/TR/html4/strict.dtd')) ||
         !($name=='html' || $publicID=='-//W3C//DTD XHTML 1.0 Strict//EN' || (!isset($systemID) || $systemID=='http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd')) ||
         !($name=='html' || $publicID=='-//W3C//DTD XHTML 1.1//EN' || (!isset($systemID) || $systemID=='http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd')))
       static::parseError('invalid doctype');

      # Append a DocumentType node to the Document node.
      # PHP's DOM can't just do that, so a document is created with the
      # specified DOCTYPE.
	  # Deviation: PHP's DOMImplementation::createDocumentType() method stupidly
	  # cannot accept an empty qualified name, so if it is missing it is replaced
	  # with 'html'.
      $implementation = new DOMImplementation();
      static::$DOM=$implementation->createDocument(null,null,$implementation->createDocumentType((isset($name)) ? $name : 'html', $publicID, $systemID));
      static::$currentNode=static::$DOM;

      # For case insensitive comparison.
      $publicID=strtolower($publicID);

      if($quirksMode===true || $name!='html' ||
         strpos($publicID,'+//silmaril//dtd html pro v0r11 19970101//')===0 ||
         strpos($publicID,'-//advasoft ltd//dtd html 3.0 aswedit + extensions//')===0 ||
         strpos($publicID,'-//as//dtd html 3.0 aswedit + extensions//')===0 ||
         strpos($publicID,'-//ietf//dtd html 2.0 level 1//')===0 ||
         strpos($publicID,'-//ietf//dtd html 2.0 level 2//')===0 ||
         strpos($publicID,'-//ietf//dtd html 2.0 strict level 1//')===0 ||
         strpos($publicID,'-//ietf//dtd html 2.0 strict level 2//')===0 ||
         strpos($publicID,'-//ietf//dtd html 2.0 strict//')===0 ||
         strpos($publicID,'-//ietf//dtd html 2.0//')===0 ||
         strpos($publicID,'-//ietf//dtd html 2.1e//')===0 ||
         strpos($publicID,'-//ietf//dtd html 3.0//')===0 ||
         strpos($publicID,'-//ietf//dtd html 3.2 final//')===0 ||
         strpos($publicID,'-//ietf//dtd html 3.2//')===0 ||
         strpos($publicID,'-//ietf//dtd html 3//')===0 ||
         strpos($publicID,'-//ietf//dtd html level 0//')===0 ||
         strpos($publicID,'-//ietf//dtd html level 1//')===0 ||
         strpos($publicID,'-//ietf//dtd html level 2//')===0 ||
         strpos($publicID,'-//ietf//dtd html level 3//')===0 ||
         strpos($publicID,'-//ietf//dtd html strict level 0//')===0 ||
         strpos($publicID,'-//ietf//dtd html strict level 1//')===0 ||
         strpos($publicID,'-//ietf//dtd html strict level 2//')===0 ||
         strpos($publicID,'-//ietf//dtd html strict level 3//')===0 ||
         strpos($publicID,'-//ietf//dtd html strict//')===0 ||
         strpos($publicID,'-//ietf//dtd html//')===0 ||
         strpos($publicID,'-//metrius//dtd metrius presentational//')===0 ||
         strpos($publicID,'-//microsoft//dtd internet explorer 2.0 html strict//')===0 ||
         strpos($publicID,'-//microsoft//dtd internet explorer 2.0 html//')===0 ||
         strpos($publicID,'-//microsoft//dtd internet explorer 2.0 tables//')===0 ||
         strpos($publicID,'-//microsoft//dtd internet explorer 3.0 html strict//')===0 ||
         strpos($publicID,'-//microsoft//dtd internet explorer 3.0 html//')===0 ||
         strpos($publicID,'-//microsoft//dtd internet explorer 3.0 tables//')===0 ||
         strpos($publicID,'-//netscape comm. corp.//dtd html//')===0 ||
         strpos($publicID,'-//netscape comm. corp.//dtd strict html//')===0 ||
         strpos($publicID,'-//o\'reilly and associates//dtd html 2.0//')===0 ||
         strpos($publicID,'-//o\'reilly and associates//dtd html extended 1.0//')===0 ||
         strpos($publicID,'-//o\'reilly and associates//dtd html extended relaxed 1.0//')===0 ||
         strpos($publicID,'-//softquad software//dtd hotmetal pro 6.0::19990601::extensions to html 4.0//')===0 ||
         strpos($publicID,'-//softquad//dtd hotmetal pro 4.0::19971010::extensions to html 4.0//')===0 ||
         strpos($publicID,'-//spyglass//dtd html 2.0 extended//')===0 ||
         strpos($publicID,'-//sq//dtd html 2.0 hotmetal + extensions//')===0 ||
         strpos($publicID,'-//sun microsystems corp.//dtd hotjava html//')===0 ||
         strpos($publicID,'-//sun microsystems corp.//dtd hotjava strict html//')===0 ||
         strpos($publicID,'-//w3c//dtd html 3 1995-03-24//')===0 ||
         strpos($publicID,'-//w3c//dtd html 3.2 draft//')===0 ||
         strpos($publicID,'-//w3c//dtd html 3.2 final//')===0 ||
         strpos($publicID,'-//w3c//dtd html 3.2//')===0 ||
         strpos($publicID,'-//w3c//dtd html 3.2s draft//')===0 ||
         strpos($publicID,'-//w3c//dtd html 4.0 frameset//')===0 ||
         strpos($publicID,'-//w3c//dtd html 4.0 transitional//')===0 ||
         strpos($publicID,'-//w3c//dtd html experimental 19960712//')===0 ||
         strpos($publicID,'-//w3c//dtd html experimental 970421//')===0 ||
         strpos($publicID,'-//w3c//dtd w3 html//')===0 ||
         strpos($publicID,'-//w3o//dtd w3 html 3.0//')===0 ||
         strpos($publicID,'-//webtechs//dtd mozilla html 2.0//')===0 ||
         strpos($publicID,'-//webtechs//dtd mozilla html//')===0 ||
         $publicID=='-//w3o//dtd w3 html strict 3.0//en//' ||
         $publicID=='-/w3c/dtd html 4.0 transitional/en' ||
         $publicID=='html' ||
         $systemID=='http://www.ibm.com/data/dtd/v11/ibmxhtml1-transitional.dtd' ||
         (!isset($systemID) && strpos($publicID,'-//w3c//dtd html 4.01 frameset//')===0) ||
         (!isset($systemID) && strpos($publicID,'-//w3c//dtd html 4.01 transitional//')===0))
       static::$quirksMode=true;
      elseif(strpos($publicID,'-//w3c//dtd xhtml 1.0 frameset//')===0 ||
             strpos($publicID,'-//w3c//dtd xhtml 1.0 transitional//')===0 ||
             (isset($systemID) && strpos($publicID,'-//w3c//dtd html 4.01 frameset//')===0) ||
             (isset($systemID) && strpos($publicID,'-//w3c//dtd html 4.01 transitional//')===0))
       static::$quirksMode='limited';

      static::$mode='before html';
     }
     else
     {
      initialAnythingElse:
      # CHECK THIS: Don't think there's an iframe srcdoc document to worry about here.
      static::parseError('doctype expected '.$type,$name);

      static::$quirksMode=true;
      # Create empty DOM Document.
      static::$DOM=DOMImplementation::createDocument();
      static::$currentNode=static::$DOM;

      # Reprocess the token in the 'before html' insertion mode.
      static::$mode='before html';
      goto reprocessToken;
     }
    }
    break;

    case 'before html':
    {
     if($type=='DOCTYPE')
     {
      static::parseError('unexpected doctype',static::$currentNodeName);
      return false;
     }
     elseif($type=='comment')
      static::$currentNode->appendChild(static::$DOM->createComment($data));
     elseif($type=='character')
     {
      if($data=="\t" || $data=="\n" || $data=="\x0c" || $data=="\x0d" || $data==' ')
      {
       # Deviation. This implementation preserves whitespace here.
       static::$currentNode->appendChild(static::$DOM->createTextNode($data));
      }
      else
      {
       beforeHtmlAnythingElse:
       # Insert an html element.
       static::insertElement(array('type'=>'start tag',
                                   'name'=>'html'));
       static::$mode='before head';
       goto reprocessToken;
      }
     }
     elseif($type=='start tag')
     {
      if($name=='html')
      {
       $ook=static::$DOM;
       # Insert an html node into the DOM document.
       static::insertElement($token);
       static::$mode='before head';
      }
      else
       goto beforeHtmlAnythingElse;
     }
     elseif($type=='end tag')
     {
      # Check for attributes. If they exist trigger a parse error.
      if(is_array($attributes))
       static::parseError('attributes in end tag',$name);
      # Check for self-closing flag. If it exists trigger a parse error.
      if($selfClosing!==null)
       static::parseError('self-closing end tag',$name);

      if($name=='head' || $name=='body' || $name=='html' || $name=='br')
       goto beforeHtmlAnythingElse;
      else
      {
       static::parseError('unexpected end tag',$name,'html');
       return false;
      }
     }
     else
      goto beforeHtmlAnythingElse;
    }
    break;

    case 'before head':
    {
     if($type=='character')
     {
      if($data=="\t" || $data=="\n" || $data=="\x0c" || $data=="\x0d" || $data==' ')
      {
       # Deviation. This implementation preserves whitespace here.
       static::$currentNode->appendChild(static::$DOM->createTextNode($data));
      }
      else
      {
       beforeHeadAnythingElse:
       # Act as if a start tag token with the tag name "head" and no attributes
       # had been seen, then reprocess the current token.
       static::emitToken(array('type'=>'start tag',
                               'name'=>'head'));
       goto reprocessToken;
      }
     }
     elseif($type=='comment')
      static::$currentNode->appendChild(static::$DOM->createComment($data));
     elseif($type=='DOCTYPE')
     {
      static::parseError('unexpected doctype',static::$currentNodeName);
      return false;
     }
     elseif($type=='start tag')
     {
      if($name=='html')
       static::emitToken($token,'in body');
      elseif($name=='head')
      {
       # Insert a head element.
       static::$head=static::insertElement($token);
       static::$mode='in head';
      }
      else
       goto beforeHeadAnythingElse;
     }
     elseif($type=='end tag')
     {
      # Check for attributes. If they exist trigger a parse error.
      if(is_array($attributes))
       static::parseError('attributes in end tag',$name);
      # Check for self-closing flag. If it exists trigger a parse error.
      if($selfClosing!==null)
       static::parseError('self-closing end tag',$name);

      if($name=='head' || $name=='body' || $name=='html' || $name=='br')
       goto beforeHeadAnythingElse;
      else
      {
       static::parseError('unexpected end tag',$name,'head');
       return false;
      }
     }
     else
      goto beforeHeadAnythingElse;
    }
    break;

    case 'in head':
    {
     if($type=='character')
     {
      if($data=="\t" || $data=="\n" || $data=="\x0c" || $data=="\x0d" || $data==' ')
       static::$currentNode->appendChild(static::$DOM->createTextNode($data));
      else
      {
       # Act as if an end tag token with the tag name "head" had been seen, and
       # reprocess the current token.
       inHeadAnythingElse:
       static::emitToken(array('type'=>'end tag',
                               'name'=>'head'));
       goto reprocessToken;
      }
     }
     elseif($type=='comment')
      static::$currentNode->appendChild(static::$DOM->createComment($data));
     elseif($type=='DOCTYPE')
     {
      static::parseError('unexpected doctype','head');
      return false;
     }
     elseif($type=='start tag')
     {
      if($name=='html')
       static::emitToken($token,'in body');
      elseif(in_array($name,static::$headElements))
      {
       # Insert the element and don't add it to the end of the stack.
       static::insertElement($token,false);
      }
      elseif($name=='meta')
      {
       # Deviation. Spec states to grab the character encoding here under
       # certain circumstances, convert everything to it, and start using
       # it. This implementation will only ever support UTF-8, so it will
       # make the metadata here reflect that.
       $attributes['charset']='UTF-8';
       unset($attributes['http-equiv']);

       # Insert the element and don't add it to the end of the stack.
       static::insertElement($token,false);
      }
      elseif(in_array($name,static::$rcdataHeadElements))
      {
       # Generic RCDATA element parsing algorithm.
       static::insertElement($token);
       static::$state='RCDATA';
       static::$oMode=static::$mode;
       static::$mode='text';
      }
      # Since there's no scripting flag scripting is disabled.
      # As it is disabled the "noscript" enabled scripting flag item isn't
      # present.
      elseif(in_array($name,static::$rawtextHeadElements))
      {
       # Generic raw text element parsing algorithm.
       genericRawTextElementParsingAlgorithm:
       static::insertElement($token);
       static::$state='RAWTEXT';
       static::$oMode=static::$mode;
       static::$mode='text';
      }
      # Since there's no scripting flag there will be no checking for it.
      elseif($name=='noscript')
      {
       static::insertElement($token);
       static::$mode='in head noscript';
      }
      elseif($name=='script')
      {
       static::insertElement($token);
       static::$state='script data';
       static::$oMode=static::$mode;
       static::$mode='text';
      }
      elseif($name=='head')
       static::parseError('unexpected start tag','head','head');
      else
       goto inHeadAnythingElse;
     }
     elseif($type=='end tag')
     {
      # Check for attributes. If they exist trigger a parse error.
      if(is_array($attributes))
       static::parseError('attributes in end tag',$name);
      # Check for self-closing flag. If it exists trigger a parse error.
      if($selfClosing!==null)
       static::parseError('self-closing end tag',$name);

      if($name=='head')
      {
       static::stackPop();
       static::$mode='after head';
      }
      elseif($name=='body' || $name=='html' || $name=='br')
       goto inHeadAnythingElse;
      else
      {
       static::parseError('unexpected end tag',$name,static::$currentNodeName);
       return false;
      }
     }
     else
      goto inHeadAnythingElse;
    }
    break;

    case 'in head noscript':
    {
     if($type=='DOCTYPE')
     {
      static::parseError('unexpected doctype','noscript');
      return false;
     }
     elseif($type=='character')
     {
      if($data=="\t" || $data=="\n" || $data=="\x0c" || $data=="\x0d" || $data==' ')
       static::$currentNode->appendChild(static::$DOM->createTextNode($data));
      else
      {
       static::parseError('unexpected character',$data,'noscript');

       # Act as if an end tag with the tag name "noscript" had been seen and
       # reprocess the current token.
       inHeadNoscriptAnythingElse:
       static::emitToken(array('type'=>'end tag',
                               'name'=>'noscript'));
       goto reprocessToken;
      }
     }
     elseif($type=='comment')
      static::$currentNode->appendChild(static::$DOM->createComment($data));
     elseif($type=='start tag')
     {
      if($name=='html')
       static::emitToken($token,'in body');
      elseif($name=='basefont' || $name=='bgsound' || $name=='link' || $name=='meta' || $name=='noframes' || $name=='style')
       static::emitToken($token,'in head');
      elseif($name=='head' || $name=='noscript')
       static::parseError('unexpected start tag',$name,'noscript');
      else
      {
       static::parseError('unexpected start tag',$name,'noscript');
       goto inHeadNoscriptAnythingElse;
      }
     }
     elseif($type=='end tag')
     {
      # Check for attributes. If they exist trigger a parse error.
      if(is_array($attributes))
       static::parseError('attributes in end tag',$name);
      # Check for self-closing flag. If it exists trigger a parse error.
      if($selfClosing!==null)
       static::parseError('self-closing end tag',$name);

      if($name=='noscript')
      {
       static::stackPop();
       static::$mode='in head';
      }
      elseif($name=='br')
      {
       static::parseError('unexpected end tag','br','noscript');
       goto inHeadNoscriptAnythingElse;
      }
      else
      {
       static::parseError('unexpected end tag',$name,static::$currentNodeName);
       return false;
      }
     }
     else
     {
      static::parseError('unexpected eof',static::$currentNodeName);
      goto inHeadNoscriptAnythingElse;
     }
    }
    break;

    case 'after head':
    {
     if($type=='character')
     {
      if($data=="\t" || $data=="\n" || $data=="\x0c" || $data=="\x0d" || $data==' ')
       static::$currentNode->appendChild(static::$DOM->createTextNode($data));
      else
      {
       # Act as if a start tag token with the tag name "body" and no attributes
       # had been seen.
       afterHeadAnythingElse:
       static::emitToken(array('type'=>'start tag',
                               'name'=>'body'));
       static::$framesetOk=true;
       goto reprocessToken;
      }
     }
     elseif($type=='comment')
      static::$currentNode->appendChild(static::$DOM->createComment($data));
     elseif($type=='DOCTYPE')
     {
      static::parseError('unexpected DOCTYPE',static::$currentNodeName);
      return false;
     }
     elseif($type=='start tag')
     {
      if($name=='html')
       static::emitToken($token,'in body');
      elseif($name=='body')
      {
       static::insertElement($token);
       static::$framesetOk=false;
       static::$mode='in body';
      }
      elseif($name=='frameset')
      {
       static::insertElement($token);
       static::$mode='in frameset';
      }
      elseif($name=='meta' || $name=='noframes' || $name=='script' || $name=='style' || $name=='title' || in_array($name,static::$headElements))
      {
       static::parseError('unexpected start tag',$name,static::$currentNodeName);
       static::stackPush(static::$head);
       $headPos=static::$stackSize-1;
       static::emitToken($token,'in head');

       # Remove the node pointed to by the head element pointer from the stack
       # of open elements.
       static::stackSlice($headPos);
      }
      elseif($name=='head')
       static::parseError('unexpected start tag','head',static::$currentNodeName);
      else
       goto afterHeadAnythingElse;
     }
     elseif($type=='end tag')
     {
      # Check for attributes. If they exist trigger a parse error.
      if(is_array($attributes))
       static::parseError('attributes in end tag',$name);
      # Check for self-closing flag. If it exists trigger a parse error.
      if($selfClosing!==null)
       static::parseError('self-closing end tag',$name);

      if($name=='body' || $name=='html' || $name=='br')
       goto afterHeadAnythingElse;
      else
      {
       static::parseError('unexpected end tag',$name,static::$currentNodeName);
       return false;
      }
     }
     else
      goto afterHeadAnythingElse;
    }
    break;

    case 'in body':
    {
     if($type=='character')
     {
      static::activeReconstruct();
      $char=static::$DOM->createTextNode($data);

      # Foster parenting stuff here for the purpose of processing tables. Described
      # first in §13.2.5.4.9 under "Anything Else". This implementation uses a flag
      # to determine if foster parenting is necessary.
      # The "in table text" insertion mode will sometimes send characters this way.
      if(static::$fosterParenting && (static::$currentNodeName=='table' || static::$currentNodeName=='tbody' ||
                                      static::$currentNodeName=='tfoot' || static::$currentNodeName=='thead' ||
                                      static::$currentNodeName=='tr'))
       static::fosterParent($char);
      else
       static::$currentNode->appendChild($char);

      if($data!="\t" && $data!="\n" && $data!="\x0c" && $data!="\x0d" && $data!=' ')
       $framesetOk=false;
     }
     elseif($type=='comment')
      static::$currentNode->appendChild(static::$DOM->createComment($data));
     elseif($type=='DOCTYPE')
     {
      static::parseError('unexpected doctype',static::$currentNodeName);
      return false;
     }
     elseif($type=='start tag')
     {
      if($name=='html')
      {
       static::parseError('unexpected start tag',$name,static::$currentNodeName);
       # For each attribute on the token check to see if the attribute is
       # already present on the top element of the stack of open elements.
       # If it is not, add the attribute and its corresponding value to
       # that element.
       $topElement=static::$stack[0];
       if(isset($attributes))
       {
        foreach($attributes as $key=>$value)
        {
         if($topElement->getAttribute($key)=='')
          $topElement->setAttribute($key,(is_null($value)) ? $key : $value);
        }
       }
      }
      elseif($name=='base' || $name=='basefont' || $name=='bgsound' || $name=='command' || $name=='link' ||
             $name=='meta' || $name=='noframes' || $name=='script' || $name=='style' || $name=='title')
       static::emitToken($token,'in head');
      elseif($name=='body')
      {
       static::parseError('unexpected start tag','body',static::$currentNodeName);

       $secondElement=static::$stack[1];
       if(static::$fragment===true)
       {
        # If the second element on the stack of open elements is not a body
        # element, or, if the stack of open elements has only one node on it,
        # then ignore the token. (fragment case)
        if($secondElement->nodeName!='body' || static::$stackSize==1)
         continue;
       }
       else
       {
        static::$framesetOk=false;

        # For each attribute on the token, check to see if the attribute is
        # already present on the body element (the second element) on the
        # stack of open elements, and if it is not, add the attribute and its
        # corresponding value to that element.
        if(isset($attributes))
        {
         foreach($attributes as $key=>$value)
         {
          if($secondElement->getAttribute($key)=='')
           $secondElement->setAttribute($key,(is_null($value)) ? $key : $value);
         }
        }
       }
      }
      elseif($name=='frameset')
      {
       static::parseError('unexpected start tag','frameset',static::$currentNodeName);

       if(static::$fragment===true)
       {
        # If the second element on the stack of open elements is not a body
        # element, or, if the stack of open elements has only one node on it,
        # then ignore the token. (fragment case)
        # If the frameset-ok flag is set to "not ok", ignore the token.
        $secondElement=static::$stack[1];
        if(!$framesetOk || $secondElement->nodeName!='body' || static::$stackSize==1)
         return false;
       }
       else
       {
        # Remove the second element on the stack of open elements from its
        # parent node, if it has one.
        $secondElement->parentNode->removeChild($secondElement);

        # Pop all the nodes from the bottom of the stack of open elements,
        # from the current node up to, but not including, the root html
        # element.
        $firstElement=static::$stack[0];
        static::$stack=array($firstElement);
        static::$currentNode=$firstElement;
        static::$currentNodeName=$firstElement->nodeName;

        # Insert an HTML element for the token.
        static::insertElement($token);
        static::$mode='in frameset';
       }
      }
      elseif($name=='address' || $name=='article' || $name=='aside' || $name=='blockquote' ||
             $name=='center' || $name=='details' || $name=='dialog' || $name=='dir' || $name=='div' ||
             $name=='dl' || $name=='fieldset' || $name=='figcaption' || $name=='figure' ||
             $name=='footer' || $name=='header' || $name=='hgroup' || $name=='main' || $name=='menu' || $name=='nav' ||
             $name=='ol' || $name=='p' || $name=='section' || $name=='summary' || $name=='ul')
      {
       # If the stack of open elements has a p element in button scope then act
       # as if an end tag with the tag name "p" had been seen.
       if(static::inScope('p','button'))
        static::emitToken(array('type'=>'end tag',
                                'name'=>'p'));

       static::insertElement($token);
      }
      elseif($name=='h1' || $name=='h2' || $name=='h3' || $name=='h4' || $name=='h5' || $name=='h6')
      {
       # If the stack of open elements has a p element in button scope then act
       # as if an end tag with the tag name "p" had been seen.
       if(static::inScope('p','button'))
        static::emitToken(array('type'=>'end tag',
                              'name'=>'p'));

       if(static::$currentNodeName=='h1' || static::$currentNodeName=='h2' || static::$currentNodeName=='h3' ||
          static::$currentNodeName=='h4' || static::$currentNodeName=='h5' || static::$currentNodeName=='h6')
       {
        static::parseError('unexpected start tag',$name,static::$currentNodeName);
        static::stackPop();
       }

       static::insertElement($token);
      }
      elseif($name=='pre' || $name=='listing')
      {
       # If the stack of open elements has a p element in button scope then act
       # as if an end tag with the tag name "p" had been seen.
       if(static::inScope('p','button'))
        static::emitToken(array('type'=>'end tag',
                              'name'=>'p'));

       static::insertElement($token);

       # If the next token is a U+000A LINE FEED (LF) character token, then
       # ignore that token and move on to the next one. (Newlines at the
       # start of pre blocks are ignored as an authoring convenience.)
       if(static::peek()=="\n")
        static::consume();

       static::$framesetOk=false;
      }
      elseif($name=='form')
      {
       if(static::$form!=null)
       {
        static::parseError('unexpected start tag','form','form');
        return false;
       }

       # If the stack of open elements has a p element in button scope then act
       # as if an end tag with the tag name "p" had been seen.
       if(static::inScope('p','button'))
        static::emitToken(array('type'=>'end tag',
                              'name'=>'p'));

       static::$form=static::insertElement($token);
      }
      elseif($name=='li')
      {
       static::$framesetOk=false;

       $node=static::$currentNode;
       $nodeName=static::$currentNodeName;
       $key=static::$stackSize-1;

       while(true)
       {
        # If node is an li element, then act as if an end tag with the tag name
        # "li" had been seen, then jump to the last step.
        if($nodeName=='li')
        {
         static::emitToken(array('type'=>'end tag',
                               'name'=>'li'));
         break;
        }
        # If node is in the special category, but is not an address, div, or p
        # element, then jump to the last step.
        if(($nodeName!='address' || $nodeName!='div' || $nodeName!='p') && static::isSpecial($node))
         break;
        # Otherwise, set node to the previous entry in the stack of open
        # elements and return to the step labeled loop.
        else
        {
         $key--;
         $node=static::$stack[$key];
         $nodeName=$node->nodeName;
        }
       }

       # This is the last step.
       # If the stack of open elements has a p element in button scope then act
       # as if an end tag with the tag name "p" had been seen.
       if(static::inScope('p','button'))
        static::emitToken(array('type'=>'end tag',
                              'name'=>'p'));

       static::insertElement($token);
      }
      elseif($name=='dd' || $name=='dt')
      {
       static::$framesetOk=false;

       $node=static::$currentNode;
       $nodeName=static::$currentNodeName;
       $key=static::$stackSize-1;

       while(true)
       {
        # If node is a dd or dt element, then act as if an end tag with the
        # same tag name as node had been seen, then jump to the last step.
        if($nodeName=='dd' || $nodeName=='dt')
        {
         static::emitToken(array('type'=>'end tag',
                               'name'=>$nodeName));

         break;
        }
        # If node is in the special category, but is not an address, div, or p
        # element, then jump to the last step.
        if(($nodeName!='address' || $nodeName!='div' || $nodeName!='p') && static::isSpecial($node))
         break;
        # Otherwise, set node to the previous entry in the stack of open
        # elements and return to the step labeled loop.
        else
        {
         $key--;
         $node=static::$stack[$key];
         $nodeName=$node->nodeName;
        }
       }

       # This is the last step.
       # If the stack of open elements has a p element in button scope then act
       # as if an end tag with the tag name "p" had been seen.
       if(static::inScope('p','button'))
        static::emitToken(array('type'=>'end tag',
                              'name'=>'p'));

       static::insertElement($token);
      }
      elseif($name=='plaintext')
      {
       # If the stack of open elements has a p element in button scope then act
       # as if an end tag with the tag name "p" had been seen.
       if(static::inScope('p','button'))
        static::emitToken(array('type'=>'end tag',
                              'name'=>'p'));

       static::insertElement($token);
       static::$state='PLAINTEXT';
      }
      elseif($name=='button')
      {
       # If the stack of open elements has a button element in scope, then this
       # is a parse error; act as if an end tag with the tag name "button" had
       # been seen, then reprocess the token.
       if(static::inScope('button'))
       {
        static::parseError('unexpected start tag','button','button');

        static::emitToken(array('type'=>'end tag',
                                'name'=>'button'));

        goto reprocessToken;
       }
       else
       {
        static::activeReconstruct();
        static::insertElement($token);
        static::$framesetOk=false;
       }
      }
      elseif($name=='a')
      {
       # If the list of active formatting elements contains an element whose
       # tag name is "a" between the end of the list and the last marker on the
       # list (or the start of the list if there is no marker on the list),
       # then this is a parse error; act as if an end tag with the tag name "a"
       # had been seen, then remove that element from the list of active
       # formatting elements and the stack of open elements if the end tag
       # didn't already remove it (it might not have if the element is not in
       # table scope).
       for($loop=static::$activeSize;$loop<0;$loop--)
       {
        $current=static::$active[$loop];
        if(is_string($current))
         break;
        elseif($current->nodeName=='a')
        {
         static::parseError('unexpected start tag','a','a');
         static::emitToken(array('type'=>'end tag',
                                 'name'=>'a'));

         static::activeSplice($loop);

         $stackPos=array_search($current,static::$stack,true);
         if($stackPos!==false)
          static::stackSplice($stackPos,1);
        }
       }

       static::activeReconstruct();
       static::activePush(static::insertElement($token),$token);
      }
      elseif($name=='b' || $name=='big' || $name=='code' || $name=='em' || $name=='font' || $name=='i' ||
             $name=='s' || $name=='small' || $name=='strike' || $name=='strong' || $name=='tt' ||
             $name=='u')
      {
       static::activeReconstruct();

       static::activePush(static::insertElement($token),$token);
      }
      elseif($name=='nobr')
      {
       static::activeReconstruct();

       if(static::inScope('nobr'))
       {
        static::parseError('unexpected start tag','nobr','nobr');
        static::emitToken(array('type'=>'end tag',
                                'name'=>'nobr'));
        static::activeReconstruct();
       }

       static::activePush(static::insertElement($token),$token);
      }
      elseif($name=='applet' || $name=='marquee' || $name=='object')
      {
       static::activeReconstruct();

       static::insertElement($token);
       static::activePush($name);
       static::$framesetOk=false;
      }
      elseif($name=='table')
      {
       if(!static::$quirksMode && static::inScope('p','button'))
        static::emitToken(array('type'=>'end tag',
                              'name'=>'p'));
       static::insertElement($token);
       static::$framesetOk=false;
       static::$mode='in table';
      }
      elseif($name=='area' || $name=='br' || $name=='embed' || $name=='img' || $name=='keygen' || $name=='wbr')
      {
       static::activeReconstruct();
       static::insertElement($token);
       # Immediately pop the current node off the stack of open elements.
       static::stackPop();
       # Can't acknowledge the self-closing flag.
       static::$framesetOk=false;
      }
      elseif($name=='input')
      {
       static::activeReconstruct();
       static::insertElement($token);
       # Immediately pop the current node off the stack of open elements.
       static::stackPop();
       # Can't acknowledge the self-closing flag.

       # If the token does not have an attribute with the name "type", or if it
       # does, but that attribute's value is not an ASCII case-insensitive
       # match for the string "hidden", then: set the frameset-ok flag to
       # "not ok".
       if(strtolower($attributes['type'])!='hidden')
        static::$framesetOk=false;
      }
      elseif($name=='param' || $name=='source' || $name=='track')
      {
       static::insertElement($token);
       # Immediately pop the current node off the stack of open elements.
       static::stackPop();
       # Can't acknowledge the self-closing flag.
      }
      elseif($name=='hr')
      {
       if(static::inScope('p','button'))
        static::emitToken(array('type'=>'end tag',
                              'name'=>'p'));

       static::insertElement($token,false);

       # Can't acknowledge the self-closing flag.

       static::$framesetOk=false;
      }
      elseif($name=='image')
      {
       static::parseError('invalid start tag','image','img');
       $token['name']='img';
       static::emitToken($token);
      }
      elseif($name=='isindex')
      {
       static::parseError('invalid start tag','isindex','form');

       if(!is_null(static::$form))
        return false;

       # Can't acknowledge the self-closing flag.

       # Act as if a start tag token with the tag name "form" had been seen.
       # If the token has an attribute called "action", set the action
       # attribute on the resulting form element to the value of the
       # "action" attribute of the token.
       $temp=array('type'=>'start tag',
                   'name'=>'form');
       if(isset($attributes['action']))
        $temp['action']=$attributes['action'];
       static::emitToken($temp);

       # Act as if a start tag token with the tag name "hr" had been seen.
       static::emitToken(array('type'=>'start tag',
                             'name'=>'hr'));

       # Act as if a start tag token with the tag name "label" had been seen.
       static::emitToken(array('type'=>'start tag',
                             'name'=>'label'));

       # Act as if a stream of character tokens had been seen.
       # If the token has an attribute with the name "prompt", then the first
       # stream of characters must be the same string as given in that
       # attribute, and the second stream of characters must be empty.
       # Otherwise, the two streams of character tokens together should,
       # together with the input element, express the equivalent of "This is a
       # searchable index. Enter search keywords: (input field)" in the user's
       # preferred language.
       if(isset($attributes['prompt']))
       {
        static::emitToken(array('type'=>'character',
                              'data'=>$attributes['prompt']));
        $temp='';
       }
       else
       {
        $temp='This is a searchable index. Enter search keywords:';
        static::emitToken(array('type'=>'character',
                                'data'=>$temp));
       }

       # Act as if a start tag token with the tag name "input" had been seen,
       # with all the attributes from the "isindex" token except "name",
       # "action", and "prompt". Set the name attribute of the resulting input
       # element to the value "isindex".
       unset($attributes['action'],$attributes['prompt']);
       $attributes['name']='isindex';
       static::emitToken(array('type'=>'start tag',
                             'name'=>'input',
                             'attributes'=>$attributes));

       # Act as if a stream of character tokens had been seen.
       static::emitToken(array('type'=>'character',
                               'data'=>$temp));

       # Act as if an end tag token with the tag name "label" had been seen.
       static::emitToken(array('type'=>'end tag',
                               'name'=>'label'));

       # Act as if a start tag token with the tag name "hr" had been seen.
       static::emitToken(array('type'=>'start tag',
                               'name'=>'hr'));

       # Act as if an end tag token with the tag name "form" had been seen.
       static::emitToken(array('type'=>'end tag',
                               'name'=>'form'));
      }
      elseif($name=='textarea')
      {
       static::insertElement($token);

       # If the next token is a U+000A LINE FEED (LF) character token, then
       # ignore that token and move on to the next one. (Newlines at the
       # start of textarea elements are ignored as an authoring
       # convenience.)
       if(static::peek()=="\n")
        static::consume();

       static::$state='RCDATA';
       static::$oMode=static::$mode;
       static::$framesetOk=false;
       static::$mode='text';
      }
      elseif($name=='xmp')
      {
       if(static::inScope('p','button'))
        static::emitToken(array('type'=>'end tag',
                              'name'=>'p'));

       static::activeReconstruct();
       static::$framesetOk=false;

       # Follow the generic raw text element parsing algorithm.
       goto genericRawTextElementParsingAlgorithm;
      }
      elseif($name=='iframe')
      {
       static::$framesetOk=false;

       # Follow the generic raw text element parsing algorithm.
       goto genericRawTextElementParsingAlgorithm;
      }
      # Just noembed because there's no scripting in this implementation.
      # Follow the generic raw text element parsing algorithm.
      elseif($name=='noembed')
       goto genericRawTextElementParsingAlgorithm;
      elseif($name=='select')
      {
       static::activeReconstruct();
       static::insertElement($token);
       static::$framesetOk=false;

       $mode=static::$mode;
       static::$mode=($mode=='in table' || $mode=='in caption' || $mode=='in table body' || $mode=='in row' || $mode=='in cell') ? 'in select in table' : 'in select';
      }
      elseif($name=='optgroup' || $name=='option')
      {
       if(static::$currentNodeName=='option')
        static::emitToken(array('type'=>'end tag',
                              'name'=>'option'));

       static::activeReconstruct();
       static::insertElement($token);
      }
      elseif($name=='rp' || $name=='rt')
      {
       if(static::inScope('ruby'))
       {
        static::generateImpliedEndTags();
        if(static::$currentNodeName!='ruby')
         static::parseError('unexpected start tag',$name,'ruby');
       }

       static::insertElement($token);
      }
      elseif($name=='math')
      {
       static::activeReconstruct();

       # Adjust MathML attributes for the token. (This fixes the case of MathML
       # attributes that are not all lowercase.)
       if(isset($attributes['definitionurl']))
       {
        $token['attributes']['definitionURL']=$attributes['definitionurl'];
        unset($token['attributes']['definitionurl']);
       }

       $token['namespace']='http://www.w3.org/1998/Math/MathML';
       static::insertElement($token,($token['selfClosing']) ? false : true);

       # Can't acknowledge the self-closing flag.
      }
      elseif($name=='svg')
      {
       static::activeReconstruct();

       # Adjust SVG attributes for the token. (This fixes the case of SVG
       # attributes that are not all lowercase.)
       if(isset($attributes))
       {
        foreach($attributes as $key=>$value)
        {
         if(isset(static::$svgAttributes[$key]))
         {
          $token['attributes'][static::$svgAttributes[$key]]=$value;
          unset($token['attributes'][$key]);
         }
        }
       }

       $token['namespace']='http://www.w3.org/2000/svg';
       static::insertElement($token,(isset($token['selfClosing'])) ? false : true);

       # Can't acknowledge the self-closing flag.
      }
      elseif($name=='caption' || $name=='col' || $name=='colgroup' || $name=='frame' || $name=='head' ||
             $name=='tbody' || $name=='td' || $name=='tfoot' || $name=='th' || $name=='thead' || $name=='tr')
      {
       static::parseError('unexpected start tag',$name,static::$currentNodeName);
       return false;
      }
      else
      {
       static::activeReconstruct();
       static::insertElement($token);
      }
     }
     elseif($type=='end tag')
     {
      # Check for attributes. If they exist trigger a parse error.
      if(is_array($attributes))
       static::parseError('attributes in end tag',$name);
      # Check for self-closing flag. If it exists trigger a parse error.
      if($selfClosing!==null)
       static::parseError('self-closing end tag',$name);

      if($name=='body')
      {
       if(!static::inScope('body'))
       {
        static::parseError('unexpected end tag','body',static::$currentNodeName);
        return false;
       }

       # The first two elements in the stack should always at this point be
       # the html and body elements respectively, so there's no point in
       # having the loop check them. So, the loop ends at 1.
       for($loop=static::$stackSize-1;$loop>1;$loop--)
       {
        $node=static::$stack[$loop];

        $nodeName=static::$stack[$loop]->nodeName;
        if($nodeName!='dd' && $nodeName!='dt' && $nodeName!='li' && $nodeName!='optgroup' && $nodeName!='option' &&
           $nodeName!='p' && $nodeName!='rp' && $nodeName!='rt' && $nodeName!='tbody' && $nodeName!='td' &&
           $nodeName!='tfoot' && $nodeName!='th' && $nodeName!='thead' && $nodeName!='tr' && $nodeName!='body' &&
           $nodeName!='html')
         static::parseError('unexpected end tag','body',$nodeName);
       }

       static::$mode='after body';
      }
      elseif($name=='html')
      {
       # Act as if an end tag with the name "body" had been seen.
       # If that token wasn't ignored, reprocess the current token.
       if(static::emitToken(array('type'=>'end tag',
                                'name'=>'body'))!==false)
        goto reprocessToken;
      }
      elseif($name=='address' || $name=='article' || $name=='aside' || $name=='blockquote' || $name=='button' ||
             $name=='center' || $name=='details' || $name=='dialog' || $name=='dir' || $name=='div' || $name=='dl' ||
             $name=='fieldset' || $name=='figcaption' || $name=='figure' || $name=='footer' || $name=='header' ||
             $name=='hgroup' || $name=='listing' || $name=='main' || $name=='menu' || $name=='nav' || $name=='ol' ||
             $name=='pre' || $name=='section' || $name=='summary' || $name=='ul')
      {
       # If the stack of open elements does not have an element in scope with
       # the same tag name as that of the token, then this is a parse error;
       # ignore the token.
       if(!static::inScope($name))
       {
        static::parseError('unexpected end tag',$name,static::$currentNodeName);
        return false;
       }

       static::generateImpliedEndTags();

       # If the current node is not an element with the same tag name as that
       # of the token, then this is a parse error.
       if(static::$currentNodeName!=$name)
        static::parseError('unexpected end tag',$name,static::$currentNodeName);

       # Pop elements from the stack of open elements until an element with
       # the same tag name as the token has been popped from the stack.
       while(true)
       {
        $nodeName=static::$currentNodeName;
        static::stackPop();
        if($nodeName==$name)
         break;
       }
      }
      elseif($name=='form')
      {
       $node=static::$form;
       static::$form=null;

       if($node==null || static::inScope($node)===false)
       {
        static::parseError('unexpected end tag','form',static::$currentNodeName);
        return false;
       }

       static::generateImpliedEndTags();
       if(static::$currentNode!=$node)
        static::parseError('unexpected end tag','form',static::$currentNodeName);

       # Remove node from the stack of open elements.
       for($loop=static::$stackSize-1;$loop>=$stackPos;$loop--)
       {
        if(static::$stack[$loop]->isSameNode($node))
        {
         static::stackSplice($loop);
         break;
        }
       }
      }
      elseif($name=='p')
      {
       # If the stack of open elements does not have an element in button scope
       # with the same tag name as that of the token, then this is a parse
       # error; act as if a start tag with the tag name "p" had been seen, then
       # reprocess the current token.
       if(!static::inScope('p','button'))
       {
        static::parseError('unexpected end tag','p',static::$currentNodeName);
        static::emitToken(array('type'=>'start tag',
                                'name'=>'p'));
        goto reprocessToken;
       }

       # Generate implied end tags, except for elements with the same tag name
       # as the token.
       static::generateImpliedEndTags('p');

       # If the current node is not an element with the same tag name as that
       # of the token, then this is a parse error.
       if(static::$currentNodeName!='p')
        static::parseError('unexpected end tag','p',static::$currentNodeName);

       # Pop elements from the stack of open elements until an element with
       # the same tag name as the token has been popped from the stack.
       while(true)
       {
        $nodeName=static::$currentNodeName;
        static::stackPop();
        if($nodeName=='p')
         break;
       }
      }
      elseif($name=='li')
      {
       # If the stack of open elements does not have an element in list item
       # scope with the same tag name as that of the token, then this is a
       # parse error; ignore the token.
       if(!static::inScope('li','list item'))
       {
        static::parseError('unexpected end tag','li',static::$currentNodeName);
        return false;
       }

       # Generate implied end tags, except for elements with the same tag name
       # as the token.
       static::generateImpliedEndTags('li');

       # If the current node is not an element with the same tag name as that
       # of the token, then this is a parse error.
       if(static::$currentNodeName!='li')
        static::parseError('unexpected end tag','li',static::$currentNodeName);

       # Pop elements from the stack of open elements until an element with
       # the same tag name as the token has been popped from the stack.
       while(true)
       {
        $nodeName=static::$currentNodeName;
        static::stackPop();
        if($nodeName=='li')
         break;
       }
      }
      elseif($name=='dd' || $name=='dt')
      {
       # If the stack of open elements does not have an element in scope with
       # the same tag name as that of the token, then this is a parse error;
       # ignore the token.
       if(!static::inScope($name))
       {
        static::parseError('unexpected end tag',$name,static::$currentNodeName);
        return false;
       }

       # Generate implied end tags, except for elements with the same tag name
       # as the token.
       static::generateImpliedEndTags($name);

       # If the current node is not an element with the same tag name as that
       # of the token, then this is a parse error.
       if(static::$currentNodeName!=$name)
        static::parseError('unexpected end tag',$name,static::$currentNodeName);

       # Pop elements from the stack of open elements until an element with
       # the same tag name as the token has been popped from the stack.
       while(true)
       {
        $nodeName=static::$currentNodeName;
        static::stackPop();
        if($nodeName==$name)
         break;
       }
      }
      elseif($name=='h1' || $name=='h2' || $name=='h3' || $name=='h4' || $name=='h5' || $name=='h6')
      {
       if(!static::inScope('h1') && !static::inScope('h2') && !static::inScope('h3') && !static::inScope('h4') && !static::inScope('h5') && !static::inScope('h6'))
       {
        static::parseError('unexpected end tag',$name,static::$currentNodeName);
        return false;
       }

       static::generateImpliedEndTags();
       if(static::$currentNodeName!=$name)
        static::parseError('unexpected end tag',$name,static::$currentNodeName);

       # Pop elements from the stack of open elements until an element whose
       # tag name is one of "h1", "h2", "h3", "h4", "h5", or "h6" has been
       # popped from the stack.
       while(true)
       {
        $nodeName=static::$currentNodeName;
        static::stackPop();
        if($nodeName=='h1' || $nodeName=='h2' || $nodeName=='h3' || $nodeName=='h4' || $nodeName=='h5' || $nodeName=='h6')
         break;
       }
      }
      elseif($name=='a' || $name=='b' || $name=='big' || $name=='code' || $name=='em' || $name=='font' ||
             $name=='i' || $name=='nobr' || $name=='s' || $name=='small' || $name=='strike' ||
             $name=='strong' || $name=='tt' || $name=='u')
      {
       # Let outer loop counter be zero.
       # If outer loop counter is greater than or equal to eight, then abort
       # these steps.
       for($loop=0;$loop<8;$loop++)
       {
        # Let the formatting element be the last element in the list of active
        # formatting elements that:
        # * is between the end of the list and the last scope marker in the
        #   list, if any, or the start of the list otherwise, and
        # * has the same tag name as the token.
        for($activePos=static::$activeSize-1;$activePos>=0;$activePos--)
        {
         $formattingElement=static::$active[$activePos];
         if(is_string($formattingElement))
         {
          $formattingElement=(is_object(static::$active[0]) && static::$active[0]->nodeName==$name) ? static::$active[0] : null;
          break;
         }
         elseif($formattingElement->nodeName==$name)
          break;
         else
          $formattingElement=null;
        }

        # If there is no such node, then abort these steps and instead act as
        # described in the "any other end tag" entry below.
        if(is_null($formattingElement))
         goto inBodyAnyOtherEndTag;

        # Otherwise, if there is such a node, but that node is not in the stack
        # of open elements, then this is a parse error; remove the element from
        # the list, and abort these steps.
        $nodeName=$formattingElement->nodeName;
        $stackPos=array_search($formattingElement,static::$stack,true);
        if($stackPos===false)
        {
         static::parseError('unexpected end tag',$nodeName,static::$currentNodeName);
         static::activeSplice($activePos);
         break;
        }
        # Otherwise, if there is such a node, and that node is also in the
        # stack of open elements, but the element is not in scope, then this is
        # a parse error; ignore the token, and abort these steps.
        elseif(!static::inScope($formattingElement))
        {
         static::parseError('unexpected end tag',$nodeName,static::$currentNodeName);
         return false;
        }
        # Otherwise, there is a formatting element and that element is in the
        # stack and is in scope. If the element is not the current node, this
        # is a parse error. In any case, proceed with the algorithm as written
        # in the following steps.
        elseif($formattingElement!==static::$currentNode) {
         static::parseError('unexpected end tag',$nodeName,static::$currentNodeName);
        }

        # Let the furthest block be the topmost node in the stack of open
        # elements that is lower in the stack than the formatting element, and
        # is an element in the special category. There might not be one.
        $furthestBlock=null;
        for($fbPos=$stackPos+1;$fbPos<static::$stackSize;$fbPos++)
        {
         $current=static::$stack[$fbPos];
         if(static::isSpecial($current))
         {
          $furthestBlock=$current;
          break;
         }
        }

        # If there is no furthest block, then the UA must skip the subsequent
        # steps and instead just pop all the nodes from the bottom of the
        # stack of open elements, from the current node up to and including
        # the formatting element, and remove the formatting element from the
        # list of active formatting elements.
        if(is_null($furthestBlock))
        {
         while($last = end(static::$stack)) {
             static::stackPop();
             if ($last->isSameNode($formattingElement)) {
                 break;
             }
         }

         static::activeSplice($activePos);
         break;
        }

        # Let the common ancestor be the element immediately above the
        # formatting element in the stack of open elements.
        $commonAncestor=static::$stack[$stackPos-1];

        # Let a bookmark note the position of the formatting element in the
        # list of active formatting elements relative to the elements on
        # either side of it in the list.
        $bookmark=$activePos;
        $stackNodePos=$fbPos;

        # Let node and last node be the furthest block.
        $node=$furthestBlock;
        $lastNode=$furthestBlock;
        for($loop2=0;$loop2<3;$loop2++)
        {
         # Let node be the element immediately above node in the stack of open
         # elements, or if node is no longer in the stack of open elements
         # (e.g. because it got removed by the next step), the element that was
         # immediately above node in the stack of open elements before node was
         # removed.
         $stackNodePos--;
         $node=static::$stack[$stackNodePos];

         # If node is not in the list of active formatting elements, then remove
         # node from the stack of open elements and then go back to the step
         # labeled inner loop.
         $activeNodePos=array_search($node,static::$active,true);
         if($activeNodePos===false)
         {
          static::stackSplice($stackNodePos);
          continue;
         }

         # Otherwise, if node is the formatting element, then go to the next
         # step in the overall algorithm.
         if($node->isSameNode($formattingElement))
          break;

         # Create an element for the token for which the element node was
         # created, replace the entry for node in the list of active formatting
         # elements with an entry for the new element, replace the entry for
         # node in the stack of open elements with an entry for the new
         # element, and let node be the new element.
         $newNode=$node->cloneNode();
         static::activeSplice($activeNodePos,1,$newNode);
         static::stackSplice($stackNodePos,1,$newNode);
         $node=$newNode;

         # If last node is the furthest block, then move the aforementioned
         # bookmark to be immediately after the new node in the list of active
         # formatting elements.
         if($lastNode->isSameNode($furthestBlock))
          $bookmark=$activeNodePos+1;

         # Insert last node into node, first removing it from its previous
         # parent node if any.
         # PHP's DOM takes care of the removal.
         $node->appendChild($lastNode);

         # Let last node be node.
         $lastNode=$node;
        }

        # If the common ancestor node is a table, tbody, tfoot, thead, or tr
        # element, then, foster parent whatever last node ended up being in the
        # previous step, first removing it from its previous parent node if any.
        # # PHP's DOM takes care of the removal.
        $commonAncestorName=$commonAncestor->nodeName;

        if($commonAncestorName=='table' || $commonAncestorName=='tbody' || $commonAncestorName=='tfoot' ||
           $commonAncestorName=='thead' || $commonAncestorName=='tr')
         static::fosterParent($lastNode);
        # Otherwise, append whatever last node ended up being in the previous
        # step to the common ancestor node, first removing it from its
        # previous parent node if any.
        else
         $commonAncestor->appendChild($lastNode);

        # Create an element for the token for which the formatting element was
        # created.
        $newElement=$formattingElement->cloneNode();

        # Take all of the child nodes of the furthest block and append them to
        # the element created in the last step.
        while($furthestBlock->firstChild)
         {$newElement->appendChild($furthestBlock->firstChild);}

        # Append that new element to the furthest block.
        $furthestBlock->appendChild($newElement);

        # Remove the formatting element from the list of active formatting
        # elements, and insert the new element into the list of active
        # formatting elements at the position of the aforementioned bookmark.
        static::activeSplice(array_search($formattingElement,static::$active,true),1);
        static::activeSplice($bookmark,1,$newElement);

        # Remove the formatting element from the stack of open elements, and
        # insert the new element into the stack of open elements immediately
        # below the position of the furthest block in that stack.
        static::stackSplice(array_search($formattingElement,static::$stack,true),1);
        static::stackSplice(array_search($furthestBlock,static::$stack,true)+1,1,$newElement);
       }
      }
      elseif($name=='applet' || $name=='marquee' || $name=='object')
      {
       if(!static::inScope($name))
       {
        static::parseError('unexpected end tag',$name,static::$currentNodeName);
        return false;
       }

       static::generateImpliedEndTags();

       # If the current node is not an element with the same tag name as that
       # of the token, then this is a parse error.
       if(static::$currentNodeName!=$name)
        static::parseError('unexpected end tag',$name,static::$currentNodeName);

       # Pop elements from the stack of open elements until an element with
       # the same tag name as the token has been popped from the stack.
       while(true)
       {
        $nodeName=static::$currentNodeName;
        static::stackPop();
        if($nodeName==$name)
         break;
       }

       # Clear the list of active formatting elements up to the last marker.
       while(true)
       {
        $entry=end(static::$active);
        static::activePop();
        if(is_string($entry))
         break;
       }
      }
      elseif($name=='br')
      {
       static::parseError('invalid end tag','br','br');
       static::emitToken(array('type'=>'start tag',
                               'name'=>'br',
                               'selfClosing'=>true));
       return false;
      }
      else
      {
       inBodyAnyOtherEndTag:

       $node=static::$currentNode;
       $nodeName=static::$currentNodeName;
       $nodePos=static::$stackSize-1;
       while(true)
       {
        if($nodeName==$name)
        {
         static::generateImpliedEndTags($name);

         if($nodeName!=static::$currentNodeName)
          static::parseError('unexpected end tag',$name,static::$currentNodeName);

         while(true)
         {
          $currentNode=static::$currentNode;
          static::stackPop();
          if($currentNode==$node)
           break 2;
         }
        }
        elseif(in_array($nodeName,static::$specialElements['html']))
        {
         static::parseError('unexpected end tag',$name,static::$currentNodeName);
         break;
        }
        $nodePos--;
        $node=static::$stack[$nodePos];
        $nodeName=$node->nodeName;
       }
      }

     }

    }
    break;

    case 'text':
    {
     if($type=='character')
      static::$currentNode->appendChild(static::$DOM->createTextNode($data));
     elseif($type=='eof')
     {
      static::parseError('unexpected eof',static::$currentNodeName);
      # No scripting in this implementation.
      static::stackPop();
      static::$mode=static::$oMode;
      goto reprocessToken;
     }
     elseif($type=='end tag')
     {
      # Check for attributes. If they exist trigger a parse error.
      if(is_array($attributes))
       static::parseError('attributes in end tag',$name);
      # Check for self-closing flag. If it exists trigger a parse error.
      if($selfClosing!==null)
       static::parseError('self-closing end tag',$name);

      # There's no scripting, so script end tags behave just like any other
      # end tags.
      static::stackPop();
      static::$mode=static::$oMode;
     }
    }
    break;

    case 'in table':
    {
     if($type=='character')
     {
      static::$pendingTableCharacterTokens=array();
      static::$oMode=static::$mode;
      static::$mode='in table text';

      goto reprocessToken;
     }
     elseif($type=='comment')
      static::$currentNode->appendChild(static::$DOM->createComment($data));
     elseif($type=='DOCTYPE')
     {
      static::parseError('unexpected doctype',static::$currentNodeName);
      return false;
     }
     elseif($type=='start tag')
     {
      if($name=='caption')
      {
       # Clear the stack back to a table context.
       while(static::$currentNodeName!='table' && static::$currentNode!='html')
        {static::stackPop();}

       static::activePush('caption');

       static::insertElement($token);
       static::$mode='in caption';
      }
      elseif($name=='colgroup')
      {
       # Clear the stack back to a table context.
       while(static::$currentNodeName!='table' && static::$currentNode!='html')
        {static::stackPop();}

       static::insertElement($token);
       static::$mode='in column group';
      }
      elseif($name=='col')
      {
       static::emitToken(array('type'=>'start tag',
                               'name'=>'colgroup'));
       goto reprocessToken;
      }
      elseif($name=='tbody' || $name=='tfoot' || $name=='thead')
      {
       # Clear the stack back to a table context.
       while(static::$currentNodeName!='table' && static::$currentNode!='html')
        {static::stackPop();}

       static::insertElement($token);
       static::$mode='in table body';
      }
      elseif($name=='td' || $name=='th' || $name=='tr')
      {
       static::emitToken(array('type'=>'start tag',
                               'name'=>'tbody'));
       goto reprocessToken;
      }
      elseif($name=='table')
      {
       static::parseError('unexpected start tag','table','table');

       # Act as if an end tag token with the tag name "table" had been seen,
       # then, if that token wasn't ignored, reprocess the current token.
       if(static::emitToken(array('type'=>'end tag',
                                  'name'=>'table'))!==false)
        goto reprocessToken;
      }
      elseif($name=='style' || $name=='script')
       static::emitToken($token,'in head');
      elseif($name=='input')
      {
       if(!isset($attributes['type']) || strtolower($attributes['type'])!='hidden')
        goto inTableAnythingElse;

       static::parseError('invalid start tag','input','input');
       static::insertElement($token);
       static::stackPop();
      }
      elseif($name=='form')
      {
       static::parseError('unexpected start tag','form','table');
       if(!is_null(static::$form))
        return false;
       else
       {
        static::$form=static::insertElement($token);
        static::stackPop();
       }
      }
      else
      {
       inTableAnythingElse:
       static::parseError('unexpected '.$type,static::$currentNodeName,$name);

       if(static::$currentNodeName=='table' || static::$currentNodeName=='tbody' || static::$currentNodeName=='tfoot' ||
          static::$currentNodeName=='thead' || static::$currentNodeName=='tr')
       {
        static::$fosterParenting=true;
        static::emitToken($token,'in body');
        static::$fosterParenting=false;
       }
       else
        static::emitToken($token,'in body');
      }
     }
     elseif($type=='end tag')
     {
      # Check for attributes. If they exist trigger a parse error.
      if(is_array($attributes))
       static::parseError('attributes in end tag',$name);
      # Check for self-closing flag. If it exists trigger a parse error.
      if($selfClosing!==null)
       static::parseError('self-closing end tag',$name);

      if($name=='table')
      {
       if(static::$fragment===true)
       {
        if(!static::inScope($name,'table'))
        {
         static::parseError('unexpected end tag','table',static::$currentNodeName);
         return false;
        }
       }

       # Pop elements from this stack until a table element has been popped
       # from the stack.
       while(true)
       {
        $nodeName=static::$currentNodeName;
        static::stackPop();
        if($nodeName=='table')
         break;
       }

       # Reset the insertion mode appropriately.
       static::resetInsertionMode();
      }
      elseif($name=='body' || $name=='caption' || $name=='col' || $name=='colgroup' || $name=='html' || $name=='tbody' ||
             $name=='td' || $name=='tfoot' || $name=='th' || $name=='thead' || $name=='tr')
      {
       static::parseError('unexpected end tag',$name,static::$currentNodeName);
       return false;
      }
      else
       goto inTableAnythingElse;
     }
     elseif($type=='eof')
     {
      if(static::$currentNodeName!='html')
       static::parseError('unexpected eof',static::$currentNodeName);
     }
    }
    break;

    case 'in table text':
    {
     if($type=='character')
      static::$pendingTableCharacterTokens[]=$token;
     else
     {
      # If any of the tokens in the pending table character tokens list are
      # character tokens that are not space characters, then reprocess those
      # character tokens using the rules given in the "anything else" entry
      # in the "in table" insertion mode.
      # Otherwise, insert the characters given by the pending table character
      # tokens list into the current node.

      $tokens='';
      foreach(static::$pendingTableCharacterTokens as $t)
       {$tokens.=$t['data'];}

      if(!preg_match('/[^'.static::WHITESPACE.']/',$tokens))
       static::$currentNode->appendChild(static::$DOM->createTextNode($tokens));
      else
      {
       static::parseError('unexpected character',$tokens,static::$currentNodeName);

       if(static::$currentNodeName=='table' || static::$currentNodeName=='tbody' || static::$currentNodeName=='tfoot' ||
          static::$currentNodeName=='thead' || static::$currentNodeName=='tr')
       {
        static::$fosterParenting=true;
        static::emitToken(array('type'=>'character',
                                'data'=>$tokens),'in body');
        static::$fosterParenting=false;
       }
       else
        static::emitToken($t,'in body');
      }
      # Switch the insertion mode to the original insertion mode and reprocess
      # the token.
      static::$mode=static::$oMode;

      goto reprocessToken;
     }
    }
    break;

    case 'in caption':
    {
     if($type=='start tag')
     {
      if($name=='caption' || $name=='col' || $name=='colgroup' || $name=='tbody' || $name=='td' ||
         $name=='tfoot' || $name=='th' || $name=='thead' || $name=='tr')
       goto inCaptionEndTagTable;
      else
       goto inCaptionAnythingElse;
     }
     elseif($type=='end tag')
     {
      # Check for attributes. If they exist trigger a parse error.
      if(is_array($attributes))
       static::parseError('attributes in end tag',$name);
      # Check for self-closing flag. If it exists trigger a parse error.
      if($selfClosing!==null)
       static::parseError('self-closing end tag',$name);

      if($name=='caption')
      {
       if(!static::inScope('caption','table'))
       {
        static::parseError('unexpected end tag','caption',static::$currentNodeName);
        return false;
       }

       static::generateImpliedEndTags();

       if(static::$currentNodeName!='caption')
        static::parseError('unexpected end tag','caption',static::$currentNodeName);

       # Pop elements from this stack until a caption element has been popped
       # from the stack.
       while(true)
       {
        $nodeName=static::$currentNodeName;
        static::stackPop();
        if($nodeName=='caption')
         break;
       }

       # Clear the list of active formatting elements up to the last marker.
       while(true)
       {
        $entry=end(static::$active);
        static::activePop();
        if(is_string($entry))
         break;
       }

       static::$mode='in table';
      }
      elseif($name=='table')
      {
       inCaptionEndTagTable:

       # Act as if an end tag with the tag name "caption" had been seen, then,
       # if that token wasn't ignored, reprocess the current token.
       if(static::emitToken(array('type'=>'end tag',
                                  'name'=>'caption'))!==false)
        goto reprocessToken;
      }
      elseif($name=='body' || $name=='col' || $name=='colgroup' || $name=='html' ||
             $name=='tbody' || $name=='td' || $name=='tfoot' || $name=='th' ||
             $name=='thead' || $name=='tr')
      {
       static::parseError('unexpected end tag',$name,static::$currentNodeName);
       return false;
      }
      else
       goto inCaptionAnythingElse;
     }
     else
     {
      inCaptionAnythingElse:
      static::emitToken($token,'in body');
     }
    }
    break;

    case 'in column group':
    {
     if($type=='character')
     {
      if($data=="\t" || $data=="\n" || $data=="\x0c" || $data=="\x0d" || $data==' ')
       static::$currentNode->appendChild(static::$DOM->createTextNode($data));
      else
      {
       inColumnGroupAnythingElse:
       if(static::emitToken(array('type'=>'end tag',
                                  'name'=>'colgroup'))!==false)
        goto reprocessToken;
      }
     }
     elseif($type=='comment')
      static::$currentNode->appendChild(static::$DOM->createComment($data));
     elseif($type=='DOCTYPE')
     {
      static::parseError('unexpected doctype',static::$currentNodeName);
      return false;
     }
     elseif($type=='start tag')
     {
      if($name=='html')
       static::emitToken($token,'in body');
      elseif($name=='col')
      {
       static::insertElement($token);
       static::stackPop();
       # Can't acknowledge the token's self-closing flag.
      }
      else
       goto inColumnGroupAnythingElse;
     }
     elseif($type=='end tag')
     {
      # Check for attributes. If they exist trigger a parse error.
      if(is_array($attributes))
       static::parseError('attributes in end tag',$name);
      # Check for self-closing flag. If it exists trigger a parse error.
      if($selfClosing!==null)
       static::parseError('self-closing end tag',$name);

      if($name=='colgroup')
      {
       # If the current node is the root html element, then this is a parse
       # error; ignore the token. (fragment case)
       if(static::$currentNode->isSameNode(static::$stack[0]))
       {
        static::parseError('unexpected end tag','colgroup',static::$currentNodeName);
        return false;
       }

       static::stackPop();
       static::$mode='in table';
      }
      elseif($name=='col')
       static::parseError('unexpected end tag','col',static::$currentNodeName);
      else
       goto inColumnGroupAnythingElse;
     }
     elseif($type=='eof')
     {
      if(static::$currentNode->isSameNode(static::$stack[0]))
      {
       // STOP PARSING.
       return false;
      }
     }
    }
    break;

    case 'in table body':
    {
     if($type=='start tag')
     {
      if($name=='tr')
      {
       # Clear the stack back to a table body context.
       while(static::$currentNodeName!='tbody' && static::$currentNodeName!='tfoot' && static::$currentNodeName!='thead' && static::$currentNodeName!='html')
        {static::stackPop();}

       static::insertElement($token);
       static::$mode='in row';
      }
      elseif($name=='th' || $name=='td')
      {
       static::parseError('unexpected start tag',$name,static::$currentNodeName);
       static::emitToken(array('type'=>'start tag',
                             'name'=>'tr'));
       goto reprocessToken;
      }
      elseif($name=='caption' || $name=='col' || $name=='colgroup' || $name=='tbody' || $name=='tfoot' || $name=='thead')
       goto inTableBodyEndTagTable;
      else
       goto inTableBodyAnythingElse;
     }
     elseif($type=='end tag')
     {
      # Check for attributes. If they exist trigger a parse error.
      if(is_array($attributes))
       static::parseError('attributes in end tag',$name);
      # Check for self-closing flag. If it exists trigger a parse error.
      if($selfClosing!==null)
       static::parseError('self-closing end tag',$name);

      if($name=='tbody' || $name=='tfoot' || $name=='thead')
      {
       if(!static::inScope($name,'table'))
       {
        static::parseError('unexpected start tag',$name,static::$currentNodeName);
        return false;
       }
       # Clear the stack back to a table body context.
       while(static::$currentNodeName!='tbody' && static::$currentNodeName!='tfoot' && static::$currentNodeName!='thead' && static::$currentNodeName!='html')
        {static::stackPop();}

       static::stackPop();
       static::$mode='in table';
      }
      elseif($name=='table')
      {
       inTableBodyEndTagTable:
       if(!static::inScope('tbody','table') && !static::inScope('thead','table') && !static::inScope('tfoot','table'))
       {
        static::parseError('unexpected start tag',$name,static::$currentNodeName);
        return false;
       }

       # Clear the stack back to a table body context.
       while(static::$currentNodeName!='tbody' && static::$currentNodeName!='tfoot' && static::$currentNodeName!='thead' && static::$currentNodeName!='html')
        {static::stackPop();}

       # Act as if an end tag with the same tag name as the current node
       # ("tbody", "tfoot", or "thead") had been seen, then reprocess the
       # current token.
       static::emitToken(array('type'=>'end tag',
                             'name'=>static::$currentNodeName));

       goto reprocessToken;
      }
      elseif($name=='body' || $name=='caption' || $name=='col' || $name=='colgroup' || $name=='html' || $name=='td' || $name=='th' || $name=='tr')
      {
       static::parseError('unexpected end tag',$name,static::$currentNodeName);
       return false;
      }
      else
       goto inTableBodyAnythingElse;
     }
     else
     {
      inTableBodyAnythingElse:
      static::emitToken($token,'in table');
     }
    }
    break;

    case 'in row':
    {
     if($type=='start tag')
     {
      if($name=='th' || $name=='td')
      {
       # Clear the stack back to a table row context.
       while(static::$currentNodeName!='tr' && static::$currentNodeName!='html')
        {static::stackPop();}

       # Insert an HTML element for the token, then switch the insertion mode
       # to "in cell".
       static::insertElement($token);
       static::$mode='in cell';
       static::activePush($name);
      }
      elseif($name=='caption' || $name=='col' || $name=='colgroup' || $name=='tbody' || $name=='tfoot' ||
             $name=='thead' || $name=='tr')
       goto inRowEndTagTable;
      else
       goto inRowAnythingElse;
     }
     elseif($type=='end tag')
     {
      # Check for attributes. If they exist trigger a parse error.
      if(is_array($attributes))
       static::parseError('attributes in end tag',$name);
      # Check for self-closing flag. If it exists trigger a parse error.
      if($selfClosing!==null)
       static::parseError('self-closing end tag',$name);

      if($name=='tr')
      {
       if(!static::inScope('tr','table'))
       {
        static::parseError('unexpected end tag','tr',static::$currentNodeName);
        return false;
       }

       # Clear the stack back to a table row context.
       while(static::$currentNodeName!='tr' && static::$currentNodeName!='html')
        {static::stackPop();}

       # Pop the current node (which will be a tr element) from the stack of
       # open elements.
       static::stackPop();

       static::$mode='in table body';
      }
      elseif($name=='table')
      {
       inRowEndTagTable:

       if(static::emitToken(array('type'=>'end tag',
                                'name'=>'tr'))!==false)
        goto reprocessToken;
      }
      elseif($name=='tbody' || $name=='tfoot' || $name=='thead')
      {
       if(!static::inScope($name,'table'))
       {
        static::parseError('unexpected end tag',$name,static::$currentNodeName);
        return false;
       }

       static::emitToken(array('type'=>'end tag',
                             'name'=>'tr'));
       goto reprocessToken;
      }
      elseif($name=='body' || $name=='caption' || $name=='col' || $name=='colgroup' || $name=='html' ||
             $name=='td' || $name=='th')
      {
       static::parseError('unexpected end tag',$name,static::$currentNodeName);
       return false;
      }
      else
       goto inRowAnythingElse;
     }
     else
     {
      inRowAnythingElse:
      static::emitToken($token,'in table');
     }
    }
    break;

    case 'in cell':
    {
     if($type=='start tag')
     {
      if($name=='caption' || $name=='col' || $name=='colgroup' || $name=='tbody' || $name=='td' || $name=='tfoot' ||
         $name=='th' || $name=='thead' || $name=='tr')
      {
       if(!static::inScope('td','table') && !static::inScope('th','table'))
       {
        static::parseError('unexpected end tag',$name,static::$currentNodeName);
        return false;
       }

       # Otherwise, close the cell and reprocess the current token.
       if(static::inScope('td','table'))
        static::emitToken(array('type'=>'end tag',
                              'name'=>'td'));
       else
        static::emitToken(array('type'=>'end tag',
                              'name'=>'th'));

       goto reprocessToken;
      }
      else
       goto inCellAnythingElse;
     }
     elseif($type=='end tag')
     {
      # Check for attributes. If they exist trigger a parse error.
      if(is_array($attributes))
       static::parseError('attributes in end tag',$name);
      # Check for self-closing flag. If it exists trigger a parse error.
      if($selfClosing!==null)
       static::parseError('self-closing end tag',$name);

      if($name=='td' || $name=='th')
      {
       if(!static::inScope($name,'table'))
       {
        static::parseError('unexpected end tag',$name,static::$currentNodeName);
        return false;
       }

       static::generateImpliedEndTags();
       if(static::$currentNodeName!=$name)
        static::parseError('unexpected end tag',$name,static::$currentNodeName);

       # Pop elements from the stack of open elements stack until an element
       # with the same tag name as the token has been popped from the stack.
       while(true)
       {
        $nodeName=static::$currentNodeName;
        static::stackPop();
        if($nodeName==$name)
         break;
       }

       # Clear the list of active formatting elements up to the last marker.
       while(true)
       {
        $entry=end(static::$active);
        static::activePop();
        if(is_string($entry))
         break;
       }

       static::$mode='in row';
      }
      elseif($name=='body' || $name=='tbody' || $name=='tfoot' || $name=='thead' || $name=='tr')
      {
       static::parseError('unexpected end tag',$name,static::$currentNodeName);
       return false;
      }
      elseif($name=='table' || $name=='tbody' || $name=='tfoot' || $name=='thead' || $name=='tr')
      {
       if(!static::inScope($name,'table'))
       {
        static::parseError('unexpected end tag',$name,static::$currentNodeName);
        return false;
       }

       # Otherwise, close the cell and reprocess the current token.
       if(static::inScope('td','table'))
        static::emitToken(array('type'=>'end tag',
                              'name'=>'td'));
       else
        static::emitToken(array('type'=>'end tag',
                              'name'=>'th'));

       goto reprocessToken;
      }
      else
       goto inCellAnythingElse;
     }
     else
     {
      inCellAnythingElse:
      static::emitToken($token,'in body');
     }
    }
    break;

    case 'in select':
    {
     if($type=='character')
      static::$currentNode->appendChild(static::$DOM->createTextNode($data));
     elseif($type=='comment')
      static::$DOM->appendChild(static::$DOM->createComment($data));
     elseif($type=='DOCTYPE')
     {
      static::parseError('unexpected doctype',static::$currentNodeName);
      return false;
     }
     elseif($type=='start tag')
     {
      if($name=='html')
       static::emitToken($token,'in body');
      elseif($name=='option')
      {
       if(static::$currentNodeName=='option')
        static::emitToken(array('type'=>'end tag',
                                'name'=>'option'));

       static::insertElement($token);
      }
      elseif($name=='optgroup')
      {
       if(static::$currentNodeName=='option')
        static::emitToken(array('type'=>'end tag',
                                'name'=>'option'));

       if(static::$currentNodeName=='optgroup')
        static::emitToken(array('type'=>'end tag',
                              'name'=>'optgroup'));

       static::insertElement($token);
      }
      elseif($name=='select')
      {
       static::parseError('unexpected start tag',$name,static::$currentNodeName);
       static::emitToken(array('type'=>'end tag',
                             'name'=>'select'));
      }
      elseif($name=='input' || $name=='keygen' || $name=='textarea')
      {
       static::parseError('unexpected start tag',$name,static::$currentNodeName);

       if(!static::inScope('select','select'))
        return false;

       static::emitToken(array('type'=>'end tag',
                               'name'=>'select'));
       goto reprocessToken;
      }
      elseif($name=='script')
       static::emitToken($token,'in head');
      else
      {
       inSelectAnythingElse:
       static::parseError('unexpected '.$type,static::$currentNodeName,$name);
       return false;
      }
     }
     elseif($type=='end tag')
     {
      if($name=='optigroup')
      {
       # First, if the current node is an option element, and the node
       # immediately before it in the stack of open elements is an optgroup
       # element, then act as if an end tag with the tag name "option" had been
       # seen.
       if(static::$currentNodeName=='option' && static::$stack[static::$stackSize-2]=='optgroup')
        static::emitToken(array('type'=>'end tag',
                                'name'=>'option'));

       # If the current node is an optgroup element, then pop that node from
       # the stack of open elements. Otherwise, this is a parse error; ignore
       # the token.
       if(static::$currentNodeName=='optgroup')
        static::stackPop();
       else
       {
        static::parseError('unexpected end tag','optgroup',static::$currentNodeName);
        return false;
       }
      }
      elseif($name=='option')
      {
       if(static::$currentNodeName=='option')
        static::stackPop();
       else
       {
        static::parseError('unexpected end tag','option',static::$currentNodeName);
        return false;
       }
      }
      elseif($name=='select')
      {
       if(!static::inScope('select','select'))
       {
        static::parseError('unexpected end tag','option',static::$currentNodeName);
        return false;
       }

       # Pop elements from the stack of open elements stack until a select
       # element has been popped from the stack.
       while(true)
       {
        $nodeName=static::$currentNodeName;
        static::stackPop();
        if($nodeName=='select')
         break;
       }

       static::resetInsertionMode();
      }
      else
       goto inSelectAnythingElse;
     }
     elseif($type=='eof')
     {
      if(static::$fragment===true)
      {
       if(static::$currentNodeName!='html')
        static::parseError('unexpected eof',static::$currentNodeName);
      }

      // STOP PARSING.
     }
    }
    break;

    case 'in select in table':
    {
     if($type=='start tag')
     {
      if($name=='caption' || $name=='table' || $name=='tbody' || $name=='tfoot' || $name=='thead' ||
         $name=='tr' || $name=='td' || $name=='th')
      {
       static::parseError('unexpected start tag',$name,static::$currentNodeName);

       static::emitToken(array('type'=>'end tag',
                               'name'=>'select'));
       goto reprocessToken;
      }
      else
       goto inSelectInTableAnythingElse;
     }
     elseif($type=='end tag')
     {
      # Check for attributes. If they exist trigger a parse error.
      if(is_array($attributes))
       static::parseError('attributes in end tag',$name);
      # Check for self-closing flag. If it exists trigger a parse error.
      if($selfClosing!==null)
       static::parseError('self-closing end tag',$name);

      if($name=='caption' || $name=='table' || $name=='tbody' || $name=='tfoot' || $name=='thead' ||
         $name=='tr' || $name=='td' || $name=='th')
      {
       static::parseError('unexpected end tag',$name,static::$currentNodeName);

       if(static::inScope($name,'table'))
       {
        static::emitToken(array('type'=>'end tag',
                                'name'=>'select'));
        goto reprocessToken;
       }
       else
        return false;
      }
      else
       goto inSelectInTableAnythingElse;
     }
     else
     {
      inSelectInTableAnythingElse:
      static::emitToken($token,'in select');
     }
    }
    break;

    case 'after body':
    {
     if($type=='character')
     {
      if($data=="\t" || $data=="\n" || $data=="\x0c" || $data=="\x0d" || $data==' ')
       static::$stack[0]->appendChild(static::$DOM->createTextNode($data));
      else
      {
       static::parseError('unexpected character',$data,static::$currentNodeName);
       afterBodyAnythingElse:
       static::$mode='body';
       goto reprocessToken;
      }
     }
     elseif($type=='comment')
      static::$stack[0]->appendChild(static::$DOM->createComment($data));
     elseif($type=='DOCTYPE')
     {
      static::parseError('unexpected doctype',static::$currentNodeName);
      return false;
     }
     elseif($type=='start tag')
     {
      if($name=='html')
       static::emitToken($token,'in body');
      else
      {
       static::parseError('unexpected start tag',$name,static::$currentNodeName);
       goto afterBodyAnythingElse;
      }
     }
     elseif($type=='end tag')
     {
      # Check for attributes. If they exist trigger a parse error.
      if(is_array($attributes))
       static::parseError('attributes in end tag',$name);
      # Check for self-closing flag. If it exists trigger a parse error.
      if($selfClosing!==null)
       static::parseError('self-closing end tag',$name);

      if($name=='html')
      {
       if(static::$fragment===true)
       {
        static::parseError('unexpected end tag','html',static::$currentNodeName);
        return false;
       }
       static::$mode='after after body';
      }
      else
      {
       static::parseError('unexpected end tag',$name,static::$currentNodeName);
       goto afterBodyAnythingElse;
      }
     }
     elseif($type=='eof')
     {
      // STOP PARSING.
     }
    }
    break;

    case 'in frameset':
    {
     if($type=='character')
     {
      if($data=="\t" || $data=="\n" || $data=="\x0c" || $data=="\x0d" || $data==' ')
       static::$currentNode->appendChild(static::$DOM->createTextNode($data));
      else
      {
       static::parseError('unexpected character',$data,static::$currentNodeName);
       return false;
      }
     }
     elseif($type=='comment')
      static::$currentNode->appendChild(static::$DOM->createComment($data));
     elseif($type=='DOCTYPE')
     {
      static::parseError('unexpected doctype',static::$currentNodeName);
      return false;
     }
     elseif($type=='start tag')
     {
      if($name=='html')
       static::emitToken($token,'in body');
      elseif($name=='frameset')
       static::insertElement($token);
      elseif($name=='frame')
      {
       static::insertElement($token);
       static::stackPop();

       # Can't acknowledge the token's self-closing flag.
      }
      elseif($name=='noframes')
       static::insertElement($token,'in head');
      else
      {
       static::parseError('unexpected start tag',$name,static::$currentNodeName);
       return false;
      }
     }
     elseif($type=='end tag')
     {
      # Check for attributes. If they exist trigger a parse error.
      if(is_array($attributes))
       static::parseError('attributes in end tag',$name);
      # Check for self-closing flag. If it exists trigger a parse error.
      if($selfClosing!==null)
       static::parseError('self-closing end tag',$name);

      if($name=='frameset')
      {
       if(static::$fragment===true)
       {
        if(static::$currentNode->isSameNode(static::$stack[0]))
        {
         static::parseError('unexpected start tag','frameset',static::$currentNodeName);
         return false;
        }
       }

       static::stackPop();

       if(static::$fragment===false && static::$currentNode!='frameset')
        static::$mode='after frameset';
      }
      else
      {
       static::parseError('unexpected end tag',$name,static::$currentNodeName);
       return false;
      }
     }
     elseif($type=='eof')
     {
      if(static::$currentNode!==static::$stack[0])
       static::parseError('unexpected eof',static::$currentNodeName);

      // STOP PARSING.
     }
    }
    break;

    case 'after frameset':
    {
     if($type=='character')
     {
      if($data=="\t" || $data=="\n" || $data=="\x0c" || $data=="\x0d" || $data==' ')
       static::$currentNode->appendChild(static::$DOM->createTextNode($data));
      else
      {
       static::parseError('unexpected character',$data,static::$currentNodeName);
       return false;
      }
     }
     elseif($type=='comment')
      static::$currentNode->appendChild(static::$DOM->createComment($data));
     elseif($type=='DOCTYPE')
     {
      static::parseError('unexpected doctype',static::$currentNodeName);
      return false;
     }
     elseif($type=='start tag')
     {
      if($name=='html')
       static::emitToken($token,'in body');
      elseif($name=='noframes')
       static::emitToken($token,'in head');
      else
      {
       static::parseError('unexpected start tag',$name,static::$currentNodeName);
       return false;
      }
     }
     elseif($type=='end tag')
     {
      # Check for attributes. If they exist trigger a parse error.
      if(is_array($attributes))
       static::parseError('attributes in end tag',$name);
      # Check for self-closing flag. If it exists trigger a parse error.
      if($selfClosing!==null)
       static::parseError('self-closing end tag',$name);

      if($name=='html')
       static::$mode='after after frameset';
      else
      {
       static::parseError('unexpected end tag',$name,static::$currentNodeName);
       return false;
      }
     }
     elseif($type=='eof')
     {
      // STOP PARSING.
     }
    }
    break;

    case 'after after body':
    {
     if($type=='character')
     {
      if($data=="\t" || $data=="\n" || $data=="\x0c" || $data=="\x0d" || $data==' ')
       static::emitToken($token,'in body');
      else
      {
       static::parseError('unexpected character',$data,static::$currentNodeName);
       afterAfterBodyAnythingElse:
       static::$mode='in body';
       goto reprocessToken;
      }
     }
     elseif($type=='comment')
      static::$DOM->appendChild(static::$DOM->createComment($data));
     elseif($type=='DOCTYPE')
      static::emitToken($token,'in body');
     elseif($type=='start tag')
     {
      if($name=='html')
       static::emitToken($token,'in body');
      else
      {
       static::parseError('unexpected start tag',$name,static::$currentNodeName);
       goto afterAfterBodyAnythingElse;
      }
     }
     elseif($type=='end tag')
     {
      # Check for attributes. If they exist trigger a parse error.
      if(is_array($attributes))
       static::parseError('attributes in end tag',$name);
      # Check for self-closing flag. If it exists trigger a parse error.
      if($selfClosing!==null)
       static::parseError('self-closing end tag',$name);

      static::parseError('unexpected end tag',$name,static::$currentNodeName);
      goto afterAfterBodyAnythingElse;
     }
     elseif($type=='eof')
     {
      // STOP PARSING.
     }
    }
    break;

    case 'after after frameset':
    {
     if($type=='character')
     {
      if($data=="\t" || $data=="\n" || $data=="\x0c" || $data=="\x0d" || $data==' ')
       static::emitToken($token,'in body');
      else
      {
       static::parseError('unexpected character',$data,static::$currentNodeName);
       static::$mode='in body';
       static::emitToken($token);
      }
     }
     elseif($type=='comment')
      static::$DOM->appendChild(static::$DOM->createComment($data));
     elseif($type=='DOCTYPE')
      static::emitToken($token,'in body');
     elseif($type=='start tag')
     {
      if($name=='html')
       static::emitToken($token,'in body');
      elseif($name=='noframes')
       static::emitToken($token,'in head');
      else
      {
       static::parseError('unexpected start tag',$name,static::$currentNodeName);
       static::$mode='in body';
       static::emitToken($token);
      }
     }
     elseif($type=='end tag')
     {
      # Check for attributes. If they exist trigger a parse error.
      if(is_array($attributes))
       static::parseError('attributes in end tag',$name);
      # Check for self-closing flag. If it exists trigger a parse error.
      if($selfClosing!==null)
       static::parseError('self-closing end tag',$name);

      static::parseError('unexpected end tag',$name,static::$currentNodeName);
      static::$mode='in body';
      static::emitToken($token);
     }
     elseif($type=='eof')
     {
      // STOP PARSING.
     }
    }
    break;
   }
   return true;
  }
 }

 # Pops an element off the end of the stack of open elements. Returns the last
 # one popped.
 # It also sets static::$currentNode, static::$currentNodeName, &
 # static::$stackSize.
 protected static function stackPop()
 {
  $node=array_pop(static::$stack);

  if($node)
  {
   static::$currentNode=end(static::$stack);
   static::$currentNodeName=static::$currentNode->nodeName;
   static::$stackSize--;
  }
  else
  {
   static::$currentNode=null;
   static::$currentNodeName=null;
   static::$stackSize=0;
  }

  return $node;
 }

 # Pushes an element onto the end of the stack of open elements.
 # It also sets static::$currentNode, static::$currentNodeName, &
 # static::$stackSize.
 # @param $node Node to push onto the end of the stack.
 protected static function stackPush($node)
 {
  static::$stack[]=$node;
  static::$currentNode=$node;
  static::$currentNodeName=static::$currentNode->nodeName;
  static::$stackSize++;
 }

 # Removes the elements designated by $offset and $length from
 # the stack.
 # It also sets static::$currentNode, static::$currentNodeName, &
 # static::$stackSize.
 # @param $offset Offset to start from.
 # @param $length The number of elements to slice off.
 # @param $replacement The array to replace the sliced elements with.
 protected static function stackSplice($offset,$length=1,$replacement=null)
 {
  if(!is_null($replacement))
  {
   if(!is_array($replacement))
    $replacement=array($replacement);
  }

  array_splice(static::$stack,$offset,$length,$replacement);
  static::$currentNode=end(static::$stack);
  static::$currentNodeName=static::$currentNode->nodeName;
  static::$stackSize=sizeof(static::$stack);
 }

 # Pops an element off the end of the list of active formatting elements.
 # Returns the last one popped.
 # It also sets static::$activeSize.
 # @param $count Number of times to pop elements off the list. Defaults to 1.
 protected static function activePop($count=1)
 {
  $output=array_pop(static::$active);

  if($output)
   static::$activeSize--;
  else
   static::$activeSize=0;

  return $output;
 }

 # Pushes an element onto the end of the list of active formatting elements.
 # It also sets static::$activeSize.
 # @param $node Node to push onto the end of the list.
 # @param $token Token used for checking againstwithin the list. Defaults to
 # null.
 protected static function activePush($node,$token=null)
 {
  # If there are already three elements in the list of active formatting
  # elements after the last list marker, if any, or anywhere in the list if
  # there are no list markers, that have the same tag name, namespace, and
  # attributes as element, then remove the earliest such element from the list
  # of active formatting elements. For these purposes, the attributes must be
  # compared as they were when the elements were created by the parser; two
  # elements have the same attributes if all their parsed attributes can be
  # paired such that the two attributes in each pair have identical names,
  # namespaces, and values (the order of the attributes does not matter).

  # It's better to add the node to the list first so that if $node is a marker
  # it just returns after adding the node to the list. This implementation of
  # the Noah's Ark algorithm just compensates for the extra item in the stack
  # by beginning its reverse iteration through the list at the next to the last
  # item in the list.
  static::$active[]=$node;
  static::$activeSize++;

  if(is_string($node))
   return;

  $count=0;
  # Although it's thoroughly insane to rewrite much of this loop it's much
  # faster to check if there's attributes on the token first than in each
  # iteration of the loop.
  if(isset($token['attributes']))
  {
   for($loop=static::$activeSize-2;$loop>=0;$loop--)
   {
    $current=static::$active[$loop];

    if(is_string($current))
     break;

    if($current->nodeName!=$token['name'])
     continue;
    if($current->namespaceURI!=$token['namespace'])
     continue;
    if(!$current->hasAttributes())
     continue;

    $attributes=$token['attributes'];
    $attr=$current->attributes;
    $attrLen=$attr->length;
    for($loop2=0;$loop2<$attrLen;$loop2++)
    {
     $item=$attr->item($loop2);
     $name=$item->nodeName;
     if(!array_key_exists($name,$attributes))
      continue 2;
     if($attributes[$name]!=$item->value)
      continue 2;
    }

    if($count==2)
    {
     static::activeSplice($loop);
     continue;
    }
    $count++;
   }
  }
  else
  {
   for($loop=static::$activeSize-2;$loop>=0;$loop--)
   {
    $current=static::$active[$loop];

    if(is_string($current))
     break;

    if($current->nodeName!=$token['name'])
     continue;
    if($current->namespaceURI!=$token['namespace'])
     continue;
    if($current->hasAttributes())
     continue;

    if($count==2)
    {
     static::activeSplice($loop);
     continue;
    }
    $count++;
   }
  }
 }

 # Removes the elements designated by $offset and $length from the list of
 # active formatting elements.
 # It also sets static::$activeSize.
 # @param $offset Offset to start from.
 # @param $length The number of elements to slice off.
 # @param $replacement The array to replace the sliced elements with.
 protected static function activeSplice($offset,$length=1,$replacement=null)
 {
  if(!is_null($replacement))
  {
   if(!is_array($replacement))
    $replacement=array($replacement);
  }

  array_splice(static::$active,$offset,$length,$replacement);
  static::$activeSize=sizeof(static::$active);
 }

 # Method which reconstructs the active formatting elements.
 protected static function activeReconstruct()
 {
  # If there are no entries in the list of active formatting elements, then
  # there is nothing to reconstruct; stop this algorithm.
  if(static::$activeSize==0)
   return;

  # If the last (most recently added) entry in the list of active formatting
  # elements is a marker, or if it is an element that is in the stack of open
  # elements, then there is nothing to reconstruct; stop this algorithm.
  # Let entry be the last (most recently added) element in the list of active
  # formatting elements.
  $entry=end(static::$active);
  if(is_string($entry) || in_array($entry,static::$stack,true))
   return;
  $key=static::$activeSize-1;

  activeReconstructStep4:
  # If there are no entries before entry in the list of active formatting
  # elements, then jump to step 8.
  if($key==0)
   goto activeReconstructStep8;

  # Let entry be the entry one earlier than entry in the list of active
  # formatting elements.
  $key--;
  $entry=static::$active[$key];

  # If entry is neither a marker nor an element that is also in the stack of
  # open elements, go to step 4.
  if(!is_string($entry) && !in_array($entry,static::$stack,true)) {
      goto activeReconstructStep4;
  }

  activeReconstructStep7:
  # Let entry be the element one later than entry in the list of active
  # formatting elements.
  $key++;
  $entry=static::$active[$key];

  # Create an element for the token for which the element entry was created, to
  # obtain new element.
  activeReconstructStep8:
  $newElement=$entry->cloneNode();

  # Append new element to the current node and push it onto the stack of open
  # elements so that it is the new current node.
  # Foster parenting stuff here for the purpose of processing tables. Described
  # first in §13.2.5.4.9 under "Anything Else". This implementation uses a flag
  # to determine if foster parenting is necessary.
  if(static::$fosterParenting && (static::$currentNodeName=='table' || static::$currentNodeName=='tbody' ||
                                  static::$currentNodeName=='tfoot' || static::$currentNodeName=='thead' ||
                                  static::$currentNodeName=='tr'))
  {
   static::fosterParent($newElement);
   static::stackPush($newElement);
  }
  else
  {
   static::$currentNode->appendChild($newElement);
   static::stackPush($newElement);
  }

  # Replace the entry for entry in the list with an entry for new element.
  static::activeSplice($key,1,$newElement);

  # If the entry for new element in the list of active formatting elements is
  # not the last entry in the list, return to step 7.
  if($key!=static::$activeSize-1)
   goto activeReconstructStep7;
 }

 # Method to insert an element into the DOM tree. Returns the node that was inserted.
 # @param $token Token to be inserted as a node.
 # @param $stack Flag specifying whether to append the node to the stack. Initially true.
 protected static function insertElement($token,$stack=true)
 {
  if(!isset($token['namespace']))
  {
   $node=static::$DOM->createElement($token['name']);

   if(isset($token['attributes']))
   {
       $attributes = $token['attributes'];
    # PHP bug workaround.
     /* if(isset($attributes['id']))
    {
     $id=static::$DOM->createAttribute('xml:id');
     $id->appendChild(static::$DOM->createTextNode($attributes['id']));
     $node->appendChild($id);
     $node->setIdAttribute('xml:id',true);
     unset($attributes['id']);
    } */

    foreach($attributes as $key=>$value)
    {
     if(!is_array($value))
      $node->setAttribute($key,(is_null($value)) ? $key : $value);
     else
      $node->setAttributeNS($value['namespace'],$key,$value['value']);
    }
   }
  }
  else
  {

   # Spec states to do this after the element is created, but it's FAR quicker
   # to trigger the errors prior to adjusting foreign attributes and before
   # creating the element.
   if(isset($token['attributes']['xmlns']) && $token['attributes']['xmlns']!=$token['namespace'])
    static::parseError('invalid foreign attribute',$token['name'],'xmlns',$token['namespace']);
   if(isset($token['attributes']['xmlns:xlink']) && $token['attributes']['xmlns:xlink']!='http://www.w3.org/1999/xlink')
    static::parseError('invalid foreign attribute',$token['name'],'xmlns:xlink','http://www.w3.org/1999/xlink');

   $node=static::$DOM->createElementNS($token['namespace'],$token['name']);

   # Instead adjust foreign attributes as they are added into the element.
   if(isset($token['attributes']))
   {
    foreach($token['attributes'] as $key=>$value)
    {
     if(!isset(static::$foreignAttributes[$key]))
      $node->setAttribute($key,(is_null($value)) ? $key : $value);
     else
     {
      # Creating an actual attribute node and appending it is necessary due to
      # some fucked up way PHP's DOM handles namespaced attributes.
      $attr=static::$DOM->createAttributeNS(static::$foreignAttributes[$key],$key);
      $attr->value=$value;
      $node->appendChild($attr);

      //$node->setAttributeNS(static::$foreignAttributes[$key],$key,$value);
     }
    }
   }
  }

  # Foster parenting stuff here for the purpose of processing tables. Described
  # first in §13.2.5.4.9 under "Anything Else". This implementation uses a flag
  # to determine if foster parenting is necessary.
  if(static::$fosterParenting && (static::$currentNodeName=='table' || static::$currentNodeName=='tbody' ||
                                  static::$currentNodeName=='tfoot' || static::$currentNodeName=='thead' ||
                                  static::$currentNodeName=='tr'))
   static::fosterParent($node);
  else
  {
   static::$currentNode->appendChild($node);
   # There's no navigation of a browsing context necessary in this implementation.

   if($stack===true)
    static::stackPush($node);
  }

  return $node;
 }

 # Checks if a particular element or element type is in scope. Returns true or false.
 # @param $element The target element. Can be either a string or a DOMElement.
 # @param $scope Scope type to check for. Defaults to null.
 protected static function inScope($element,$scope=null)
 {
  $node=static::$currentNode;
  $key=static::$stackSize-1;

  while(true)
  {
   $name=$node->nodeName;
   $namespace=$node->namespaceURI;

   $check=(is_string($element)) ? $name : $node;
   if($check==$element)
    return true;
   elseif($namespace==null)
   {
    switch($scope)
    {
     case null:
     {
      if($name=='applet' || $name=='caption' || $name=='html' || $name=='table' ||
       $name=='td' || $name=='th' || $name=='marquee' || $name=='object' || $name=='#document-fragment')
       return false;
     }
     break;
     case 'list item':
     {
      if($name=='applet' || $name=='caption' || $name=='html' || $name=='table' ||
         $name=='td' || $name=='th' || $name=='marquee' || $name=='object' ||
         $name=='ol' || $name=='ul' || $name=='#document-fragment')
       return false;
     }
     break;
     case 'button':
     {
      if($name=='applet' || $name=='caption' || $name=='html' || $name=='table' ||
         $name=='td' || $name=='th' || $name=='marquee' || $name=='object' ||
         $name=='button' || $name=='#document-fragment')
       return false;
     }
     break;
     case 'table':
     {
      if($name=='html' || $name=='table' || $name=='#document-fragment')
       return false;
     }
     break;
     case 'select':
     {
      if($name=='optgroup' || $name=='option')
       return false;
     }
    }
   }
   elseif($scope!='table' && $scope!='select')
   {
    if($namespace=='http://www.w3.org/1998/Math/MathML' &&
       ($name=='mi' || $name=='mo' || $name=='mn' || $name=='ms' || $name='mtext' ||
        $name=='annotation-xml'))
     return false;
    elseif($namespace=='http://www.w3.org/2000/svg' &&
           ($name=='foreignObject' || $name=='desc' || $name=='title'))
     return false;
   }

   $key--;
   $node=static::$stack[$key];
  }
 }

 # Generates implied end tags.
 # @param $exclusion A given element name to exclude from the array of implied
 # elements. Defaults to null.
 protected static function generateImpliedEndTags($exclusion=null)
 {
  $elements=array_diff(static::$impliedElements,array($exclusion));

  while(in_array(static::$currentNodeName,$elements))
   {static::stackPop();}
 }

 # Foster parents a given node.
 # @param $node The node to foster parent.
 protected static function fosterParent($node)
 {
  # The foster parent element is the parent element of the last table element
  # in the stack of open elements, if there is a table element and it has such
  # a parent element.
  $fosterParent=null;

  for($loop=static::$stackSize-1;$loop>=0;$loop--)
  {
   $current=static::$stack[$loop];
   if($current->nodeName=='table')
   {
    $fosterParent=$current->parentNode;
    $fosterParent->insertBefore($node,$current);
    return;
   }
  }
  if(static::$fragment===true)
  {
   if(is_null($fosterParent))
    $fosterParent=static::$stack[0];
    # WHAT THE FUCK?!
    # DO THIS.
  }
 }

 # Checks to see if a particular node is an element that requires special
 # processing.
 # @param $node Node to check if it is special.
 protected static function isSpecial($node)
 {
  switch($node->namespaceURI)
  {
   case null: $specialElements=static::$specialElements['html'];
   break;
   case 'http://www.w3.org/1998/Math/MathML': $specialElements=static::$specialElements['mathml'];
   break;
   case 'http://www.w3.org/2000/svg': $specialElements=static::$specialElements['svg'];
   break;
   default: $specialElements=static::$specialElements['html'];
  }

  $nodeName=$node->nodeName;
  return in_array($nodeName,$specialElements);
 }

 # Resets the insertion mode.
 protected static function resetInsertionMode()
 {
  # Let last be false.
  $last=false;

  # Let node be the last node in the stack of open elements.
  $node=static::$currentNode;
  $nodeName=static::$currentNodeName;
  $nodePos=static::$stackSize-1;

  # A lot of code is being repeated here because one check for a fragment
  # is much faster than several per loop.
  if(static::$fragment===false)
  {
   while(true)
   {
    # If node is a td or th element and last is false, then switch the insertion
    # mode to "in cell" and abort these steps.
    if(($nodeName=='td' || $nodeName=='th') && $last===false)
    {
     static::$mode='in cell';
     return;
    }

    # If node is a tr element, then switch the insertion mode to "in row" and
    # abort these steps.
    if($nodeName=='tr')
    {
     static::$mode='in row';
     return;
    }

    # If node is a tbody, thead, or tfoot element, then switch the insertion mode
    # to "in table body" and abort these steps.
    if($nodeName=='tbody' || $nodeName=='thead' || $nodeName=='tfoot')
    {
     static::$mode='in table body';
     return;
    }

    # If node is a caption element, then switch the insertion mode to
    # "in caption" and abort these steps.
    if($nodeName=='caption')
    {
     static::$mode='in caption';
     return;
    }

    # If node is a body element, then switch the insertion mode to
    # "in body" and abort these steps.
    if($nodeName=='body')
    {
     static::$mode='in body';
     return;
    }

    $nodePos--;
    $node=static::$stack[$nodePos];
    $nodeName=$node->nodeName;
   }
  }
  else
  {
   while(true)
   {
    # If node is the first node in the stack of open elements, then set last to
    # true and set node to the context element. (fragment case)
    if($node->isSameNode(static::$stack[0]))
    {
     $last=true;
     $node=static::$context;
     $nodeName=$node->nodeName;
    }

    # If node is a select element, then switch the insertion mode to "in select"
    # and abort these steps. (fragment case)
    if($nodeName=='select')
    {
     static::$mode='in select';
     return;
    }

    # If node is a td or th element and last is false, then switch the insertion
    # mode to "in cell" and abort these steps.
    if(($nodeName=='td' || $nodeName=='th') && $last===false)
    {
     static::$mode='in cell';
     return;
    }

    # If node is a tr element, then switch the insertion mode to "in row" and
    # abort these steps.
    if($nodeName=='tr')
    {
     static::$mode='in row';
     return;
    }

    # If node is a tbody, thead, or tfoot element, then switch the insertion mode
    # to "in table body" and abort these steps.
    if($nodeName=='tbody' || $nodeName=='thead' || $nodeName=='tfoot')
    {
     static::$mode='in table body';
     return;
    }

    # If node is a caption element, then switch the insertion mode to
    # "in caption" and abort these steps.
    if($nodeName=='caption')
    {
     static::$mode='in caption';
     return;
    }

    # If node is a colgroup element, then switch the insertion mode to
    # "in column group" and abort these steps. (fragment case)
    if($nodeName=='colgroup')
    {
     static::$mode='in column group';
     return;
    }

    # If node is a table element, then switch the insertion mode to
    # "in table" and abort these steps.
    if($nodeName=='table')
    {
     static::$mode='in table';
     return;
    }

    # If node is a head element, then switch the insertion mode to
    # "in body" ("in body"!  not "in head"!) and abort these steps.
    # (fragment case)
    # If node is a body element, then switch the insertion mode to
    # "in body" and abort these steps.
    if($nodeName=='head' || $nodeName=='body')
    {
     static::$mode='in body';
     return;
    }

    # If node is a frameset element, then switch the insertion mode to
    # "in frameset" and abort these steps. (fragment case)
    if($nodeName=='frameset')
    {
     static::$mode='in frameset';
     return;
    }

    # If node is an html element, then  switch the insertion mode to
    # "before head" Then, abort these steps. (fragment case)
    if($nodeName=='html')
    {
     static::$mode='before head';
     return;
    }

    # If last is true, then switch the insertion mode to "in body" and abort
    # these steps. (fragment case)
    if($last===true)
    {
     static::$mode='in body';
     return;
    }

    $nodePos--;
    $node=static::$stack[$nodePos];
    $nodeName=static::$stack->nodeName;
   }
  }
 }

 public static function errorHandler($level,$message,$file,$line,$context)
 {
  switch($level)
  {
   case E_USER_WARNING: echo 'HTML5 Parse Error: '.$message."\n";
   break;
   case E_USER_ERROR: echo 'HTML5 Fatal Error: '.$message.' in '.$file.' on line '.$line."\n";
   break;
   default: return false;
  }

  if(static::$debug)
   echo 'state: '.static::$state."\n".'mode: '.static::$mode."\n";
 }

 public static function parseError($error)
 {
  $message=static::$parseErrors[$error];
  if(is_null($message))
   return static::fatalError('invalid parse error',__METHOD__,$error);

  $args=func_get_args();
  $length=sizeof($args);
  if($length>1)
  {
   array_shift($args);

   $args=array_map(function($value)
   {
    if($value==="\n")
     return 'Newline';

    return "'$value'";
   },$args);

   $message=call_user_func_array('sprintf',array_merge([$message],$args));
  }

  trigger_error($message,E_USER_WARNING);
 }

 public static function fatalError($error,$method)
 {
  if(!is_string($method))
   return static::fatalError('method expected',__METHOD__);

  $message=static::$fatalErrors[$error];
  if(is_null($message))
   return static::fatalError('invalid fatal error',__METHOD__,$error);

  $args=func_get_args();
  $length=sizeof($args);
  if($length>2)
  {
   array_shift($args);
   array_shift($args);

   $args=array_map(function($value)
   {
    if($value==="\n")
     return 'Newline';

    return "'$value'";
   },$args);

   $message=call_user_func_array('sprintf',array_merge(array($message),$args));
  }

  trigger_error($method.': '.$message,E_USER_ERROR);
  return false;
 }
}
