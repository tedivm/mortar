(function($) {
 
	$.fn.MortarFormSlugify = function(name) {

		return this.each(function() {

			$(this).find("input").each(function() {

				var slug = $(this).attr("slugify");

				if(slug == 'yes')
				{
					$(this).slugify(name)
				}
			});
		});
	};
 
})(jQuery);