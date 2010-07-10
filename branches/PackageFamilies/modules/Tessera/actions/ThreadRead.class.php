<?

class TesseraActionThreadRead extends TesseraActionForumRead
{
	protected $listOptions = array('browseBy' => 'creationDate', 'order' => 'ASC');

	public function viewHtml($page)
	{
		$view = parent::viewHtml($page);

		$loc = $this->model->getLocation();

		if($loc->getStatus() === 'Open') {
			$query = Query::getQuery();
			$url = new Url();
			$url->location = $query['location'];
			$url->format = $query['format'];
			$url->action = 'PostReply';

			$form = new TesseraPostReplyForm('post-reply', $this->model);
			$form->setAction($url);
			$view .= $form->getFormAs('Html');
		}
		return $view;
	}
}

?>