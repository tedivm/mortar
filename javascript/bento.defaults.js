$.siteSetting.defaults.url = baseUrl + 'index.php?engine=Json&action=jsSettings&package=BentoBase';

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