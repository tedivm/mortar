	<?php
	
	// Diff_Match_Patch v1.5
	// Computes the difference between two texts to create a patch.
	// Applies the patch onto another text, allowing for errors.
	// Copyright (C) 2006 Neil Fraser
	// http://neil.fraser.name/software/diff_match_patch/
	
	// This program is free software you can redistribute it and/or
	// modify it under the terms of the GNU Lesser General Public
	// License as published by the Free Software Foundation.
	
	// This program is distributed in the hope that it will be useful,
	// but WITHOUT ANY WARRANTY without even the implied warranty of
	// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	// GNU Lesser General Public License (www.gnu.org) for more details.
	
	
	// Defaults.
	// Redefine these in your program to override the defaults.
	
class DiffMatchPatch
{
	// Number of seconds to map a diff before giving up. (0 for infinity)
	protected $_DIFF_TIMEOUT = 1.0;
	// Cost of an empty edit operation in terms of edit characters.
	protected $_DIFF_EDIT_COST = 4;
	// Tweak the relative importance (0.0 = accuracy, 1.0 = proximity)
	/* $_MATCH_BALANCE = 0.5; */
	// At what point is no match declared (0.0 = perfection, 1.0 = very loose)
	/* $_MATCH_THRESHOLD = 0.5; */
	// The min and max cutoffs used when computing text lengths.
	/* $_MATCH_MINLENGTH = 100; */
	/* $_MATCH_MAXLENGTH = 1000; */
	// Chunk size for context length.
	/* $_PATCH_MARGIN = 4; */
	
	
	//////////////////////////////////////////////////////////////////////
	// Diff                              //
	//////////////////////////////////////////////////////////////////////
	
	// The data structure representing a diff is an array of tuples:
	// [[DIFF_DELETE, "Hello"], [DIFF_INSERT, "Goodbye"], [DIFF_EQUAL, " world."]]
	// which means: delete "Hello", add "Goodbye" and keep " world."
	protected $_DIFF_DELETE = -1;
	protected $_DIFF_INSERT = 1;
	protected $_DIFF_EQUAL = 0;

	public function __construct( $opts = array() ) {
		if(isset($opts['timeout']))
			$this->_DIFF_TIMEOUT = $opts['timeout'];
		if(isset($opts['editcost']))
			$this->_DIFF_EDIT_COST = $opts['editcost'];
	}
	
