<?php
/**
 * FCKeditor - This is the integration file for PHP 5.
 *
 * It defines the FCKeditor class that can be used to create editor
 * instances in PHP pages on server side. This is the
 *
 * @author		Frederico Caldeira Knabben
 * @copyright	Copyright (C) 2003-2007 Frederico Caldeira Knabben
 * @license		http://www.mozilla.org/MPL/MPL-1.1.html, http://www.gnu.org/licenses/gpl.html, http://www.gnu.org/licenses/lgpl.html
 * @link		http://www.fckeditor.net
 * @subpackage	Library
 * @category	Form
 */

/**
 * FKCEditor Class
 *
 * Originally from the FCKeditor package, modified to better fit Mortar
 *
 * @subpackage	Library
 * @category	Form
 * @author		Frederico Caldeira Knabben, Robert Hafner
 * @link		http://www.fckeditor.net
 */
class FCKeditor
{

	protected $InstanceName ;
	public $BasePath ;
	public $Width ;
	public $Height ;
	public $ToolbarSet ;
	public $Value ;
	public $Config = array();
	public $id;

	// PHP 5 Constructor (by Marcus Bointon <coolbru@users.sourceforge.net>)

	/**
	 * The constructor takes a name as its argument.
	 *
	 * @param string $instanceName
	 */
	public function __construct( $instanceName )
 	{
 		$config = Config::getInstance();
		$this->InstanceName	= $instanceName ;
		$this->BasePath		= $config['domain'] . 'system/external/fckeditor/' ;
		$this->Width		= '100%' ;
		$this->Height		= '600' ;
		$this->ToolbarSet	= 'Default' ;
		$this->Value		= '' ;
		$this->id			= '' ;
	}

	/**
	 * This function returns the html needed to make the fckeditor.
	 *
	 * @return string
	 */
	public function CreateHtml()
	{
		$HtmlValue = htmlspecialchars( $this->Value ) ;

		$Html = '<div>' ;

		if ( $this->IsCompatible() )
		{

			$File = ($_GET['fcksource'] == true) ? 'fckeditor.original.html' : 'fckeditor.html';

			$Link = $this->BasePath . 'editor/' .$File . '?InstanceName=' . $this->InstanceName ;

			if ( $this->ToolbarSet != '' )
				$Link .= '&amp;Toolbar=' . $this->ToolbarSet;

			if ( $this->id == '' )
				$this->id = $this->InstanceName . '_fck';

			// Render the linked hidden field.
			$Html .= '<input type="hidden" id="' . $this->id . '" name="' . $this->InstanceName . '" value="' . $HtmlValue . '" style="display:none" />' ;

			// Render the configurations hidden field.
			$Html .= '<input type="hidden" id="'. $this->id .'___Config" value="' . $this->GetConfigFieldString() . '" style="display:none" />' ;

			// Render the editor IFRAME.
			$Html .= '<iframe id="'. $this->id .'___Frame" src="' . $Link . '" width="' . $this->Width .'" height="'. $this->Height .'" frameborder="0" scrolling="no"></iframe>' ;
		}
		else
		{

			$WidthCSS = ( strpos( $this->Width, '%' ) === false ) ? $this->Width . 'px' : $this->Width ;
			$HeightCSS = ( strpos( $this->Height, '%' ) === false ) ?  $this->Height . 'px' : $this->Height ;

			$Html .= '<textarea name="' . $this->InstanceName . '" rows="4" cols="40" style="width:' . $WidthCSS . '; height: ' . $HeightCSS . '\">' . $HtmlValue . '</textarea>' ;
		}

		$Html .= '</div>' ;

		return $Html ;
	}

	protected function IsCompatible()
	{
		$sAgent = $_SERVER['HTTP_USER_AGENT'] ;

		if ( strpos($sAgent, 'MSIE') !== false && strpos($sAgent, 'mac') === false && strpos($sAgent, 'Opera') === false )
		{
			$iVersion = (float)substr($sAgent, strpos($sAgent, 'MSIE') + 5, 3) ;
			return ($iVersion >= 5.5) ;
		}
		else if ( strpos($sAgent, 'Gecko/') !== false )
		{
			$iVersion = (int)substr($sAgent, strpos($sAgent, 'Gecko/') + 6, 8) ;
			return ($iVersion >= 20030210) ;
		}
		else if ( strpos($sAgent, 'Opera/') !== false )
		{
			$fVersion = (float)substr($sAgent, strpos($sAgent, 'Opera/') + 6, 4) ;
			return ($fVersion >= 9.5) ;
		}
		else if ( preg_match( "|AppleWebKit/(\d+)|i", $sAgent, $matches ) )
		{
			$iVersion = $matches[1] ;
			return ( $matches[1] >= 522 ) ;
		}
		else
			return false ;
	}

	protected function GetConfigFieldString()
	{
		$sParams = '' ;

		foreach ( $this->Config as $sKey => $sValue )
		{
			if ( $sParams != '' )
				$sParams .= '&amp;' ;


			$sParams .= urlencode($sKey );

			if ( $sValue === true )
			{
				$sParams .= '=true' ;
			}elseif ( $sValue === false ){
				$sParams .= '=false' ;
			}else{
				$sParams .= '=' . urlencode( $sValue ) ;
			}
		}

		return $sParams ;
	}
}

?>