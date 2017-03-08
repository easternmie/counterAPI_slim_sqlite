//$(document).ready(function() {
	var KEY = "";


	// var cloud = "http://cloudapi";
	// var cloud = "http://10.20.125.3";
	var cloud = "http://goswatch.myvnc.com";
	// var cloudAPI = "/watch@goscloud/v1";
	var cloudAPI = "";


	// var server = "http://localpi";
	// var server = "http://10.20.125.26";
	var server = "";
	// var localAPI = "/counterAPI_slim_sqlite/v1";
	var localAPI = "";
	



	function setCookie(c_name,c_value,exdays) {
		var exdate=new Date();
		exdate.setDate(exdate.getDate() + exdays);
		document.cookie=encodeURIComponent(c_name) 
		+ "=" + encodeURIComponent(c_value)
		+ (!exdays ? "" : "; expires="+exdate.toUTCString());
		;
	}

	function getCookie(cname) {
	    var name = cname + "=";
	    var ca = document.cookie.split(';');
	    for(var i = 0; i <ca.length; i++) {
	        var c = ca[i];
	        while (c.charAt(0)==' ') {
	            c = c.substring(1);
	        }
	        if (c.indexOf(name) == 0) {
	            return c.substring(name.length,c.length);
	        }
	    }
	    return "";
	}

	function deleteCookie(name) {
	  document.cookie = name +'=; Expires=Thu, 01 Jan 1970 00:00:01 GMT;';
	}

	function logOut(){
		//alert('process logout');
		deleteCookie("USER");
		deleteCookie("KEY");
    	deleteCookie("ID");
    	deleteCookie("LOC");
		window.location.replace('index.html');
	}


//});