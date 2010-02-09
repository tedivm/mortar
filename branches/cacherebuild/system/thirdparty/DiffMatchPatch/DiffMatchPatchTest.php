<HTML>
<BODY>
<H1>Test harness for diff_match_patch.js</H1>

<P>Version 1.5, October 2006</P>

<P>If debugging errors, start with the first reported error, subsequent tests often rely on earlier ones.</P>

<?php
include_once( 'DiffMatchPatch.class.php' );

$_DIFF_DELETE = -1;
$_DIFF_INSERT = 1;
$_DIFF_EQUAL = 0;


//set_time_limit( 120 );

function test( $a, $b ) {
    // If a and b are the same, print "Ok", otherwise "Fail!"
    if (equal( $a, $b )) {
        print( "<FONT COLOR='#009900'>Ok</FONT><BR>" );
    } else {
        print( "<FONT COLOR='#990000'>Fail!</FONT><BR>" );
    }
}

function equal( $a, $b ) {
    // Are a and b the same? -- Recursive.
    if (is_null( $a ) && is_null( $b )) {
        return true;
    } else if (is_array( $a ) && is_array( $b )) {
        $cnt = count( $a );
        if ($cnt != count( $b )) {
            return false;
        }
        for ($i = 0; $i < $cnt; $i++) {
            if (! equal( $a[$i], $b[$i] )) {
                return false;
            }
        }
        return true;
    } else {
        return $a == $b;
    }
}

$diffObj = new DiffMatchPatch();

  //////////
 // Diff //
//////////

// Detect and remove any common prefix.
print("<H3>diff_prefix:</H3>");
// Null case
test($diffObj->diff_prefix("abc", "xyz"), array("abc", "xyz", ""));
// Non-null case
test($diffObj->diff_prefix("1234abc", "1234xyz"), array("abc", "xyz", "1234"));

// Detect and remove any common suffix.
print("<H3>diff_suffix:</H3>");
// Null case
test($diffObj->diff_suffix("abc", "xyz"), array("abc", "xyz", ""));
// Non-null case
test($diffObj->diff_suffix("abc1234", "xyz1234"), array("abc", "xyz", "1234"));

// Detect a halfmatch.
print("<H3>diff_halfmatch:</H3>");
// No match
test($diffObj->diff_halfmatch("1234567890", "abcdef"), null);
// Single Match
test($diffObj->diff_halfmatch("1234567890", "a345678z"), array("12", "90", "a", "z", "345678"));
test($diffObj->diff_halfmatch("a345678z", "1234567890"), array("a", "z", "12", "90", "345678"));
// Multiple Matches
test($diffObj->diff_halfmatch("121231234123451234123121", "a1234123451234z"), array("12123", "123121", "a", "z", "1234123451234"));
test($diffObj->diff_halfmatch("x-=-=-=-=-=-=-=-=-=-=-=-=", "xx-=-=-=-=-=-=-="), array("", "-=-=-=-=-=", "x", "", "x-=-=-=-=-=-=-="));
test($diffObj->diff_halfmatch("-=-=-=-=-=-=-=-=-=-=-=-=y", "-=-=-=-=-=-=-=yy"), array("-=-=-=-=-=", "", "", "y", "-=-=-=-=-=-=-=y"));

// Convert lines down to characters
print("<H3>diff_lines2chars:</H3>");
test($diffObj->diff_lines2chars("alpha\nbeta\nalpha\n", "beta\nalpha\nbeta\n"),
     array(array("\x00\x01", "\x00\x02", "\x00\x01"),
           array("\x00\x02", "\x00\x01", "\x00\x02"),
           array('', "alpha\n", "beta\n")));

