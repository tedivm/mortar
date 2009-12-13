(function($) {
	$.fn.slugify = function(obj) {
		$(this).data('obj', $(obj));
		$(this).keyup(function() {
			var obj = $(this).data('obj');
			var slug = $(this).val().replace(/\s+/g,'_').replace(/[^a-zA-Z0-9\_]/g,'');
			obj.val(slug);
		});
	}
})(jQuery);