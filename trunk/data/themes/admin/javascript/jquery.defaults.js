if(typeof($.validator) == 'function')
{	
	$.validator.setDefaults({

		errorPlacement: function(error, element) {
			element.prev("label").attr('title', error.text()).bt({cssClass:'applesauce'});
		},
		
		highlight: function(element, errorClass) {
			$(element.form).find("label[for=" + element.id + "]").addClass('formError');
		},
		
		unhighlight: function(element, errorClass) {
			$(element.form).find("label[for=" + element.id + "].formError").removeClass('formError').removeAttr('title').bt({remove: true});
		}
		
	});
}