// Convert chars up to lines
print("<H3>diff_chars2lines:</H3>");
$diff = array(array($_DIFF_EQUAL, array("\x00\x01","\x00\x02","\x00\x01")), array($_DIFF_INSERT, array("\x00\x02","\x00\x01","\x00\x02")));
$lineArray = array('', "alpha\n", "beta\n");
$diffObj->diff_chars2lines($diff, $lineArray);
test($diff, array(array($_DIFF_EQUAL, "alpha\nbeta\nalpha\n"), array($_DIFF_INSERT, "beta\nalpha\nbeta\n")));

// Cleanup a messy diff
print("<H3>diff_cleanupMerge:</H3>");
// Null case
$diff = array();
$diffObj->diff_cleanupMerge($diff);
test($diff, array());
// No change case
$diff = array(array($_DIFF_EQUAL, "a"), array($_DIFF_DELETE, "b"), array($_DIFF_INSERT, "c"));
$diffObj->diff_cleanupMerge($diff);
test($diff, array(array($_DIFF_EQUAL, "a"), array($_DIFF_DELETE, "b"), array($_DIFF_INSERT, "c")));
// Merge equalities
$diff = array(array($_DIFF_EQUAL, "a"), array($_DIFF_EQUAL, "b"), array($_DIFF_EQUAL, "c"));
$diffObj->diff_cleanupMerge($diff);
test($diff, array(array($_DIFF_EQUAL, "abc")));
// Merge deletions
$diff = array(array($_DIFF_DELETE, "a"), array($_DIFF_DELETE, "b"), array($_DIFF_DELETE, "c"));
$diffObj->diff_cleanupMerge($diff);
test($diff, array(array($_DIFF_DELETE, "abc")));
// Merge insertions
$diff = array(array($_DIFF_INSERT, "a"), array($_DIFF_INSERT, "b"), array($_DIFF_INSERT, "c"));
$diffObj->diff_cleanupMerge($diff);
test($diff, array(array($_DIFF_INSERT, "abc")));
// Merge interweave
$diff = array(array($_DIFF_DELETE, "a"), array($_DIFF_INSERT, "b"), array($_DIFF_DELETE, "c"),
              array($_DIFF_INSERT, "d"), array($_DIFF_EQUAL, "e"), array($_DIFF_EQUAL, "f"));
$diffObj->diff_cleanupMerge($diff);
test($diff, array(array($_DIFF_DELETE, "ac"), array($_DIFF_INSERT, "bd"), array($_DIFF_EQUAL, "ef")));
// Prefix and suffix detection
$diff = array(array($_DIFF_DELETE, "a"), array($_DIFF_INSERT, "abc"), array($_DIFF_DELETE, "dc"));
$diffObj->diff_cleanupMerge($diff);
test($diff, array(array($_DIFF_EQUAL, "a"), array($_DIFF_DELETE, "d"), array($_DIFF_INSERT, "b"), array($_DIFF_EQUAL, "c")));


// Cleanup semantically trivial equalities
print("<H3>diff_cleanupSemantic:</H3>");
// No elimination
$diff = array(array($_DIFF_DELETE, "a"), array($_DIFF_INSERT, "b"), array($_DIFF_EQUAL, "cd"), array($_DIFF_DELETE, "e"));
$diffObj->diff_cleanupSemantic($diff);
test($diff, array(array($_DIFF_DELETE, "a"), array($_DIFF_INSERT, "b"), array($_DIFF_EQUAL, "cd"), array($_DIFF_DELETE, "e")));
// Simple elimination
$diff = array(array($_DIFF_DELETE, "a"), array($_DIFF_EQUAL, "b"), array($_DIFF_DELETE, "c"));
$diffObj->diff_cleanupSemantic($diff);
test($diff, array(array($_DIFF_DELETE, "abc"), array($_DIFF_INSERT, "b")));
// Backpass elimination
$diff = array(array($_DIFF_DELETE, "ab"), array($_DIFF_EQUAL, "cd"), array($_DIFF_DELETE, "e"), array($_DIFF_EQUAL, "f"), array($_DIFF_INSERT, "g"));
$diffObj->diff_cleanupSemantic($diff);
test($diff, array(array($_DIFF_DELETE, "abcdef"), array($_DIFF_INSERT, "cdfg")));

