<?php

class FacebookAPIExtension extends Extension {
	
	// <editor-fold desc="Initialisation">

	/**
	 * Ensures that the facebook API is initialised for this request 
	 */
	public function onBeforeInit() {
		$this->owner->initFacebook();
	}

	/**
	 * Inject required facebook scripts after initialisation 
	 */
	public function onAfterInit() {
		Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.min.js');
		Requirements::javascript('//connect.facebook.net/en_US/all.js#xfbml=1');
	}

	/**
	 * Initialises the facebook API and sets up required javascript
	 * Implement in Page_Controller to override this function
	 */
	public function initFacebook() {

		$this->owner->extend('onBeforeInitFacebook');

		// Back end initialisation
		$facebook = FacebookAPI::get();
		if (empty($facebook))
			return;

		// Frontend initialisation
		$appID = $facebook->getAppId();
		Requirements::customScript("FB.init({
			appId  : '$appID',
			status : true, // check login status
			cookie : true, // enable cookies to allow the server to access the session
			xfbml  : true  // parse XFBML
		});
		FB.Canvas.setAutoGrow(); //Resizes the iframe to fit content");

		$this->owner->extend('onAfterInitFacebook', $facebook);
	}
	
	// </editor-fold>
	
	// <editor-fold desc="Control/Helpers">
	
	/**
	 * Determine if the user is properly authorised, and uses javascript to redirect
	 * the user if not. If all requested permissions have been denied then the client
	 * code should be prepared to handle a manual process for applying for the
	 * missing permissions (e.g. present a link requesting full permissions).
	 * @param string|array $permissions List permissions required in this login
	 * @param string $redirectURL URL to redirect user to after permissions are approved
	 * param boolean $useClientRedirect Flag indicating whether the user should be 
	 * redirected via http headers or javascript. If true, the user will be redirected via 
	 * a client script. If false the user will be redirected via a Location HTTP
	 * header. This should be left true if the application sits within 
	 * an frame, as redirecting the user to a permissions dialog within an iframe
	 * will cause a blank page (as facebook doesn't allow this, even if it's their
	 * own iframe). The use of javascript to redirect the user allows frame popping.
	 * @param boolean $ignoreDenied Force these permissions to be requested, even if they
	 * have been previously denied
	 * @return boolean Flag indicating whether the user is properly authenticated
	 */
	public function requestFacebookPermissions($permissions, $redirectURL = null, $useClientRedirect = true, $ignoreDenied = false) {

		// Check if any permissions are missing
		$missingPermissions = $this->owner->determineUngrantedFacebookPermissions($permissions);
		if (empty($missingPermissions)) return true;

		// Determine positions that this user is allowed to request still.
		// Any permission that has been explicitly denied may not be asked again
		// unless clearFacebookPermissionsDenied has been called or $ignoreDenied
		// is true
		if ($ignoreDenied) {
			$requestingPermissions = $missingPermissions;
		} else {
			$deniedPermissions = $this->owner->getDeniedFacebookPermissions();
			$requestingPermissions = array_diff($missingPermissions, $deniedPermissions);
		}

		// Any requested permission should be initially treated as denied while
		// in the request queue, until explicitly approved or otherwise forced by
		// the application. This is to prevent the application repeatedly 
		// asking for permission, as per facebook guidelines.
		$this->owner->denyFacebookPermissions($requestingPermissions);

		// Redirect to login
		$loginURL = $this->owner->getFacebookLoginURL(implode(',', $requestingPermissions), $redirectURL);
		if($useClientRedirect) {
			$this->owner->redirectClient($loginURL);
		} else {
			$this->owner->redirect($loginURL);
		}
		return false;
	}

	/**
	 * Calculates the url users should be redirected to in order to authenticate for
	 * this application.
	 * @param string $permissions List of comma-separated permissions required in this login
	 * @param string $redirectURL Redirect URL
	 * @return string
	 */
	public function getFacebookLoginURL($permissions, $redirectURL = null) {
		$facebook = FacebookAPI::get();
		$parameters = array('scope' => $permissions);
		if (!empty($redirectURL)) {
			$parameters['redirect_uri'] = $redirectURL;
		}
		return $facebook->getLoginUrl($parameters);
	}

	/**
	 * Redirects the user without interrupting rendering of page. This mechanism is
	 * used to provide a mechanism for redireciting the user while always displaying
	 * full opengraph tags for javascript-disabled crawlers (such as the facebook bot).
	 * @param string $url
	 * @param string $frame The frame to redirect to, either 'self', 'auto' or 'top'
	 */
	public function redirectClient($url, $frame = 'auto') {

		// Redirects to the same hostname should not pop out of frames, where redirects to
		// external urls should direct to the 'top' frame. This is to keep facebook applications
		// nestled snugly within the outer facebook.com wrapper.
		if ($frame == 'auto') {
			if (preg_match("/^(http:\/\/)?(?<host>[^\/]+)/i", $url, $matches) &&
					strcasecmp($matches['host'], $_SERVER['HTTP_HOST']) !== 0) {
				$frame = 'top';
			} else {
				$frame = 'self';
			}
		}
		Requirements::customScript("$frame.location.href = '$url';", 'FacebookRedirect');
	}

	// </editor-fold>
	
	// <editor-fold desc="Permission Getters/Setters">

	/**
	 * Parses the specified permission list
	 * @param array|string $permissions Permission list in the form of either a
	 * string or array of permissions
	 * @return array List of permissions in array format
	 */
	protected function parsePermissions($permissions) {
		if (empty($permissions))
			return array();
		if (is_array($permissions))
			return $permissions;
		if (is_string($permissions))
			return explode(',', $permissions);
		throw new Exception("Could not parse permission list $permissions");
	}

	/*
	 * When this application performs another user-initiated action then any 
	 * previously denied permissions may by re-requested by calling this 
	 * function. Must be used sparingly, and only when appropriate.
	 * @param array|string $permissions The list of permissions to clear from the deny list,
	 * or null if all should be cleared.
	 */

	public function clearDeniedFacebookPermissions($permissions = null) {
		// Null case, nothing to clear
		$FacebookAPIPermissionsDenied = Session::get('FacebookAPIPermissionsDenied');
		if (empty($FacebookAPIPermissionsDenied)) return;

		// Check permissions requested for clearing
		if($permissions === null) {
			$denied = array();
		} else {
			$permissions = $this->parsePermissions($permissions);
			// Remove requested permissions from denied list
			$denied = array_diff($FacebookAPIPermissionsDenied, $permissions);
		}
		$this->owner->setDeniedFacebookPermissions($denied);
	}

	/**
	 * Retrieves the list of all facebook permissions requested, but not yet
	 * authorised. Any requested permission that has not yet been approved 
	 * should be treated as denied 
	 */
	public function getDeniedFacebookPermissions() {
		
		// Null case, no denied items
		$FacebookAPIPermissionsDenied = Session::get('FacebookAPIPermissionsDenied');
		if (empty($FacebookAPIPermissionsDenied)) return array();

		// Remove any recently granted permissions from denied list
		$granted = $this->owner->getGrantedFacebookPermissions();
		$this->owner->clearDeniedFacebookPermissions($granted);
		
		// Remaining denied permissions
		return $FacebookAPIPermissionsDenied;
	}

	/**
	 * Checks the status of a single permission
	 * @param string $permission
	 * @return string Status of the permission, which can be either:
	 * <ul>
	 *  <li>default - Not requested</li>
	 *	<li>denied - Asked for and not granted</li>
	 *  <li>granted - Approved by user</li>
	 * </ul>
	 */
	public function checkFacebookPermission($permission) {
		
		// Check denied
		$denied = $this->owner->getDeniedFacebookPermissions();
		if(in_array($permission, $denied)) return 'denied';
		
		// check granted
		$granted = $this->owner->getGrantedFacebookPermissions();
		if(in_array($permission, $granted)) return 'granted';
		
		return 'default';
	}

	/**
	 * Replaces all marked denied permissions with the specified list.
	 * @param string|array $permissions 
	 */
	public function setDeniedFacebookPermissions($permissions) {
		Session::set('FacebookAPIPermissionsDenied',$this->parsePermissions($permissions));
	}

	/**
	 * Marks the following additional permissions as denied. Does not affect 
	 * already denied permissions
	 * @param array|string $permissions 
	 */
	public function denyFacebookPermissions($permissions) {
		$permissions = $this->parsePermissions($permissions);

		// Merge this list with existing permissions
		$denied = $this->owner->getDeniedFacebookPermissions();
		$denied = array_merge($denied, $permissions);

		// Save back
		$this->owner->setDeniedFacebookPermissions($denied);
	}

	/**
	 * Local per-request cache of approved permissions
	 * This value should not change within a single request
	 * @var array|null
	 */
	protected $facebookPermissionsGranted = null;

	/**
	 * Retrieves the list of all authorised permissions for the current user
	 * @return array List of approved permissions
	 */
	public function getGrantedFacebookPermissions() {

		// Use locally cached permission set 
		if ($this->facebookPermissionsGranted !== null) {
			return $this->facebookPermissionsGranted;
		}

		// Record this cached value
		return $this->facebookPermissionsGranted = $this->owner->updateGrantedFacebookPermissions();
	}

	/**
	 * Retrieves the list of all authorised permissions for the current user
	 * with the most updated version from the server
	 * @return array List of approved permissions, or empty array if none are approved
	 */
	public function updateGrantedFacebookPermissions() {
		// Check user is logged in
		$user = FacebookAPI::get()->getUser();
		if (empty($user))
			return array();

		// Check permissions are retrieveable
		$permissions = FacebookAPI::get()->api("/$user/permissions");
		if (empty($permissions['data'])) return array();

		// $permissions should look something like the below
		// $permissions = array('data' => array(array('installed' => 1, 'email' => 1)), 'paging' => array())
		// Merge each array element in data
		$permissionItems = call_user_func_array('array_merge', $permissions['data']);
		return array_keys($permissionItems);
	}

	/**
	 * Determine if all of the specified permissions are granted
	 * @param string|array $permissions list of permissions requested
	 * @return array List of all permissions requested, but not granted
	 */
	public function determineUngrantedFacebookPermissions($permissions) {
		$permissions = $this->parsePermissions($permissions);

		if (empty($permissions)) return true; // Ask for nothing and ye shall receive it!

		// Subtract granted permissions from those requested
		$grantedPermissions = $this->owner->getGrantedFacebookPermissions();
		return array_diff($permissions, $grantedPermissions);
	}
	
	// </editor-fold>
}