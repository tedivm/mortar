(function($) {
	
	
  //
  // plugin definition
  $.fn.NAME = function(options) {
  // utilitiy definition
  // $.NAME = function(options) {
  	
    // build main options
    var opts = $.extend({}, $.fn.NAME.defaults, options);
   
    // iterate over each matched element
    // comment this out for utility functions
    return this.each(function() {
    	
      // build element specific options
      if($.metadata)
      {
      	meta = (opts.meta) : $(this).metadata()[opts.meta] : $(this).metadata();
      	 var opts = $.extend({}, opts, meta);
      }
          
 
    });
  };
 
  
  // private function for debugging
  
//  Example:
//  function functionName($obj) {
//    
//  };
 
  // define and expose our public functions

//  Example:
//  $.fn.NAME.functionName = function() {
//  $.NAME.functionName = function() {
//
//  };
 

  // plugin defaults
  $.fn.NAME.defaults = {};


})(jQuery);
