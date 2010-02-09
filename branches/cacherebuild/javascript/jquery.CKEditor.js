/*
 ### jQuery CKEditor Plugin v0.1 - 2009-11-24 ###
 * http://www.fyneworks.com/ - diego@fyneworks.com
 * Dual licensed under the MIT and GPL licenses:
 *   http://www.opensource.org/licenses/mit-license.php
 *   http://www.gnu.org/licenses/gpl.html
 ###
 Project: http://jquery.com/plugins/project/CKEditor/
 Website: http://www.fyneworks.com/jquery/CKEditor/
*/
/*
 USAGE: $('textarea').ckeditor({ path:'/path/to/ckeditor/editor/' }); // initialize CKEditor
 ADVANCED USAGE: $.ckeditor.update(); // update value in textareas of each CKEditor instance
*/

/*# AVOID COLLISIONS #*/
;if(window.jQuery) (function($){
/*# AVOID COLLISIONS #*/

$.extend($, {
 ckeditor:{
  waitFor: 10,// in seconds, how long should we wait for the script to load?
  config: { }, // default configuration
  path: '/CKEditor/', // default path to CKEditor directory
  selector: 'textarea.ckeditor', // jQuery selector for automatic replacements
		editors: [], // array of element ids pointing to CKEditor instances
  loaded: false, // flag indicating whether CKEditor script is loaded
  intercepted: null, // variable to store intercepted method(s)
  
  // utility method to load instance of CKEditor
  instance: function(i){
			var x = CKEDITOR.instances[i];
			//LOG(['ckeditor.instance','x',x]);
			// Look for textare with matching name for backward compatibility
			if(!x){
				x = $('#'+i.replace(/\./gi,'\\\.')+'')[0];
				//LOG(['ckeditor.instance','ele',x]);
				if(x) x = CKEDITOR.instances[x.id];
			};
			//LOG(['ckeditor.instance',i,x]);
			return x;
		},
		
  // utility method to read contents of CKEditor
  content: function(i, v){
			//LOG(['ckeditor.content',arguments]);
			var x = this.instance(i);
			if(!x){
				alert('CKEditor instance "'+i+'" could not be found!');
				return '';
			};
			if(v!=undefined){
 			//LOG(['ckeditor.content',x,'x.setData',v]);
				x.setData(v);
			};
			//LOG(['ckeditor.content','getData',x.getData(true)]);
   return x.getData(true);
  }, // ckeditor.content function
  
  // inspired by Sebastián Barrozo <sbarrozo@b-soft.com.ar>
  setHTML: function(i, v){
			//LOG(['ckeditor.setHTML',arguments]);
   if(typeof i=='object'){
    v = i.html;
    i = i.name || i.instance;
   };
   return $.ckeditor.content(i, v);
  },
  
  // utility method to update textarea contents before ajax submission
  update: function(){
			// Remove old non-existing editors from memory
			$.ckeditor.clean();
			// loop through editors
			for(var name in CKEDITOR.instances){
 			//LOG(['ckeditor.update',name,CKEDITOR.instances[name]]);
				var data = this.content(name);
				var area = $('#'+name);
 			//LOG(['ckeditor.update','-->',area,data]);
				area.text( data );
			};
   //LOG(['ckeditor.update','done']);
  }, // ckeditor.update
  
  // utility method to non-existing instances from memory
  clean: function(){
			//LOG(['ckeditor.clean','before',CKEDITOR.instances]);
			for(var name in CKEDITOR.instances){
 			//LOG(['ckeditor.update',name,CKEDITOR.instances[name]]);
				if($('#'+name).length==0)
				 delete CKEDITOR.instances[name];
			};
			//LOG(['ckeditor.clean','after',CKEDITOR.instances]);
  }, // ckeditor.clean
  
  // utility method to create instances of CKEditor (if any)
  create: function(options){
			// Create a new options object
   var o = $.extend({}/* new object */, $.ckeditor.config || {}, options || {});
   // Normalize plugin options
   $.extend(o, {
    selector: o.selector || $.ckeditor.selector,
    basePath: o.path || o.basePath || (window.CKEDITOR_BASEPATH ? CKEDITOR_BASEPATH : $.ckeditor.path)
   });
   // Find ckeditor.editor-instance 'wannabes'
   var e = o.e ? $(o.e) : undefined;
   if(!e || !e.length>0) e = $(o.selector);
   if(!e || !e.length>0) return;
   // Load script and create instances
   if(!$.ckeditor.loading && !$.ckeditor.loaded){
    $.ckeditor.loading = true;
    $.getScript(
     o.basePath+'ckeditor.js',
     function(){ $.ckeditor.loaded = true; }
    );
   };
   // Start editor
   var start = function(){//e){
    if($.ckeditor.loaded){
     //LOG(['ckeditor.create','start',e,o]);
     $.ckeditor.editor(e,o);
    }
    else{
     //LOG(['ckeditor.create','waiting for script...',e,o]);
     if($.ckeditor.waited<=0){
      alert('jQuery.CKEditor plugin error: The CKEditor script did not load.');
     }
     else{
      $.ckeditor.waitFor--;
      window.setTimeout(start,1000);
     };
    }
   };
   start(e);
   // Return matched elements...
   return e;
  },
  
  // utility method to integrate this plugin with others...
  intercept: function(){
   if($.ckeditor.intercepted) return;
   // This method intercepts other known methods which
   // require up-to-date code from CKEditor
   $.ckeditor.intercepted = {
    ajaxSubmit: $.fn.ajaxSubmit || function(){}
   };
   $.fn.ajaxSubmit = function(){
				//LOG(['ckeditor.intercepted','$.fn.ajaxSubmit',CKEDITOR.instances]);
    $.ckeditor.update(); // update html
    return $.ckeditor.intercepted.ajaxSubmit.apply( this, arguments );
   };
			// Also attach to conventional form submission
			//$('form').submit(function(){
   // $.ckeditor.update(); // update html
   //});
  },
  
  // utility method to create an instance of CKEditor
  editor: function(e /* elements */, o /* options */){
   // Create a local over-loaded copy of the default configuration
			o = $.extend({}, $.ckeditor.config || {}, o || {});
   // Make sure we have a jQuery object
   e = $(e);
   //LOG(['ckeditor.editor','E',e,o]);
   if(e.size()>0){
    // Go through objects and initialize ckeditor.editor
    e.each(
     function(i,t){
						if((t.tagName||'').toLowerCase()!='textarea')
							return alert(['An invalid parameter has been passed to the $.CKEditor.editor function','tagName:'+t.tagName,'name:'+t.name,'id:'+t.id].join('\n'));
      
      var T = $(t);// t = element, T = jQuery
      if(!t.ckeditor/* not already installed */){
							// make sure the element has an id
							t.id = t.id || 'ckeditor'+($.ckeditor.editors.length+1);
							$.ckeditor.editors[$.ckeditor.editors.length] = t.id;
							// make sure the element has a name
							t.name = t.name || t.id;
       //LOG(['ckeditor.editor','metadata',T.metadata()]);
							// Accept settings from metadata plugin
							var config = $.extend({}, o,
								($.meta ? T.data()/*NEW metadata plugin*/ :
								($.metadata ? T.metadata()/*OLD metadata plugin*/ : 
								null/*metadata plugin not available*/)) || {}
							);
							// normalize configuration one last time...
							config = $.extend(config, {
								width: (o.width || o.Width || T.width() || '100%'),
								height: (o.height || o.Height || T.height() || '500px'),
								basePath: (o.path || o.basePath),
								toolbar: (o.toolbar || o.ToolbarSet || undefined)// 'Default')
							});
       //LOG(['ckeditor.editor','make','t',t]);
       //LOG(['ckeditor.editor','make','t.id',t.id]);
       //LOG(['ckeditor.editor','make','config',config]);
							// create CKEditor instance
       var editor = CKEDITOR.replace(t.id, config);
							// Store reference to element in CKEditor object
       editor.textarea = T;
							// Store reference to CKEditor object in element
       t.ckeditor = editor;
      };
     }
    );
				// Remove old non-existing editors from memory
				$.ckeditor.clean();
   };
   // return jQuery array of elements
   return e;
  }, // ckeditor.editor function
  
  // start-up method
  start: function(o/* options */){
   // Attach itself to known plugins...
			$.ckeditor.intercept();
			// Create CKEDITOR
   return $.ckeditor.create(o);
  } // ckeditor.start
  
 } // ckeditor object
 //##############################
 
});
// extend $
//##############################


$.extend($.fn, {
 ckeditor: function(o){
  
		if(this.length==1 && this[0].id && window.CKEDITOR && CKEDITOR.instances[this[0].id]!=undefined)
			return CKEDITOR.instances[this[0].id];
		
		return $(this).each(function(){
   $.ckeditor.start(
				$.extend(
					{}, // create a new options object
					o || {}, // overload with this call's options parameter
					{e: this} // store reference to self
				) // $.extend
			); // $.ckeditor.start
  }); // each element
		
 } //$.fn.ckeditor
});
// extend $.fn
//##############################

/*# AVOID COLLISIONS #*/
})(jQuery);
/*# AVOID COLLISIONS #*/