	public function diff_main( $text1, $text2, $checkLines = true ) {
		// Find the differences between two texts. Return an array of changes.
		// If checkLines is present and false, then don't run a line-level diff first to identify the changed areas.
	
	
		// Check for equality (speedup)
		if (strcmp( $text1, $text2 ) == 0) {
			return array( array( $this->_DIFF_EQUAL, $text1 ) );
		}
	
		// Trim off common prefix (speedup)
		list( $text1, $text2, $commonPrefix ) = $this->diff_prefix( $text1, $text2 );
	
		// Trim off common suffix (speedup)
		list( $text1, $text2, $commonSuffix ) = $this->diff_suffix( $text1, $text2 );
	
		if (strlen( $text1 ) > strlen( $text2 )) {
			$longText = $text1;
			$shortText = $text2;
		} else {
			$longText = $text2;
			$shortText = $text1;
		}
	
		if (strlen( $text1 ) == 0) {
			// Just add some text (speedup)
			$diff = array( array( $this->_DIFF_INSERT, $text2 ) );
		} else if (strlen( $text2 ) == 0) {
			// Just delete some text (speedup)
			$diff = array( array( $this->_DIFF_DELETE, $text1 ) );
		} else if (($i = strpos( $longText, $shortText )) !== false) {
			// Shorter text is inside the longer text (speedup)
			$diff = array( array( $this->_DIFF_INSERT, substr( $longText, 0, $i ) ),
				array( $this->_DIFF_EQUAL, $shortText ),
				array( $this->_DIFF_INSERT, substr( $longText, $i + strlen( $shortText ) ) ) );
			if (strlen( $text1 ) > strlen( $text2 )) {
				// Swap insertions for deletions if diff is reversed.
				$diff[0][0] = $diff[2][0] = $this->_DIFF_DELETE;
			}
		} else {
			unset( $longText ); // Garbage collect
			unset( $shortText ); // Garbage collect
	
			// Check to see if the problem can be split in two.
			$hm = $this->diff_halfmatch( $text1, $text2 );
			if (! is_null( $hm )) {
				// A half-match was found, sort out the return data.
				list( $text1_a, $text1_b, $text2_a, $text2_b, $mid_common ) = $hm;
				// Send both pairs off for separate processing.
				$diff_a = $this->diff_main( $text1_a, $text2_a, $checkLines );
				$diff_b = $this->diff_main( $text1_b, $text2_b, $checkLines );
				// Merge the results.
				$diff_a[] = array( $this->_DIFF_EQUAL, $mid_common );
				$diff = array_merge( $diff_a, $diff_b );
			} else {
				// Perform a real diff.
				if ($checkLines && strlen( $text1 ) + strlen( $text2 ) < 250) {
					$checkLines = false; // Too trivial for the overhead.
				}
				if ($checkLines) {
					// Scan the text on a line-by-line basis first.
					list( $text1, $text2, $lineArray ) = $this->diff_lines2chars( $text1, $text2 );
				}
				$diff = $this->diff_map( $text1, $text2, $checkLines );
				if (is_null( $diff )) {
					// No acceptable result.
					$diff = array( array( $this->_DIFF_DELETE, $text1 ),
							array( $this->_DIFF_INSERT, $text2 ) );
				}
				if ($checkLines) {
					$this->diff_chars2lines( $diff, $lineArray ); // Convert the diff back to original text.
					$this->diff_cleanupSemantic( $diff ); // Eliminate freak matches (e.g. blank lines)
	
					// Rediff any replacement blocks, this time on character-by-character basis.
					$diff[] = array( $this->_DIFF_EQUAL, '' ); // Add a dummy entry at the end.
					$pointer = 0;
					$count_delete = 0;
					$count_insert = 0;
					$text_delete = '';
					$text_insert = '';
	
					while ($pointer < count( $diff )) {
						switch ($diff[ $pointer ][ 0 ]) {
							case $this->_DIFF_INSERT:
								$count_insert++;
								$text_insert .= $diff[ $pointer ][ 1 ];
								break;
							case $this->_DIFF_DELETE:
								$count_delete++;
								$text_delete .= $diff[ $pointer ][ 1 ];
								break;
							default: // Upon reaching an equality, check for prior redundancies.
								if ($count_delete >= 1 && $count_insert >= 1) {
									// Delete the offending records and add the merged ones.
									$a = $this->diff_main( $text_delete, $text_insert, false );
									array_splice( $diff, $pointer - $count_delete - $count_insert, $count_delete + $count_insert );
									$pointer -= ($count_delete + $count_insert);
									for ($i = count( $a ) - 1; $i >= 0; $i--) {
										array_splice( $diff, $pointer, 0, array( $a[$i] ) );
									}
									$pointer += count( $a );
								}
								$count_insert = 0;
								$count_delete = 0;
								$text_delete = '';
								$text_insert = '';
								break;
						}
						$pointer++;
					}
				array_pop( $diff ); // Remove the dummy entry at the end.
				}
			}
		}
		if ($commonPrefix) {
				array_unshift( $diff, array( $this->_DIFF_EQUAL, $commonPrefix ) );
		}
		if ($commonSuffix) {
				array_push( $diff, array( $this->_DIFF_EQUAL, $commonSuffix ) );
		}
		$this->diff_cleanupMerge( $diff );
		return $diff;
	}
	
	
	public function diff_lines2charsMunge( $text, &$linearr, &$linehh ) {
		$chars = array();
		while (strlen( $text ) > 0) {
			$i = strpos( $text, "\n" );
			if ($i === false) {
				$i = strlen( $text );
			}
			$line = substr( $text, 0, $i + 1 );
			$text = substr( $text, $i + 1 );
			if (! isset( $linehh[ $line ] )) {
				$linehh[ $line ] = count( $linearr );
				$linearr[] = $line;
			}
			$index = $linehh[ $line ];
			$chars[] = chr( $index >> 8 ) . chr( $index & 0xff );
		}
		return $chars;
	}
	
