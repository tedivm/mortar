<?php

class BentoBaseActionReadDirectory extends ModelActionRead
{
	public function viewHtml()
	{
		if(is_numeric($this->model->defaultChild))
		{
			$location = new Location($this->model->defaultChild);

			$url = new Url();
			$url->locationId = $this->model->defaultChild;



		}elseif($this->model->indexListing){

		}else{

		}
	}
}

?>