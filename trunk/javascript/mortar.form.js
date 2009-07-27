/*
 * Mortar Form Plugin
 */
(function($) {
	// plugin definition
	$.fn.MorterForm = function(options) {

		// build main options
		var opts = $.extend({}, $.fn.MorterForm.defaults, options);

		// iterate over each matched element
		// comment this out for utility functions
		return this.each(function() {

			// build element specific options
			if($.metadata)
			{
      	//meta = (opts.meta) : $(this).metadata()[opts.meta] : $(this).metadata();
      	 //var opts = $.extend({}, opts, meta);
			}

			$(this).validate({meta: opts.validationMetaClass});

			if(opts.validateOnLoad)
				$(this).valid();

			$(this).find("label").tooltip({extraClass: opts.tooltipClass});

			$(this).find("input,textarea").each(function() {

				var inputOpts = $(this).metadata();

				if(inputOpts.autosuggest && inputOpts.autosuggest.data)
				{
					data = (inputOpts.autosuggest.data)
					$(this).autosuggest(inputOpts.autosuggest.data, inputOpts.autosuggest.options)
				}

				if(inputOpts.html)
				{
					$(this).fck();
				}

			});
		});
	};

  // plugin defaults
  $.fn.MorterForm.defaults = {
  							tooltipClass:"formTip",
  							validationMetaClass:"validation"
  						};

})(jQuery);