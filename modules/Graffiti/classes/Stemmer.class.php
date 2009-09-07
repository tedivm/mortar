<?php

class GraffitiStemmer
{
	static function stem($word, $language = 'English')
	{
		/*
			At the moment this class just routes to the GraffitiStemmerEnglish class. This is due to a) not having
			any other languages supported at the moment and b) the difficulty in running static functions prior to
			php 5.3. It is my hope that by the time we start supporting other languages we will also have committed to
			not supporting anything prior to php 5.3

			Using the staticHack function is not recommended for performance reasons.
		*/

		if($language != 'English')
			throw new CoreError('Unable to load stemmer for language ' . $language);

		return GraffitiStemmerEnglish::stem($word);
	}
}

?>