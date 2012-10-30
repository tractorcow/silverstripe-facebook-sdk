<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of FacebookAPISiteConfig
 *
 * @author Damo
 */
class FacebookAPISiteConfig extends DataExtension {
	
	public static function get_extra_config($class, $extensionClass, $args) {
		
        $db = array();
		
		// Note: OpenGraph module also appends this field, so don't duplicate
		if(!class_exists('OpenGraphSiteConfigExtension') && 
			FacebookAPI::get_config('appID') == 'SiteConfig') {
			$db['FacebookApplicationID'] = 'Varchar(255)';
		}
		
        if (FacebookAPI::get_config('secret') == 'SiteConfig') {
            $db['FacebookApplicationSecret'] = 'Varchar(255)';
		}

        return array(
            'db' => $db
        );
    }
	
	public function updateCMSFields(FieldList $fields) {
		
        if (!class_exists('OpenGraphSiteConfigExtension') && 
			FacebookAPI::get_config('appID') == 'SiteConfig') {
            $fields->addFieldToTab(
				'Root.Facebook',
				new TextField('FacebookApplicationID', 'Facebook Application ID', null, 255)
			);
		}
		
        if (FacebookAPI::get_config('secret') == 'SiteConfig') {
			$fields->addFieldToTab(
				'Root.Facebook',
				new TextField('FacebookApplicationSecret')
			);
		}
    }
	
	protected function getConfigurableField($dbField, $configField) {
		$value = FacebookAPI::get_config($configField);
        if ($value == 'SiteConfig') {
            return $this->owner->getField($dbField);
		}
        return $value;
	}

    public function getFacebookApplicationID() {
		if(class_exists('OpenGraphSiteConfigExtension')) {
			// If the OpenGraph module is already installled, use that configuration for retrieving the application ID
			return $this->owner->OGApplicationID;
		}
		return $this->getConfigurableField('FacebookApplicationID', 'appID');
    }
	
    public function getFacebookApplicationSecret() {
		return $this->getConfigurableField('FacebookApplicationSecret', 'secret');
    }
}