<?php

class GraffitiPluginFormInputTagToHtml extends MortarCorePluginFormInputUserToHtml
{
	protected function runCheck(MortarFormInput $input)
	{
		if($input->type != 'tag')
			return false;

		return true;
	}

	public function setInput(MortarFormInput $input)
	{
		parent::setInput($input);
		$input->property('autocomplete', false);
	}

	protected function getUrl(MortarFormInput $input)
	{
		$url = new Url();
		$url->module = PackageInfo::loadByName(null, 'Graffiti');
		$url->format = 'json';
		$url->action = 'TagList';

		return $url;
	}

	protected function getString($id, $baseString)
	{
		GraffitiTagLookUp::getTagFromId($id);

		if($tag = GraffitiTagLookUp::getTagFromId($id))
			$baseString .= $tag . ', ';

		return $baseString;
	}

	public function getCustomJavaScript()
	{
		$id = $this->input->property('id');
		$url = $this->getUrl($this->input);

		$code = <<<CODE
	$(function() {
		function split( val ) {
			return val.split( /,\s*/ );
		}
		function extractLast( term ) {
			return split( term ).pop();
		}

		$( "#$id" ).autocomplete({
			source: function( request, response ) {
				$.getJSON( "$url", {
					term: extractLast( request.term )
				}, response );
			},
			search: function() {
				var term = extractLast( this.value );
				if ( term.length < 1 ) {
					return false;
				}
			},
			focus: function() {
				return false;
			},
			select: function( event, ui ) {
				var terms = split( this.value );
				terms.pop();
				terms.push( ui.item.value );
				terms.push( "" );
				this.value = terms.join( ", " );
				return false;
			}
		});
	});
CODE;
		return array($code);
	}
}

?>