// Cleanup operationally trivial equalities
print("<H3>diff_cleanupEfficiency:</H3>");
$_DIFF_EDIT_COST = 4;
// No elimination
$diff = array(array($_DIFF_DELETE, "ab"), array($_DIFF_INSERT, "12"), array($_DIFF_EQUAL, "wxyz"), array($_DIFF_DELETE, "cd"), array($_DIFF_INSERT, "34")); 
$diffObj->diff_cleanupEfficiency($diff);
test($diff, array(array($_DIFF_DELETE, "ab"), array($_DIFF_INSERT, "12"), array($_DIFF_EQUAL, "wxyz"), array($_DIFF_DELETE, "cd"), array($_DIFF_INSERT, "34")));
// Four-edit elimination
$diff = array(array($_DIFF_DELETE, "ab"), array($_DIFF_INSERT, "12"), array($_DIFF_EQUAL, "xyz"), array($_DIFF_DELETE, "cd"), array($_DIFF_INSERT, "34"));
$diffObj->diff_cleanupEfficiency($diff);
test($diff, array(array($_DIFF_DELETE, "abxyzcd"), array($_DIFF_INSERT, "12xyz34")));
// Three-edit elimination
$diff = array(array($_DIFF_INSERT, "12"), array($_DIFF_EQUAL, "x"), array($_DIFF_DELETE, "cd"), array($_DIFF_INSERT, "34"));
$diffObj->diff_cleanupEfficiency($diff);
test($diff, array(array($_DIFF_DELETE, "xcd"), array($_DIFF_INSERT, "12x34")));
// Backpass elimination
$diff = array(array($_DIFF_DELETE, "ab"), array($_DIFF_INSERT, "12"), array($_DIFF_EQUAL, "xy"),
              array($_DIFF_INSERT, "34"), array($_DIFF_EQUAL, "z"), array($_DIFF_DELETE, "cd"), array($_DIFF_INSERT, "56"));
$diffObj->diff_cleanupEfficiency($diff);
test($diff, array(array($_DIFF_DELETE, "abxyzcd"), array($_DIFF_INSERT, "12xy34z56")));
// High cost elimination
$diffObj = new DiffMatchPatch(array('editcost' => 5));
$diff = array(array($_DIFF_DELETE, "ab"), array($_DIFF_INSERT, "12"), array($_DIFF_EQUAL, "wxyz"), array($_DIFF_DELETE, "cd"), array($_DIFF_INSERT, "34"));
$diffObj->diff_cleanupEfficiency($diff);
test($diff, array(array($_DIFF_DELETE, "abwxyzcd"), array($_DIFF_INSERT, "12wxyz34")));
$diffObj = new DiffMatchPatch();

// Add an index to each $diff tuple
print("<H3>diff_addIndex:</H3>");
$diff = array(array($_DIFF_DELETE, "a"), array($_DIFF_INSERT, "12"), array($_DIFF_EQUAL, "wxy"),
              array($_DIFF_INSERT, "34"), array($_DIFF_EQUAL, "z"), array($_DIFF_DELETE, "bcd"), array($_DIFF_INSERT, "56"));
$diffObj->diff_addIndex($diff);
test($diff, array(array($_DIFF_DELETE, 'a', 0), array($_DIFF_INSERT, '12', 0), array($_DIFF_EQUAL, 'wxy', 2),
                  array($_DIFF_INSERT, '34', 5), array($_DIFF_EQUAL, 'z', 7), array($_DIFF_DELETE, 'bcd', 8), array($_DIFF_INSERT, '56', 8)));

