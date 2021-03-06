<?php

class MortarCoreLogInForm extends MortarFormForm
{
	protected function define()
	{
		$page = ActivePage::getInstance();
		$theme = $page->getTheme();

		$usernameHint = new ViewThemeTemplate($theme, 'support/usernameHint.html');
		$passwordHint = new ViewThemeTemplate($theme, 'support/passwordHint.html'); 

		$this->createInput('username')->
				setLabel('Username: ')->
				addRule('required')->
				setPosttext($usernameHint->getDisplay());

		$this->createInput('password')->
				setLabel('Password: ')->
				setType('password')->
				addRule('required')->
				setPosttext($passwordHint->getDisplay());
	}
}

?>