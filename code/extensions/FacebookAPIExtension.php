<?php

class FacebookAPIExtension extends Extension {
	
	public function onAfterInit() {
		Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.min.js');
		Requirements::javascript('//connect.facebook.net/en_US/all.js');
		
		// Load app ID
		$appID = SiteConfig::current_site_config()->FacebookApplicationID;
		Requirements::customScript("FB.init({
			appId  : '$appID',
			status : true, // check login status
			cookie : true, // enable cookies to allow the server to access the session
			xfbml  : true  // parse XFBML
		});");
	}
	
	public function Facebook() {
		return SiteConfig::current_site_config()->Facebook();
	}
}