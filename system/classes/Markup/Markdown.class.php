<?php
class MarkupMarkdown extends MarkupHtml
{
	public function markupText($text)
	{
		$md = new Markdown_Parser();
		return $md->transform($text);
	}
}
?>