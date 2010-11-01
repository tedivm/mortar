(function($) {
	$.fn.MortarFormDatepicker = function(options) {

		var opts = $.extend({}, $.fn.MortarFormDatepicker.defaults, options);

		return this.each(function() {

			$(this).find("input,textarea").each(function() {

				var inputOpts = $(this).metadata();

				if(inputOpts.datetime && inputOpts.datetime.data)
				{
					datetimeOpts = $.extend({},
						$.fn.MortarFormDatepicker.defaults.datetime,
						inputOpts.datetime.options);

					$(this).datetimepicker(datetimeOpts)
				}
			});
		});
	};

	$.fn.MortarFormDatepicker.defaults = {
	};

	$.fn.MortarFormDatepicker.defaults.datetime = {
		ampm: true,
		hourgrid: 4,
		minutegrid: 5
	};

})(jQuery);
