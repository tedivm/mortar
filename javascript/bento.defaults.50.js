$.siteSetting.defaults.url = baseUrl + 'index.php?format=json&action=jsSettings&module=BentoBase';

if(typeof($.validator) == 'function')
{
	$.validator.setDefaults({
		meta: "validation",

		onsubmit: true

	});
}

if(typeof($.fck) == 'object')
{
	$.fck.config.path = $.siteSetting('url').javascript + 'fckeditor/';
}