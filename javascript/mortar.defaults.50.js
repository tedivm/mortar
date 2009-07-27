$.siteSetting.defaults.url = baseUrl + 'index.php?format=json&action=jsSettings&module=Mortar';

if(typeof($.fck) == 'object')
{
	$.fck.config.path = $.siteSetting('url').javascript + 'fckeditor/';
}