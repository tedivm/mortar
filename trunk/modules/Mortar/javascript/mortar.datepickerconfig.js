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

					$(this).datepicker(datetimeOpts)
				}
			});
		});
	};

	$.fn.MortarFormDatepicker.defaults = {
	};

	$.fn.MortarFormDatepicker.defaults.datetime = {
					duration: '',
					showTime: true,
					constrainInput: true,
					stepMinutes: 1,
					stepHours: 1,
					altTimeField: '',
					currentText: 'Now',
					prevText: '<',
					nextText: '>',	
					time24h: false
	};

})(jQuery);
