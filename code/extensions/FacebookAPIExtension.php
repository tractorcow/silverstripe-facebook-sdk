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
		});");

		// session initialisation   
		$user = $facebook->getUser();
		$loginUrl = $facebook->getLoginUrl(
			array('scope' => FacebookAPI::get_permissions())
		);

		if ($user) {
			try {
				// Proceed knowing you have a logged in user who's authenticated.
				$user_profile = $facebook->api('/me');
			} catch (FacebookApiException $e) {
				//you should use error_log($e); instead of printing the info on browser
				d($e);  // d is a debug function defined at the end of this file
				$user = null;
			}
		}

		if (!$user) {
			echo "<script type='text/javascript'>top.location.href = '$loginUrl';</script>";
			exit;
		}
		
		$this->owner->extend('onAfterInitFacebook', $facebook);
	}

}