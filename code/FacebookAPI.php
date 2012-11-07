<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of FacebookAPI
 *
 * @author Damo
 */
class FacebookAPI {
	
	protected static $instance = null;
	
	/**
	 * @return Facebook Facebook API instance
	 */
	public static function get() {
		if(self::$instance) return self::$instance;
	
		// Check application is configured
		$settings = SiteConfig::current_site_config();
		if(empty($settings->FacebookApplicationID)) return null;
		
		// Construct
		return self::$instance = new Facebook(array(
			'appId'  => $settings->FacebookApplicationID,
			'secret' => $settings->FacebookApplicationSecret,
			'cookie' => true
		));
	}
	
	/**
	 * Retrieves the configured field, or "SiteConfig" if this should be
	 * managed through the siteconfig instead of yaml configuration
	 * @return string Value of the configured field
	 */
	public static function get_config($field) {
		return Config::inst()->get('FacebookAPI', $field);
	}

	/**
	 * Configure the site to use a specified value for a field. Specifying 'SiteConfig'
	 * will cause the value for this field to be managed via the SiteConfig
	 * @param string $field
	 * @param string $value 
	 */
	public static function set_config($field, $value = 'SiteConfig') {
		Config::inst()->update('FacebookAPI', $field, $value);
	}
	
	/**
	 * Sets the appID, or 'SiteConfig' to manage in CMS
	 * To retrieve the calculated value for appID you should use
	 * SiteConfig::getFacebookApplicationID
	 * @param string $value 
	 */
	public static function set_app_id($value) {
		self::set_config('appID', $value);
	}
	
	/**
	 * Sets the API secret key, or 'SiteConfig' to manage in CMS
	 * To retrieve the calculated value for secret you should use 
	 * SiteConfig::getFacebookApplicationSecret
	 * @param string $value 
	 */
	public static function set_secret($value) {
		self::set_config('secret', $value);
	}
}