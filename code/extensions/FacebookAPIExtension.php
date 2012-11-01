<?php

class FacebookAPIExtension extends Extension {

	public function onAfterInit() {
		Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.min.js');
		Requirements::javascript('//connect.facebook.net/en_US/all.js');

		$this->owner->initFacebook();
	}

	/**
	 * Implement in Page_Controller to override this function
	 * @return type 
	 */
	public function initFacebook() {

		// Back end initialisation
		$facebook = FacebookAPI::get();
		if (empty($facebook)) return;
		
		$this->owner->extend('onBeforeInitFacebook', $facebook);

		// Frontend initialisation
		$appID = $facebook->getAppId();
		Requirements::customScript("FB.init({
			appId  : '$appID',
			status : true, // check login status
			cookie : true, // enable cookies to allow the server to access the session
			xfbml  : true  // parse XFBML
		});
		FB.Canvas.setAutoGrow(); //Resizes the iframe to fit content");

		$this->checkFacebookPermissions();
		
		$this->owner->extend('onAfterInitFacebook', $facebook);
	}
	
	/**
	 * Determine if the user is properly authorised, and uses javascript to redirect
	 * the user if not
	 * @return boolean Flag indicating whether the user is properly authenticated 
	 */
	public function checkFacebookPermissions() {
		// session initialisation
		if (!($user = FacebookAPI::get()->getUser())) {
			// Redirect to login
			$loginUrl = $this->getFacebookLoginURL();
			Requirements::customScript("top.location.href = '$loginUrl';", 'FacebookLogin');
			return false;
		} else {
			return true;
		}
	}
	
	public function getFacebookLoginURL() {
		$facebook = FacebookAPI::get();
		return $facebook->getLoginUrl(
			array('scope' => FacebookAPI::get_permissions())
		);
	}

}