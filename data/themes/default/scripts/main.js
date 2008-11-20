<!--
function setupUsername() {
	su_reset=0;
	if ($('#login_name').val()=='enter username') {
		su_login='';
		su_reset=1;
	}
	
	if ($('#login_name').val()=='') {
		su_login='enter username';
		su_reset=1;
	}
	if (su_reset==1) $('#login_name').val(su_login);
}

function setupPassword() {
	su_reset=0;
	if ($('#login_password').val()=='enter password') {
		su_login='';
		su_input='<input type="password" style="width:95px;height:12px;" id="login_password" name="password" onblur="setupPassword();" value="'+su_login+'">';
		setTimeout("$('#login_password').each(function(){this.focus()});", 10);
		su_reset=1;
	}
	
	if ($('#login_password').val()=='') {
		su_login='enter password';
		su_input='<input type="text" style="width:95px;height:12px;" id="login_password" name="password"  onfocus="setupPassword();" value="'+su_login+'">';
		su_reset=1;
	}
	if (su_reset==1) $('#login_field').html(su_input);
}

function fixPNG(img) {
	var src = img.src;
	img.style.width = img.width + "px";
	img.style.height = img.height + "px";
	img.style.filter = "progid:DXImageTransform.Microsoft.AlphaImageLoader(src='" + src + "', sizingMethod='scale')";
	img.src = shim;
};

function PNGsupport()
{
	var browser=navigator.appName;
	var b_version=navigator.appVersion;
	var version=parseFloat(b_version);
	if ((browser=="Microsoft Internet Explorer")&& (version<7))
	{
		return false;
	} else {
		return true;
	}

}
$(document).ready(function() {
	// Do something when page loads
});
 
//-->