	public function diff_lines2chars( $text1, $text2 ) {
		// Split text into an array of strings.
		// Reduce the texts to a string of hashes where each character represents one line.
		$lineArray = array(); // lineArray[4] == "Hello\n"
		$lineHash = array(); // lineHash["Hello\n"] == 4
		$lineArray[] = '';
	
		return array( $this->diff_lines2charsMunge( $text1, $lineArray, $lineHash ), $this->diff_lines2charsMunge( $text2, $lineArray, $lineHash ), $lineArray );
	}
	
	
	public function diff_chars2lines( &$diff, &$lineArray ) {
		// Rehydrate the text in a diff from a string of line hashes to real lines of text.
		for ($i = 0; $i < count( $diff ); $i++) {
			$chars = $diff[ $i ][ 1 ];
			if (is_array( $chars )) {
				$chars = implode( '', $chars );
			}
			$len = strlen( $chars );
			$text = '';
			for ($j = 0; $j < $len; $j += 2) {
				$index = (ord( $chars[ $j ] ) << 8) | ord( $chars[ $j + 1 ] );
				$text .= $lineArray[ $index ];
			}
			$diff[ $i ][ 1 ] = $text;
		}
	}
	
	
	public function diff_map( $text1, $text2, $textIsArray = false ) {
	
		// Explore the intersection points between the two texts.
		$ms_end = microtime() + $this->_DIFF_TIMEOUT * 1000000; // Don't run for too long.
		$t1len = $textIsArray ? count( $text1 ) : strlen( $text1 );
		$t2len = $textIsArray ? count( $text2 ) : strlen( $text2 );
		$max = ($t1len + $t2len) / 2;
		$v_map1 = array();
		$v_map2 = array();
		$v1 = array();
		$v2 = array();
		$v1[1] = 0;
		$v2[1] = 0;
		$footsteps = array();
		$done = false;
		// If the total number of characters is odd, then the front path will collide with the reverse path.
		$front = ($t1len + $t2len) % 2;
		for ($d = 0; $d < $max; $d++) {
			if ($this->_DIFF_TIMEOUT > 0 && microtime() > $ms_end) { // Timeout reached
				return NULL;
			}
	
			// Walk the front path one step.
			$v_map1[ $d ] = array();
			for ($k = -$d; $k <= $d; $k += 2) {
				if ($k == -$d || $k != $d && $v1[ $k - 1 ] < $v1[ $k + 1 ]) {
					$x = $v1[ $k + 1 ];
				} else {
					$x = $v1[ $k - 1 ] + 1;
				}
				$y = $x - $k;
	
				while (true) {
					$footstep = "{$x},{$y}";
					if ($front && isset( $footsteps[ $footstep ] )) {
						$done = true;
					} else if (! $front) {
						$footsteps[ $footstep ] = $d;
					}
					if ($done || $x >= $t1len || $y >= $t2len || $text1[$x] != $text2[$y]) {
						break;
					}
					$x++; $y++;
				}
	
				$v1[ $k ] = $x;
				$v_map1[ $d ][ "{$x},{$y}" ] = true;
				if ($done) {
					// Front path ran over reverse path.
					$v_map2 = array_slice( $v_map2, 0, $footsteps[ $footstep ] + 1 );
					if ($textIsArray) {
						return array_merge( $this->diff_path1( $v_map1, array_slice( $text1, 0, $x ), array_slice( $text2, 0, $y ), true ),
								   $this->diff_path2( $v_map2, array_slice( $text1, $x ), array_slice( $text2, $y ), true ) );
					} else {
						return array_merge( $this->diff_path1( $v_map1, substr( $text1, 0, $x ), substr( $text2, 0, $y ) ),
								   $this->diff_path2( $v_map2, substr( $text1, $x ), substr( $text2, $y ) ) );
					}
				}
			}
	
			// Walk the reverse path one step.
			$v_map2[ $d ] = array();
			for ($k = -$d; $k <= $d; $k += 2) {
				if ($k == -$d || $k != $d && $v2[ $k - 1 ] < $v2[ $k + 1 ]) {
					$x = $v2[ $k + 1 ];
				} else {
					$x = $v2[ $k - 1 ] + 1;
				}
				$y = $x - $k;
	
				while (true) {
					$footstep = ($t1len - $x) . ',' . ($t2len - $y);
					if (! $front && isset( $footsteps[ $footstep ] )) {
						$done = true;
					} else if ($front) {
						$footsteps[ $footstep ] = $d;
					}
					if ($done || $x >= $t1len || $y >= $t2len || $text1[$t1len - $x - 1] != $text2[$t2len - $y - 1]) {
						break;
					}
					$x++; $y++;
				}
	
				$v2[ $k ] = $x;
				$v_map2[ $d ][ "{$x},{$y}" ] = true;
				if ($done) {
					// Reverse path ran over front path.
					$v_map1 = array_slice( $v_map1, 0, $footsteps[ $footstep ] + 1 );
					if ($textIsArray) {
						return array_merge( $this->diff_path1( $v_map1, array_slice( $text1, 0, $t1len - $x ), array_slice( $text2, 0, $t2len - $y ), true ),
								   $this->diff_path2( $v_map2, array_slice( $text1, $t1len - $x ), array_slice( $text2, $t2len - $y ), true ) );
					} else {
						return array_merge( $this->diff_path1( $v_map1, substr( $text1, 0, $t1len - $x ), substr( $text2, 0, $t2len - $y ) ),
								   $this->diff_path2( $v_map2, substr( $text1, $t1len - $x ), substr( $text2, $t2len - $y ) ) );
					}
				}
			}
		}
		// Number of diffs equals number of characters, no commonality at all.
		return NULL;
	}
	
	
	public function diff_path1( &$v_map, $text1, $text2, $textIsArray = false ) {
	
		// Work from the middle back to the start to determine the path.
		$path = array();
		$x = $textIsArray ? count( $text1 ) : strlen( $text1 );
		$y = $textIsArray ? count( $text2 ) : strlen( $text2 );
		$last_op = NULL;
		for ($d = count( $v_map ) - 2; $d >= 0; $d--) {
			while (true) {
				if (isset( $v_map[ $d ][ ($x - 1) . ',' . $y ] )) {
					$x--;
					if ($last_op === $this->_DIFF_DELETE) {
						$path[0][1] = $text1[$x] . $path[0][1];
					} else {
						array_unshift( $path, array( $this->_DIFF_DELETE, $text1[$x] ) );
					}
					$last_op = $this->_DIFF_DELETE;
					break;
				} else if (isset( $v_map[ $d ][ $x . ',' . ($y - 1) ] )) {
					$y--;
					if ($last_op === $this->_DIFF_INSERT) {
						$path[0][1] = $text2[$y] . $path[0][1];
					} else {
						array_unshift( $path, array( $this->_DIFF_INSERT, $text2[$y] ) );
					}
					$last_op = $this->_DIFF_INSERT;
					break;
				} else {
					$x--;
					$y--;
					//if ($text1[$x] != $text2[$y]) {
					//  return alert("No diagonal. Can't happen. (diff_path1)");
					//}
					if ($last_op === $this->_DIFF_EQUAL) {
						$path[0][1] = $text1[$x] . $path[0][1];
					} else {
						array_unshift( $path, array( $this->_DIFF_EQUAL, $text1[$x] ) );
					}
					$last_op = $this->_DIFF_EQUAL;
				}
			}
		}
		return $path;
	}
	
	
	public function diff_path2( &$v_map, $text1, $text2, $textIsArray = false ) {
	
		// Work from the middle back to the end to determine the path.
		$path = array();
		$x = $t1len = $textIsArray ? count( $text1 ) : strlen( $text1 );
		$y = $t2len = $textIsArray ? count( $text2 ) : strlen( $text2 );
		$last_op = NULL;
		for ($d = count( $v_map ) - 2; $d >= 0; $d--) {
			while (true) {
				if (isset( $v_map[ $d ][ ($x - 1) . ',' . $y ] )) {
					$ch = $text1[$t1len - $x];
					$x--;
					if ($last_op === $this->_DIFF_DELETE) {
						$path[ count( $path ) - 1 ][ 1 ] .= $ch;
					} else {
						array_push( $path, array( $this->_DIFF_DELETE, $ch ) );
					}
					$last_op = $this->_DIFF_DELETE;
					break;
				} else if (isset( $v_map[ $d ][ $x . ',' . ($y - 1) ] )) {
					$ch = $text2[ $t2len - $y];
					$y--;
					if ($last_op === $this->_DIFF_INSERT) {
						$path[ count( $path ) - 1 ][ 1 ] .= $ch;
					} else {
						array_push( $path, array( $this->_DIFF_INSERT, $ch ) );
					}
					$last_op = $this->_DIFF_INSERT;
					break;
				} else {
					$ch = $text1[$t1len - $x];
					$x--;
					$y--;
					//if ($text1[$t1len - $x - 1] != $text2[$t2len - $y - 1]) {
					//  return alert("No diagonal. Can't happen. (diff_path2)");
					//}
					if ($last_op === $this->_DIFF_EQUAL) {
						$path[ count( $path ) - 1 ][ 1 ] .= $ch;
					} else {
						array_push( $path, array( $this->_DIFF_EQUAL, $ch ) );
					}
					$last_op = $this->_DIFF_EQUAL;
				}
			}
		}
		return $path;
	}
	
	
	public function diff_prefix( $text1, $text2 ) {
		// Trim off common prefix
		$max = min( strlen( $text1 ), strlen( $text2 ) );
		for ($i = 0; $i < $max; $i++) {
			if ($text1[$i] != $text2[$i]) {
				break;
			}
		}
		return array( substr( $text1, $i ), substr( $text2, $i ), substr( $text1, 0, $i ) );
	}
	
	
	public function diff_suffix( $text1, $text2 ) {
		// Trim off common suffix
		for ($i = strlen( $text1 ) - 1, $j = strlen( $text2 ) - 1; $i >= 0 && $j >= 0; $i--, $j--) {
			if ($text1[$i] != $text2[$j]) {
				break;
			}
		}
		return array( substr( $text1, 0, $i + 1 ), substr( $text2, 0, $j + 1 ), substr( $text1, $i + 1 ) );
	}
	
