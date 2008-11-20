<?php
/*
License and Disclaimer:
Copyright 2003 Chris Snyder. All rights reserved.

Redistribution and use in source and binary forms, with or without modification, 
are permitted provided that the following conditions are met:

1. Redistributions of source code must retain the above copyright notice, this 
   list of conditions and the following disclaimer.
2. Redistributions in binary form must reproduce the above copyright notice, this 
   list of conditions and the following disclaimer in the documentation and/or other 
   materials provided with the distribution.

THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND
FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
AUTHOR OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;  LOSS OF USE, DATA, OR PROFITS;
OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY,
WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR
OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF
ADVISED OF THE POSSIBILITY OF SUCH DAMAGE. 
*/


class XSS
{
	public $allowed_tags = array ( "p"=>1, "br"=>0, "a"=>1, "img"=>0, 
						"li"=>1, "ol"=>1, "ul"=>1, 
						"b"=>1, "i"=>1, "em"=>1, "strong"=>1, 
						"del"=>1, "ins"=>1, "u"=>1, "code"=>1, "pre"=>1, 
						"blockquote"=>1, "hr"=>0);
	                   
						

						
	public function filter($input)
	{
		return $this->safe_html($input);
	}
						
	/**
	 * Add a tag to the allowed list for html fields
	 *
	 * @param string $tag
	 * @param bool $needs_close
	 */						     
	public function add_allowed_tag($tag, $needs_close = true)
	{
		$allowed_tags['tag'] = $needs_close ? 1 : 0;
	}                        
	
	/**
	 * first, an HTML attribute stripping function used by safe_html()
	 * after stripping attributes, this function does a second pass
	 * to ensure that the stripping operation didn't create an attack
	 * vector.
	 *
	 * @author Chris Snyder
	 * @copyright 2003 Chris Snyder (snyder@chxo.com)
	 * @license http://www.freebsd.org/copyright/freebsd-license.html FreeBSD-Style License
	 * @param string $html
	 * @param array $attrs
	 * @return string
	 */
	private function strip_attributes($html, $attrs) 
	{
	
		if (!is_array($attrs)) {
			$array= array( "$attrs" );
			unset($attrs);
			$attrs= $array;
		}
	  
		foreach ($attrs AS $attribute) {
			// once for ", once for ', s makes the dot match linebreaks, too.
			$search[]= '/' . $attribute . '\s*=\s*".+"/Uis';
			$search[]= '/' . $attribute . '\s*=\s*\'.+\'/Uis';
			// and once more for unquoted attributes
			$search[]= '/'.$attribute.'\s*=\s*\S+/i';
		}
		$html= preg_replace($search, "", $html);
	

		// do another pass and strip_tags() if matches are still found
		foreach ($search AS $pattern) {
			if (preg_match($pattern, $html)) {
				$html= strip_tags($html);
				break;
			}
		}
	
		return $html;
	}
	
	/**
	 *  checks for javascript and encoded entites
	 *
	 * @author Chris Snyder
	 * @copyright 2003 Chris Snyder (snyder@chxo.com)
	 * @license http://www.freebsd.org/copyright/freebsd-license.html FreeBSD-Style License
	 * @param string $html
	 * @return bool
	 */
	private function js_and_entity_check($html) 
	{
		// anything with ="javascript: is right out -- strip all tags if found
		$pattern= "/=[\S\s]*s\s*c\s*r\s*i\s*p\s*t\s*:\s*\S+/Ui";
		if (preg_match($pattern, $html)) {
			return TRUE;
		}
	  
		// anything with encoded entites inside of tags is out, too
		$pattern= "/<[\S\s]*&#[x0-9]*[\S\s]*>/Ui";
		if (preg_match($pattern, $html)) {
			return TRUE;
		}
	  
		return FALSE;
	}
	
	/**
	 * Returns a string that has been cleansed of potential XSS attempts but does
	 * allow for specified html
	 *
	 * @author Chris Snyder
	 * @copyright 2003 Chris Snyder (snyder@chxo.com)
	 * @license http://www.freebsd.org/copyright/freebsd-license.html FreeBSD-Style License
	 * @param string $html
	 * @return string
	 */
	private function safe_html($html) 
	{
		
		// check for obvious oh-noes
		if ( $this->js_and_entity_check( $html ) ) {
			$html= strip_tags($html);
			return $html;
		}
	  
		// there's some debate about this.. is strip_tags() better than rolling your own regex?
		$stripallowed= "";
		foreach ($this->allowed_tags AS $tag=>$closeit) {
			$stripallowed .= "<$tag>";
		}
	
		//print "Stripallowed: $stripallowed -- ".print_r($allowedtags,1);
		$html= strip_tags($html, $stripallowed);
	
		// also, lets get rid of some pesky attributes that may be set on the remaining tags...
		// this should be changed to keep_attributes($htmlm $goodattrs), or perhaps even better keep_attributes
		//  should be run first. then strip_attributes, if it finds any of those, should cause safe_html to strip all tags.
		$badattrs= array("on\w+", "style", "fs\w+", "seek\w+");
		$html= $this->strip_attributes($html, $badattrs);
	
		// close html tags if necessary -- note that this WON'T be graceful formatting-wise, it just has to fix any maliciousness
		foreach ($this->allowed_tags AS $tag=>$closeit) {
			if (!$closeit) continue;
			$patternopen= "/<$tag\b[^>]*>/Ui";
			$patternclose= "/<\/$tag\b[^>]*>/Ui";
			$totalopen= preg_match_all ( $patternopen, $html, $matches );
			$totalclose= preg_match_all ( $patternclose, $html, $matches2 );
			if ($totalopen>$totalclose) {
				$html.= str_repeat('</' . $tag . '>', ($totalopen - $totalclose));
			}
		}
	  
		// check (again!) for obvious oh-noes that might have been caused by tag stipping
		if ( $this->js_and_entity_check( $html ) ) {
			$html= strip_tags($html)."<!--xss stripped after processing-->";
			return $html;
		}
	
		// close any open <!--'s
		$html.= '<!-- -->';
	
		return $html;
	}
	

}


?>