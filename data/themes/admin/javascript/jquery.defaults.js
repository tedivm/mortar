if(typeof($.validator) == 'function')
{	
	

	
	$.validator.setDefaults({

		errorPlacement: function(error, element) {
			
			element.prev("label").attr('title', error.text()); //.cluetip({attribute : 'rel', splitTitle: '|', showtitle:false});
			
			
			//error.appendTo(element.prev("label")).hide();
			
		},
		
		highlight: function(element, errorClass) {
			$(element.form).find("label[for=" + element.id + "]").addClass('formError');
		},
		
		unhighlight: function(element, errorClass) {
			$(element.form).find("label[for=" + element.id + "].formError").removeClass('formError').removeAttr('title'); //.bt({remove: true});
		}
		
	});
}