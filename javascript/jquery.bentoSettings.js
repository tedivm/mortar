(function($) {
	
	
  //
  // utilitiy definition
  
  $.bentoSettings = function(name, value) {
  	
  	var opts = $.extend({}, $.fn.bentoSettings.defaults);
  	if(name = 'options')
  	{
  		opts = $.extend({}, opts, options);
  	}
    
  	if(!isLoaded)
  	{
	  	$.bentoSettings.loadSettings(opts);
  	}
    switch(name)
    {
    	case 'reload':
    		$.bentoSettings.loadSettings(opts);
    	case 'options':
    		return;
    		break;
    		    	
    	default:
    		if(value)
    		{
    			siteInfo[name] = value;
    		}else{
    			return siteInfo[name];
    		}
    }
    
    
 
    });
  };
 
  //
  // private function for debugging
  //
//  Example:
//  function functionName($obj) {
//    
//  };
 
  //
  // define and expose our public functions
  //
//  Example:
//  $.fn.NAME.functionName = function() {
//
//  };
 
//  Example:
  $.bentoSettings.loadSettings = function(opts) {
	
  	$.getJson(opts.Url,
  		function(data){
  			siteInfo = $.extend({}, $.fn.bentoSettings.siteDefaults, data);
  		});
  		isLoaded = true;
  };

  // private
  var siteInfo = {};
  var isLoaded = false;

  //
  // plugin defaults
  //
  
  
  $.bentoSettings.defaults = {};
  $.bentoSettings.siteDefaults = {};

	
})(jQuery);
