(function($) {

var siteInfo;

	$.siteSetting = function(name) {
		{
			if(!isLoaded)
			{
				$.ajax({
					url: $.siteSetting.defaults.url,
					dataType:"json",
					async: false,
					success: function(json){
						siteInfo = json;
						isLoaded = true;
					}
			 	});				
			}
		}
		return siteInfo[name];
    };

  	$.siteSetting.defaults = {"url":""};
  	// private
	var isLoaded = false;
	var siteInfo;
  
})(jQuery);
