a:2:{s:4:"time";d:1227080053.24324893951416015625;s:4:"data";s:2932:"<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>

	<meta http-equiv="content-type" content="text/html; charset=iso-8859-1" />

<link href='{# theme_path #}css/all.css' rel='stylesheet' type='text/css'/>



	<title>{# title #}<title>
	
	{# meta #}
	
	{# css #}
	
	{# jsIncludes #}
	
	<script type="text/javascript">
	
		$(function(){
		
			{# scriptStartup #}
			
		});	
			
		{# script #}
		
	</script>


<script>

$(function(){
	
	
// left column animation	
	$('#left-column > div > h3').click(function (event) {
		var $target = $(event.target);
		if( $target.is("h3") ) 
		{
			$navfields = $target.parent().children('ul.nav');
			if($navfields.is(':hidden'))
			{
				$navfields.show("slide", {direction: "up"}, 850);
			}else{
				$navfields.hide("slide", {direction: "up"}, 650);
			}
		}
	});
/*
	$('#left-column a').click(function (event) {
		var $target = $(event.target);
		
		$.getJSON(url, 
			function (data)
				{
					$('#center-column').html(data.content);
				}
			);
		
		
		
	//	$target.hide("pulsate").show("slide");
	});
	
//tab click	
	$('#top-navigation li').click(function (event) {
		var target = $(this);
		if( target.is("li") && !target.hasClass("active") ) 
		{
			// make clicked tag active, all other tabs inactive
			target.siblings('li').removeClass('active').end().addClass('active');
			
			// load new sidebar
			// blind clip drop explode fold puff slide scale pulsate
			$('#left-column').hide("slide", {direction: "left"}, 650).show("slide", {direction: "left"}, 650);
			
			//load new center column
			$('#center-column').hide("slide", 
									{direction: "down"},
									650, 
										function(){ 
											$(this).html('New Center').
												show("slide", {direction: "down"}, 650);
										}); //.show("slide", {direction: "down"}, 650);
			
			// add events to sidebar
			$('#left-column a').click(function (event) {
				var $target = $(event.target);
				$target.hide("pulsate").show("pulsate");
			});
			
			// load default actions

			
		}

	});	
	
*/	
	
	
	
});
  



</script>

</head>
<body>
<div id="main">

	<div id="header">
	
		<!-- logo -->
		<a href="index.html" class="logo"><img src="{# theme_path #}img/logo.gif" width="101" height="29" alt="" /></a>
		
		{# navtabs #}
		
		<!-- tabs -->

		<!-- tabs -->

	</div> <!-- #main -->

	
	<div id="middle">
	
	{# navbar #}	
	
		
		
		<div id="center-column">
			{# content #}
		</div>
		
		<!--
		<div id="right-column">
			<strong class="h">INFO</strong>
			<div class="box">Detect and eliminate viruses and Trojan horses, even new and unknown ones. Detect and eliminate viruses and Trojan horses, even new and </div>
	  </div>
	  -->
	</div>
	<div id="footer"></div>
</div>


</body>
</html>
";}