	public function diff_halfmatch_i( $longer, $shorter, $i ) {
		// Start with a 1/4 length substring at position i as a seed.
		$seed = substr( $longer, $i, floor( strlen( $longer ) / 4 ) );
		$j = -1;
		$best_common = '';
		$best = NULL;
		while (($j = strpos( $shorter, $seed, $j + 1 )) !== false) {
			$my_prefix = $this->diff_prefix( substr( $longer, $i ), substr( $shorter, $j ) );
			$my_suffix = $this->diff_suffix( substr( $longer, 0, $i ), substr( $shorter, 0, $j ) );
			if (strlen( $best_common ) < (strlen( $my_prefix[2] ) + strlen( $my_suffix[2] ))) {
				$best_common = $my_suffix[2] . $my_prefix[2];
				$best = array( $my_suffix[0], $my_prefix[0], $my_suffix[1], $my_prefix[1] );
			}
		}
		if (strlen( $best_common ) >= strlen( $longer ) / 2) {
			$best[] = $best_common;
		} else {
			$best = NULL;
		}
		return $best;
	}
	
	public function diff_halfmatch( $text1, $text2 ) {
		// Do the two texts share a substring which is at least half the length of the longer text?
		if (strlen( $text1 ) > strlen( $text2 )) {
			$longText = $text1;
			$shortText = $text2;
		} else {
			$longText = $text2;
			$shortText = $text1;
		}
		if (strlen( $longText ) < 10 || strlen( $shortText ) < 1) {
				return NULL; // Pointless
		}
	
		// First check if the second quarter is the seed for a half-match.
		$hm1 = $this->diff_halfmatch_i( $longText, $shortText, ceil( strlen( $longText ) / 4 ) );
		// Check again based on the third quarter.
		$hm2 = $this->diff_halfmatch_i( $longText, $shortText, ceil( strlen( $longText ) / 2 ) );
		if (is_null( $hm1 ) && is_null( $hm2 )) {
			return NULL;
		} else if (is_null( $hm2 )) {
			$hm = $hm1;
		} else if (is_null( $hm1 )) {
			$hm = $hm2;
		} else { // Both matched. Select the longest.
			$hm = (strlen( $hm1[4] ) > strlen( $hm2[4] ) ? $hm1 : $hm2);
		}
	
		// A half-match was found, sort out the return data.
		if (strlen( $text1 ) > strlen( $text2 )) {
			return $hm;
		} else {
			return array( $hm[2], $hm[3], $hm[0], $hm[1], $hm[4] );
		}
	}
	
	
	public function diff_cleanupSemantic( &$diff ) {
	
		// Reduce the number of edits by eliminating semantically trivial equalities.
		$changes = false;
		$equalities = array(); // Stack of indices where equalities are found.
		$lastequality = NULL; // Always equal to equalities[equalities.length-1][1]
		$pointer = 0; // Index of current position.
		$length_changes1 = 0; // Number of characters that changed prior to the equality.
		$length_changes2 = 0; // Number of characters that changed after the equality.
		while ($pointer < count( $diff )) {
			if ($diff[ $pointer ][ 0 ] == $this->_DIFF_EQUAL) { // equality found
				array_push( $equalities, $pointer );
				$length_changes1 = $length_changes2;
				$length_changes2 = 0;
				$lastequality = $diff[ $pointer ][ 1 ];
			} else { // an insertion or deletion
				$length_changes2 += strlen( $diff[ $pointer ][ 1 ] );
				if (!is_null( $lastequality ) && (strlen( $lastequality ) <= $length_changes1) && (strlen( $lastequality ) <= $length_changes2)) {
					//alert("Splitting: '"+lastequality+"'");
					array_splice( $diff, $equalities[ count( $equalities ) - 1 ], 0, array( array( $this->_DIFF_DELETE, $lastequality ) ) ); // Duplicate record
					$diff[ $equalities[ count( $equalities ) - 1 ] + 1 ][ 0 ] = $this->_DIFF_INSERT; // Change second copy to insert.
					array_pop( $equalities ); // Throw away the equality we just deleted;
					array_pop( $equalities ); // Throw away the previous equality;
					$pointer = count( $equalities ) ? $equalities[ count( $equalities ) - 1 ] : -1;
					$length_changes1 = 0; // Reset the counters.
					$length_changes2 = 0;
					$lastequality = NULL;
					$changes = true;
				}
			}
			$pointer++;
		}
	
		if ($changes) {
			$this->diff_cleanupMerge( $diff );
		}
	}
	
	
	public function diff_cleanupEfficiency( &$diff ) {
	
		// Reduce the number of edits by eliminating operationally trivial equalities.
		$changes = false;
		$equalities = array(); // Stack of indices where equalities are found.
		$lastequality = ''; // Always equal to equalities[equalities.length-1][1]
		$pointer = 0; // Index of current position.
		$pre_ins = false; // Is there an insertion operation before the last equality.
		$pre_del = false; // Is there an deletion operation before the last equality.
		$post_ins = false; // Is there an insertion operation after the last equality.
		$post_del = false; // Is there an deletion operation after the last equality.
		while ($pointer < count( $diff )) {
			if ($diff[ $pointer ][ 0 ] == $this->_DIFF_EQUAL) { // equality found
				if (strlen( $diff[ $pointer ][ 1 ] ) < $this->_DIFF_EDIT_COST && ($post_ins || $post_del)) {
					// Candidate found.
					array_push( $equalities, $pointer );
					$pre_ins = $post_ins;
					$pre_del = $post_del;
					$lastequality = $diff[ $pointer ][ 1 ];
				} else {
					// Not a candidate, and can never become one.
					$equalities = array();
					$lastequality = '';
				}
				$post_ins = $post_del = false;
			} else { // an insertion or deletion
				if ($diff[ $pointer ][ 0 ] == $this->_DIFF_DELETE) {
					$post_del = true;
				} else {
					$post_ins = true;
				}
				// Five types to be split:
				// <ins>A</ins><del>B</del>XY<ins>C</ins><del>D</del>
				// <ins>A</ins>X<ins>C</ins><del>D</del>
				// <ins>A</ins><del>B</del>X<ins>C</ins>
				// <ins>A</del>X<ins>C</ins><del>D</del>
				// <ins>A</ins><del>B</del>X<del>C</del>
				if ($lastequality
					&& (($pre_ins && $pre_del && $post_ins && $post_del)
					|| ((strlen( $lastequality ) < $this->_DIFF_EDIT_COST/2) && ($pre_ins + $pre_del + $post_ins + $post_del) == 3)))
				{
					//alert("Splitting: '"+lastequality+"'");
					array_splice( $diff, $equalities[ count( $equalities ) - 1 ], 0, array( array( $this->_DIFF_DELETE, $lastequality ) ) ); // Duplicate record
					$diff[ $equalities[ count( $equalities ) - 1 ] + 1 ][ 0 ] = $this->_DIFF_INSERT; // Change second copy to insert.
					array_pop( $equalities ); // Throw away the equality we just deleted;
					$lastequality = '';
					if ($pre_ins && $pre_del) {
						// No changes made which could affect previous entry, keep going.
						$post_ins = $post_del = true;
						$equalities = array();
					} else {
						array_pop( $equalities ); // Throw away the previous equality;
						$pointer = count( $equalities ) ? $equalities[ count( $equalities ) - 1 ] : -1;
						$post_ins = $post_del = false;
					}
					$changes = true;
				}
			}
			$pointer++;
		}
	
		if ($changes) {
			$this->diff_cleanupMerge( $diff );
		} 
	}
	
	
	public function diff_cleanupMerge( &$diff ) {
	
		// Reorder and merge like edit sections. Merge equalities.
		// Any edit section can move as long as it doesn't cross an equality.
		array_push( $diff, array( $this->_DIFF_EQUAL, '' ) ); // Add a dummy entry at the end.
		$pointer = 0;
		$count_delete = 0;
		$count_insert = 0;
		$text_delete = '';
		$text_insert = '';
		$my_xfix;
		while ($pointer < count( $diff )) {
			if ($diff[ $pointer ][ 0 ] == $this->_DIFF_INSERT) {
				$count_insert++;
				$text_insert .= $diff[ $pointer ][ 1 ];
				$pointer++;
			} else if ($diff[ $pointer ][ 0 ] == $this->_DIFF_DELETE) {
				$count_delete++;
				$text_delete .= $diff[ $pointer ][ 1 ];
				$pointer++;
			} else { // Upon reaching an equality, check for prior redundancies.
				if ($count_delete != 0 || $count_insert != 0) {
					if ($count_delete != 0 && $count_insert != 0) {
						// Factor out any common prefixes.
						$my_xfix = $this->diff_prefix( $text_insert, $text_delete );
						if ($my_xfix[ 2 ] != '') {
							if (($pointer - $count_delete - $count_insert) > 0 && $diff[ $pointer - $count_delete - $count_insert - 1 ][ 0 ] == $this->_DIFF_EQUAL) {
								$diff[ $pointer - $count_delete - $count_insert - 1 ][ 1 ] .= $my_xfix[ 2 ];
							} else {
								array_splice( $diff, 0, 0, array( array( $this->_DIFF_EQUAL, $my_xfix[ 2 ] ) ) );
								$pointer++;
							}
							$text_insert = $my_xfix[ 0 ];
							$text_delete = $my_xfix[ 1 ];
						}
						// Factor out any common suffixies.
						$my_xfix = $this->diff_suffix( $text_insert, $text_delete );
						if ($my_xfix[ 2 ] != '') {
							$text_insert = $my_xfix[ 0 ];
							$text_delete = $my_xfix[ 1 ];
							$diff[ $pointer ][ 1 ] = $my_xfix[ 2 ] . $diff[ $pointer ][ 1 ];
						}
					}
					// Delete the offending records and add the merged ones.
					if ($count_delete == 0) {
						array_splice( $diff, $pointer - $count_delete - $count_insert, $count_delete + $count_insert, array( array( $this->_DIFF_INSERT, $text_insert ) ) );
					} else if ($count_insert == 0) {
						array_splice( $diff, $pointer - $count_delete - $count_insert, $count_delete + $count_insert, array( array( $this->_DIFF_DELETE, $text_delete ) ) );
					} else {
						array_splice( $diff, $pointer - $count_delete - $count_insert, $count_delete + $count_insert, array( array( $this->_DIFF_DELETE, $text_delete ),
													                                            array( $this->_DIFF_INSERT, $text_insert ) ) );
					}
					$pointer = $pointer - $count_delete - $count_insert + ($count_delete ? 1 : 0) + ($count_insert ? 1 : 0) + 1;
				} else if ($pointer != 0 && $diff[ $pointer - 1 ][ 0 ] == $this->_DIFF_EQUAL) {
					// Merge this equality with the previous one.
					$diff[ $pointer - 1 ][ 1 ] = $diff[ $pointer - 1 ][ 1 ] . $diff[ $pointer ][ 1 ];
					array_splice( $diff, $pointer, 1 );
				} else {
					$pointer++;
				}
				$count_insert = 0;
				$count_delete = 0;
				$text_delete = '';
				$text_insert = '';
			}
		}
		if (strlen( $diff[ count( $diff ) - 1 ][ 1 ] ) == 0) {
			array_pop( $diff ); // Remove the dummy entry at the end.
		}
	}
	
	
	public function diff_addIndex( &$diff ) {
	
		// Add an index to each tuple, represents where the tuple is located in text2.
		// e.g. [[DIFF_DELETE, 'h', 0], [DIFF_INSERT, 'c', 0], [DIFF_EQUAL, 'at', 1]]
		$i = 0;
		for ($x = 0; $x < count( $diff ); $x++) {
			array_push( $diff[ $x ], $i );
			if ($diff[ $x ][ 0 ] != $this->_DIFF_DELETE) {
				$i += strlen( $diff[ $x ][ 1 ] );
			}
		}
	}
	
	
	public function diff_xIndex( &$diff, $loc ) {
	
		// loc is a location in text1, compute and return the equivalent location in text2.
		// e.g. "The cat" vs "The big cat", 1->1, 5->8
		$chars1 = 0;
		$chars2 = 0;
		$last_chars1 = 0;
		$last_chars2 = 0;
		for ($x = 0; $x < count( $diff ); $x++) {
			if ($diff[ $x ][ 0 ] != $this->_DIFF_INSERT) { // Equality or deletion.
				$chars1 += strlen( $diff[ $x ][ 1 ] );
			}
			if ($diff[ $x ][ 0 ] != $this->_DIFF_DELETE) { // Equality or insertion.
				$chars2 += strlen( $diff[ $x ][ 1 ] );
			}
			if ($chars1 > $loc) { // Overshot the location.
				break;
			}
			$last_chars1 = $chars1;
			$last_chars2 = $chars2;
		}
		if (count( $diff ) != $x && $diff[ $x ][ 0 ] == $this->_DIFF_DELETE) { // The location was deleted.
			return $last_chars2;
		}
		// Add the remaining character length.
		return $last_chars2 + ($loc - $last_chars1);
	}
	
	
	public function diff_prettyHtml( &$diff ) {
	
		// Convert a diff array into a pretty HTML report.
		$this->diff_addIndex( $diff );
		$html = '';
		for ($x = 0; $x < count( $diff ); $x++) {
			$m = $diff[ $x ][ 0 ]; // Mode (delete, equal, insert)
			$t = $diff[ $x ][ 1 ]; // Text of change.
			$i = $diff[ $x ][ 2 ]; // Index of change.
			$t = str_replace( '&', '&amp;', $t );
			$t = str_replace( array( '<', '>' ), array( '&lt;', '&gt;' ), $t );
			$t = str_replace( "\n", '&para;<BR>', $t );
			if ($m == $this->_DIFF_DELETE) {
				$html .= "<DEL STYLE='background:#FFE6E6;' TITLE='i={$i}'>{$t}</DEL>";
			} else if ($m == $this->_DIFF_INSERT) {
				$html .= "<INS STYLE='background:#E6FFE6;' TITLE='i={$i}'>{$t}</INS>";
			} else {
				$html .= "<SPAN TITLE='i={$i}'>{$t}</SPAN>";
			}
		}
		return $html;
	}
	
}
	
?>