// Pretty print
print("<H3>diff_prettyHtml:</H3>");
$diff = array(array($_DIFF_EQUAL, "a\n"), array($_DIFF_DELETE, "<B>b</B>"), array($_DIFF_INSERT, "c&d"));
test($diffObj->diff_prettyHtml($diff), "<SPAN TITLE='i=0'>a&para;<BR></SPAN><DEL STYLE='background:#FFE6E6;' TITLE='i=2'>&lt;B&gt;b&lt;/B&gt;</DEL><INS STYLE='background:#E6FFE6;' TITLE='i=2'>c&amp;d</INS>");

// Translate a location in text1 to text2
print("<H3>diff_xIndex:</H3>");
// Translation on equality
$tmp = array(array($_DIFF_DELETE, "a"), array($_DIFF_INSERT, "1234"), array($_DIFF_EQUAL, "xyz"));
test($diffObj->diff_xIndex($tmp, 2), 5);
// Translation on deletion
$tmp = array(array($_DIFF_EQUAL, "a"), array($_DIFF_DELETE, "1234"), array($_DIFF_EQUAL, "xyz"));
test($diffObj->diff_xIndex($tmp, 3), 1);

// Trace a path from back to front.
print("<H3>diff_path1 & diff_path2:</H3>");
// Single letters
$v_map = array();
$v_map[] = array( '0,0' => true );
$v_map[] = array( '0,1' => true, '1,0' => true );
$v_map[] = array( '0,2' => true, '2,0' => true, '2,2' => true );
$v_map[] = array( '0,3' => true, '2,3' => true, '3,0' => true, '4,3' => true );
$v_map[] = array( '0,4' => true, '2,4' => true, '4,0' => true, '4,4' => true, '5,3' => true );
$v_map[] = array( '0,5' => true, '2,5' => true, '4,5' => true, '5,0' => true, '6,3' => true, '6,5' => true );
$v_map[] = array( '0,6' => true, '2,6' => true, '4,6' => true, '6,6' => true, '7,5' => true );
test($diffObj->diff_path1($v_map, "A1B2C3D", "W12X3"), array(array($_DIFF_INSERT, 'W'), array($_DIFF_DELETE, 'A'), array($_DIFF_EQUAL, '1'), array($_DIFF_DELETE, 'B'), array($_DIFF_EQUAL, '2'), array($_DIFF_INSERT, 'X'), array($_DIFF_DELETE, 'C'), array($_DIFF_EQUAL, '3'), array($_DIFF_DELETE, 'D')));
array_pop( $v_map );
test($diffObj->diff_path2($v_map, "4E5F6G", "4Y56Z"), array(array($_DIFF_EQUAL, '4'), array($_DIFF_DELETE, 'E'), array($_DIFF_INSERT, 'Y'), array($_DIFF_EQUAL, '5'), array($_DIFF_DELETE, 'F'), array($_DIFF_EQUAL, '6'), array($_DIFF_DELETE, 'G'), array($_DIFF_INSERT, 'Z')));
// Double letters
$v_map = array();
$v_map[] = array( '0,0' => true );
$v_map[] = array( '0,1' => true, '1,0' => true );
$v_map[] = array( '0,2' => true, '1,1' => true, '2,0' => true );
$v_map[] = array( '0,3' => true, '1,2' => true, '2,1' => true, '3,0' => true );
$v_map[] = array( '0,4' => true, '1,3' => true, '3,1' => true, '4,0' => true, '4,4' => true );
test($diffObj->diff_path1($v_map, "AB12", "WX12"), array(array($_DIFF_INSERT, 'WX'), array($_DIFF_DELETE, 'AB'), array($_DIFF_EQUAL, '12')));
$v_map = array();
$v_map[] = array( '0,0' => true );
$v_map[] = array( '0,1' => true, '1,0' => true );
$v_map[] = array( '1,1' => true, '2,0' => true, '2,4' => true );
$v_map[] = array( '2,1' => true, '2,5' => true, '3,0' => true, '3,4' => true );
$v_map[] = array( '2,6' => true, '3,5' => true, '4,4' => true );
test($diffObj->diff_path2($v_map, "CD34", "34YZ"), array(array($_DIFF_DELETE, 'CD'), array($_DIFF_EQUAL, '34'), array($_DIFF_INSERT, 'YZ')));

