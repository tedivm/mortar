<?

class TesseraActionThreadRead extends TesseraActionForumRead
{
	protected $listOptions = array('browseBy' => 'creationDate', 'order' => 'ASC');

	public function viewHtml($page)
	{
		$query = Query::getQuery();
		$url = new Url();
		$url->location = $query['location'];
		$url->format = $query['format'];
		$url->action = 'PostReply';

		$view = parent::viewHtml($page);
		$form = new TesseraPostReplyForm('post-reply', $this->model);
		$form->setAction($url);
		return $view . $form->getFormAs('Html');
	}
}

?>