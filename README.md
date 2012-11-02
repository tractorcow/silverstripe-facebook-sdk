# Facebook API wrapper module for Silverstripe

This module provides an extremely simple wrapper for the facebook PHP SDK module from <https://github.com/facebook/facebook-php-sdk>

Warning! This module's API is under considerable development at the moment, and things are changing regularly.
If you would like to use this in a serious application please check back once we have a stable release.

## Credits and Authors

 * Damian Mooyman - <https://github.com/tractorcow/silverstripe-facebook-sdk>

## License

 * TODO

## Requirements

 * SilverStripe 3.0
 * PHP 5.3

## Installation Instructions

 * Extract all files into the 'facebook-sdk' folder under your Silverstripe root.
 * Suggest that you install the opengraph module <https://github.com/tractorcow/silverstripe-opengraph> in
   order to properly meta-tag all pages. This is not absolutely essential.
 * Add "<div id="fb-root"></div>" somewhere in your HTML templates. The facebook JS api 
   will attempt to create this automatically if omitted.
 * Configure the appID and secret key through either your _config.php, or YAML 
   files (see _config/FacebookAPI.yml).
 * Configure your application extended permissions (through YAML only currently).
   See FacebookAPI

## Notes

 * If the OpenGraph module is also installed then this application will automatically use the appID specified under that module