// Perform a trivial diff
print("<H3>diff (shortcuts):</H3>");
// Null case
test($diffObj->diff_main("abc", "abc", false), array(array($_DIFF_EQUAL, "abc")));
// Simple insertion
test($diffObj->diff_main("abc", "ab123c", false), array(array($_DIFF_EQUAL, "ab"), array($_DIFF_INSERT, "123"), array($_DIFF_EQUAL, "c")));
// Simple deletion
test($diffObj->diff_main("a123bc", "abc", false), array(array($_DIFF_EQUAL, "a"), array($_DIFF_DELETE, "123"), array($_DIFF_EQUAL, "bc")));
// Two insertions
test($diffObj->diff_main("abc", "a123b456c", false), array(array($_DIFF_EQUAL, "a"), array($_DIFF_INSERT, "123"), array($_DIFF_EQUAL, "b"),
                                                 array($_DIFF_INSERT, "456"), array($_DIFF_EQUAL, "c")));
// Two deletions
test($diffObj->diff_main("a123b456c", "abc", false), array(array($_DIFF_EQUAL, "a"), array($_DIFF_DELETE, "123"), array($_DIFF_EQUAL, "b"),
                                                 array($_DIFF_DELETE, "456"), array($_DIFF_EQUAL, "c")));

// Perform a real diff
print("<H3>diff (real):</H3>");
// Switch off the timeout.
$_DIFF_TIMEOUT= 0;
// Simple cases
test($diffObj->diff_main("a", "b", false), array(array($_DIFF_DELETE, "a"), array($_DIFF_INSERT, "b")));
test($diffObj->diff_main("Apples are a fruit.", "Bananas are also fruit.", false), array(array($_DIFF_DELETE, "Apple"), array($_DIFF_INSERT, "Banana"), array($_DIFF_EQUAL, 's are a'), array($_DIFF_INSERT, 'lso'), array($_DIFF_EQUAL, ' fruit.')));
// Overlaps
test($diffObj->diff_main("1ayb2", "abxab", false), array(array($_DIFF_DELETE, '1'), array($_DIFF_EQUAL, 'a'), array($_DIFF_DELETE, 'y'), array($_DIFF_EQUAL, 'b'), array($_DIFF_DELETE, '2'), array($_DIFF_INSERT, 'xab')));
test($diffObj->diff_main("abcy", "xaxcxabc", false), array(array($_DIFF_INSERT, 'x'), array($_DIFF_EQUAL, 'a'), array($_DIFF_DELETE, 'b'), array($_DIFF_INSERT, 'x'), array($_DIFF_EQUAL, 'c'), array($_DIFF_DELETE, 'y'), array($_DIFF_INSERT, 'xabc')));

// Test the linemode speedup
print("<H3>diff (linemode):</H3>");
// Must be long to pass the 250 char cutoff.
$a = "1234567890\n1234567890\n1234567890\n1234567890\n1234567890\n1234567890\n1234567890\n1234567890\n1234567890\n1234567890\n1234567890\n1234567890\n1234567890\n";
$b = "abcdefghij\nabcdefghij\nabcdefghij\nabcdefghij\nabcdefghij\nabcdefghij\nabcdefghij\nabcdefghij\nabcdefghij\nabcdefghij\nabcdefghij\nabcdefghij\nabcdefghij\n";
test($diffObj->diff_main($a, $b, true), $diffObj->diff_main($a, $b, false));
$a = "1234567890\n1234567890\n1234567890\n1234567890\n1234567890\n1234567890\n1234567890\n1234567890\n1234567890\n1234567890\n1234567890\n1234567890\n1234567890\n";
$a = "abcdefghij\n1234567890\n1234567890\n1234567890\nabcdefghij\n1234567890\n1234567890\n1234567890\nabcdefghij\n1234567890\n1234567890\n1234567890\nabcdefghij\n";
test($diffObj->diff_main($a, $b, true), $diffObj->diff_main($a, $b, false));

