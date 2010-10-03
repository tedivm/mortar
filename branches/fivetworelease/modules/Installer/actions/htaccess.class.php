<?php

class InstallerActionhtaccess extends ActionBase
{

	public static $settings = array( 'Base' => array('headerTitle' => 'htaccess Settings') );

	protected $form = true;
	protected $error = array();
	protected $installed = false;
	protected $dbConnection;

	protected $ioHandler;

	public $subtitle = '';

	public function __construct($identifier, $handler)
	{
		$this->ioHandler = $handler;
	}

	public function start()
	{
		if(INSTALLMODE !== true)
			exit();

		$input = Input::getInput();
		$query = Query::getQuery();
		$config = Config::getInstance();



	}

	public function viewAdmin($page)
	{
		$theme = $page->getTheme();
		$jsMin = $theme->getMinifier('js');
		//$page->addScript($jsMin->getBaseString());
		$cssMin = $theme->getMinifier('css');
		$css = '<style type="text/css">' . $cssMin->getBaseString() . '</style>';
		$page->addHeaderContent($css);

		$name = substr($_SERVER['PHP_SELF'], 0, strpos($_SERVER['PHP_SELF'], DISPATCHER));
		$name = trim($name, '/');

		if(strlen($name) > 0)
		{
			$path = '/' . $name . '/';
		}else{
			$path = '/';
		}

		$htaccessContent = '
RewriteEngine On
RewriteBase ' . $path . '

# If the file is real, skip this rule
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*) index.php?p=$1 [QSA]

<Files ~ "\.sqlite$">
    Order allow,deny
    Deny from all
</Files>

<Files ~ "\.ini$">
    Order allow,deny
    Deny from all
</Files>

';

		$escapedHtaccess = htmlentities($htaccessContent);
		$form = '
<fieldset id="thing_section_main">
<textarea name="htaccess" id="thing_htaccess" rows="20" cols="140">' . $escapedHtaccess . '</textarea>
</fieldset>
';

		$output = '<p>If you are running multiple sites they need to have the same RewriteBase or you need to configure
			the virtual hosts file instead of the .htaccess one.</p>';
		$output .= $form;
		return $output;
	}

	public function checkAuth($action = NULL)
	{
		return true;
	}

}

?>