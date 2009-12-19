$.siteSetting.defaults.url = baseUrl + 'module/Mortar/jsSettings.json';

if(typeof($.ckeditor) == 'object')
{
	$.ckeditor.config.path = $.siteSetting('url').javascript + 'ckeditor/';
	window.CKEDITOR_BASEPATH = $.siteSetting('url').javascript + 'ckeditor/';
}