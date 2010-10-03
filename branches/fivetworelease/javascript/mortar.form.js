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

				if(inputOpts.autocomplete && inputOpts.autocomplete.data)
				{
					autocompleteOpts = $.extend({},
												$.fn.MorterForm.defaults.autocomplete,
												inputOpts.autocomplete.options);

					$(this).autocomplete(inputOpts.autocomplete.data, autocompleteOpts)
				}


				// Setup WYSIWYG editor
				if(inputOpts.html)
				{
					htmlbox = $(this);
					$('#' + $(this).attr('id') + '_label').remove();
					$(this).ckeditor( {customConfig : $.ckeditor.config.path + 'customconfig.js'} );
					setTimeout(function() { htmlbox.insertAfter($('#cke_' + htmlbox.attr('id'))); }, 4000);
				}
			});

			$(".mf-toggle-hide > legend").toggleControl(
				".mf-toggle-hide > div", {speed: 'fast', 
							openClass: 'mf-toggle-open', 
							closeClass: 'mf-toggle-close' });
			$(".mf-toggle-show > legend").toggleControl(
				".mf-toggle-show > div", {speed: 'fast', 
							hide: false,
							openClass: 'mf-toggle-open', 
							closeClass: 'mf-toggle-close' });

			$("input").click(function() {
				$.ckeditor.update();
			});


		});
	};

  // plugin defaults
	$.fn.MorterForm.defaults = {
  							tooltipClass:"formTip",
  							validationMetaClass:"validation"
  						};

	$.fn.MorterForm.defaults.autocomplete = {
					dataType:"json",
					cacheLength:10,
					minChars:0,
					formatItem:function(data,i,max,value,term){ return value; },
					parse: function parse(data) {
									var parsed = [];
									$.each(data, function(i, val){
										parsed[parsed.length] = {
											data: val.id,
											value: val.name,
											result: val.name
										};
									});
									return parsed;
								}
  						};

})(jQuery);