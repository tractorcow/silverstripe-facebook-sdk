<?php

class FacebookAPIExtension extends Extension {

	public function onAfterInit() {
		Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.min.js');
		Requirements::javascript('//connect.facebook.net/en_US/all.js');
	}
	
	public function onBeforeInit() {
		$this->owner->initFacebook();
	}

	/**
	 * Implement in Page_Controller to override this function
	 * @return type 
	 */
	public function initFacebook() {
		
		$this->owner->extend('onBeforeInitFacebook');

		// Back end initialisation
		$facebook = FacebookAPI::get();
		if (empty($facebook)) return;

		// Frontend initialisation
		$appID = $facebook->getAppId();
		Requirements::customScript("FB.init({
			appId  : '$appID',
			status : true, // check login status
			cookie : true, // enable cookies to allow the server to access the session
			xfbml  : true  // parse XFBML
		});
		FB.Canvas.setAutoGrow(); //Resizes the iframe to fit content");

		// Initialise the user
		if($this->owner->requiresFacebookLogin()) {
			$this->owner->authenticateFacebookLogin();
		}
		
		$this->owner->extend('onAfterInitFacebook', $facebook);
	}
	
	/**
	 * Determines if the current page requires a valid facebook login with the
	 * configured permissions in order to view.
	 * Implement this in your page class in order to override, or change the configuration
	 * @return boolean
	 */
	public function requiresFacebookLogin() {
		return FacebookAPI::get_requires_login();
	}
	
	/**
	 * Determine if the user is properly authorised, and uses javascript to redirect
	 * the user if not
	 * @return boolean Flag indicating whether the user is properly authenticated 
	 */
	public function authenticateFacebookLogin() {
		
		// Session initialisation
		if (FacebookAPI::get()->getUser()) return true;
		
		// // Redirect to login
		$loginUrl = $this->owner->getFacebookLoginURL();
		$this->owner->redirectClient($loginUrl);
		return false;
	}
	
	/**
	 * Calculates the url users should be redirected to in order to authenticate for
	 * this application.
	 * @return string
	 */
	public function getFacebookLoginURL() {
		$facebook = FacebookAPI::get();
		return $facebook->getLoginUrl(
			array('scope' => FacebookAPI::get_permissions())
		);
	}
	
	/**
	 * Redirects the user without interrupting rendering of page. This mechanism is
	 * used to provide a mechanism for redireciting the user while always displaying
	 * full opengraph tags for javascript-disabled crawlers (such as the facebook bot).
	 * @param string $url
	 */
	public function redirectClient($url) {
		Requirements::customScript("top.location.href = '$url';", 'FacebookRedirect');
	}

}