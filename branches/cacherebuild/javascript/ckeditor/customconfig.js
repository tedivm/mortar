/*
Copyright (c) 2003-2009, CKSource - Frederico Knabben. All rights reserved.
For licensing, see LICENSE.html or http://ckeditor.com/license
*/

CKEDITOR.editorConfig = function( config )
{
	config.baseHref = baseUrl;

	config.uiColor = '#A3C4E1';

	config.colorButton_enableMore = true;

	config.toolbar = 
	[
		['Source'],
		['Cut','Copy','Paste','PasteText','PasteFromWord','-','SpellChecker', 'Scayt'],
		['Undo','Redo','-','Find','Replace','-','RemoveFormat'],
		['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'],
		['Link','Unlink','Anchor'],
		['Image','Table','HorizontalRule','SpecialChar'],
		['Maximize', 'ShowBlocks'],
		'/',
		['Bold','Italic','Underline','Strike','-','Subscript','Superscript'],
		['NumberedList','BulletedList','-','Outdent','Indent','Blockquote'],
		['Styles','Format','Font','FontSize'],
		['TextColor','BGColor'],
		['About']
	];

	config.contentsCss = baseUrl + 'module/Mortar/FontLookUp.css';
	config.customConfig = baseUrl + 'module/Mortar/FontLookUp.js';
};