/*****************************************************************************************************************************************************************
 *****************************************************************************************************************************************************************

  ///////////
 // Match //
///////////

// Initialise the bitmasks for Bitap
print("<H3>match_alphabet:</H3>");
// Unique
test(match_alphabet("abc"), {'a':4, 'b':2, 'c':1});
// Duplicates
test(match_alphabet("abcaba"), {'a':37, 'b':18, 'c':8});

// Bitap algorithm
print("<H3>match_bitap:</H3>");
MATCH_BALANCE = 0.5;
MATCH_THRESHOLD = 0.5;
MATCH_MINLENGTH = 100;
MATCH_MAXLENGTH = 1000;
// Exact matches
test(match_bitap("abcdefghijk", "fgh", 5), 5);
test(match_bitap("abcdefghijk", "fgh", 0), 5);
// Fuzzy matches
test(match_bitap("abcdefghijk", "efxhi", 0), 4);
test(match_bitap("abcdefghijk", "cdefxyhijk", 5), 2);
test(match_bitap("abcdefghijk", "bxy", 1), null);
// Overflow
test(match_bitap("123456789xx0", "3456789x0", 2), 2);
// Threshold test
MATCH_THRESHOLD = 0.75;
test(match_bitap("abcdefghijk", "efxyhi", 1), 4);
MATCH_THRESHOLD = 0.1;
test(match_bitap("abcdefghijk", "bcdef", 1), 1);
MATCH_THRESHOLD = 0.5;
// Multiple select
test(match_bitap("abcdexyzabcde", "abccde", 3), 0);
test(match_bitap("abcdexyzabcde", "abccde", 5), 8);
// Balance test
MATCH_BALANCE = 0.6; // Strict location, loose accuracy.
test(match_bitap("abcdefghijklmnopqrstuvwxyz", "abcdefg", 24), null);
test(match_bitap("abcdefghijklmnopqrstuvwxyz", "abcxdxexfgh", 1), 0);
MATCH_BALANCE = 0.4; // Strict accuracy loose location.
test(match_bitap("abcdefghijklmnopqrstuvwxyz", "abcdefg", 24), 0);
test(match_bitap("abcdefghijklmnopqrstuvwxyz", "abcxdxexfgh", 1), null);
MATCH_BALANCE = 0.5;

// Full match
print("<H3>match_main:</H3>");
// Shortcut matches
test(match_main("abcdef", "abcdef", 1000), 0);
test(match_main("", "abcdef", 1), null);
test(match_main("abcdef", "", 3), 3);
test(match_main("abcdef", "de", 3), 3);
// Complex match
MATCH_THRESHOLD = 0.7;
test(match_main("I am the very model of a modern major general.", " that berry ", 5), 4);
MATCH_THRESHOLD = 0.5;

  ///////////
 // Patch //
///////////

// Patch Object
print("<H3>patch_obj:</H3>");
var p = new patch_obj();
p.start1 = 20;
p.start2 = 21;
p.length1 = 18;
p.length2 = 17;
p.diffs = [[DIFF_EQUAL, "jump"], [DIFF_DELETE, "s"], [DIFF_INSERT, "ed"], [DIFF_EQUAL, " over "], [DIFF_DELETE, "the"], [DIFF_INSERT, "a"], [DIFF_EQUAL, " laz"]];
test(p.text1(), "jumps over the laz");
test(p.text2(), "jumped over a laz");
var strp = p.toString();
test(strp, "@@ -21,18 +22,17 @@\n jump\n-s\n+ed\n  over \n-the\n+a\n  laz\n");
print("<H3>patch_fromtext:</H3>");
test(patch_fromtext(strp)[0].toString(), strp);
test(patch_fromtext("@@ -1 +1 @@\n-a\n+b\n")[0].toString(), "@@ -1 +1 @@\n-a\n+b\n");
test(patch_fromtext("@@ -1,3 +0,0 @@\n-abc\n")[0].toString(), "@@ -1,3 +0,0 @@\n-abc\n");
test(patch_fromtext("@@ -0,0 +1,3 @@\n+abc\n")[0].toString(), "@@ -0,0 +1,3 @@\n+abc\n");

print("<H3>patch_totext:</H3>");
test(patch_totext([p]), strp);

print("<H3>patch_addcontext:</H3>");
PATCH_MARGIN = 4;
p = patch_fromtext("@@ -21,4 +21,10 @@\n-jump\n+somersault\n")[0];
patch_addcontext(p, "The quick brown fox jumps over the lazy dog.");
test(p.toString(), "@@ -17,12 +17,18 @@\n fox \n-jump\n+somersault\n s ov\n");
// Same, but not enough trailing context.
p = patch_fromtext("@@ -21,4 +21,10 @@\n-jump\n+somersault\n")[0];
patch_addcontext(p, "The quick brown fox jumps.");
test(p.toString(), "@@ -17,10 +17,16 @@\n fox \n-jump\n+somersault\n s.\n");
// Same, but not enough leading context.
p = patch_fromtext("@@ -3 +3,2 @@\n-e\n+at\n")[0]
patch_addcontext(p, "The quick brown fox jumps.")
test(p.toString(), "@@ -1,7 +1,8 @@\n Th\n-e\n+at\n  qui\n")
// Same, but with ambiguity.
p = patch_fromtext("@@ -3 +3,2 @@\n-e\n+at\n")[0]
patch_addcontext(p, "The quick brown fox jumps.  The quick brown fox crashes.")
test(p.toString(), "@@ -1,27 +1,28 @@\n Th\n-e\n+at\n  quick brown fox jumps. \n")

print("<H3>patch_make:</H3>");
var patches = patch_make("The quick brown fox jumps over the lazy dog.", "That quick brown fox jumped over a lazy dog.")
test(patch_totext(patches), "@@ -1,11 +1,12 @@\n Th\n-e\n+at\n  quick b\n@@ -21,18 +22,17 @@\n jump\n-s\n+ed\n  over \n-the\n+a\n  laz\n")

print("<H3>patch_splitmax:</H3>");
// Assumes that MATCH_MAXBITS is 32.
patches = patch_make("abcdef1234567890123456789012345678901234567890123456789012345678901234567890uvwxyz", "abcdefuvwxyz");
patch_splitmax(patches);
test(patch_totext(patches), "@@ -3,32 +3,8 @@\n cdef\n-123456789012345678901234\n 5678\n@@ -27,32 +3,8 @@\n cdef\n-567890123456789012345678\n 9012\n@@ -51,30 +3,8 @@\n cdef\n-9012345678901234567890\n uvwx\n");

print("<H3>patch_apply:</H3>");
// Exact match
patches = patch_make("The quick brown fox jumps over the lazy dog.", "That quick brown fox jumped over a lazy dog.");
var results = patch_apply(patches, "The quick brown fox jumps over the lazy dog.");
test(results, ['That quick brown fox jumped over a lazy dog.', [true, true]]);
// Partial match
results = patch_apply(patches, "The quick red rabbit jumps over the tired tiger.");
test(results, ['That quick red rabbit jumped over a tired tiger.', [true, true]]);
// Failed match
results = patch_apply(patches, "I am the very model of a modern major general.");
test(results, ['I am the very model of a modern major general.', [false, false]]);

print("<H3>Done.</H3>");

//--></SCRIPT>
 *****************************************************************************************************************************************************************
 *****************************************************************************************************************************************************************/
?>

</BODY>
</HTML>
