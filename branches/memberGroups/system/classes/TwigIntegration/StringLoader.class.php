<?php

class TwigIntegrationStringLoader extends Twig_Loader_String
{
	public function isFresh($name, $time)
	{
		return true;
	}

}
?>