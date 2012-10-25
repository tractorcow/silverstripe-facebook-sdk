<?php

class FacebookAPIExtension extends Extension {
	
    /**
     * Flag to indicate whether this module should attempt to automatically load itself
     * @var boolean
     */
    public static $auto_load = true;
	
	
	public static function load() {
		Object::add_extension('Page_Controller', 'FacebookAPIExtension');
	}
	
	public function onAfterInit() {
		Requirements::javascript('//connect.facebook.net/en_US/all.js');
	}
	
}