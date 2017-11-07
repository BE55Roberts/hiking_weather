<?php
/**
 * FILE:   HikingWeatherController.php
 * GOAL:   To support Awesome Hiking
 * AUTHOR: Bradley Roberts
 * EMAIL:  Bradley@Roberts.net
 * DATE: 8/28/2017
 */

namespace Drupal\hiking_weather\Controller;

// Add Controller Base
use Drupal\Core\Controller\ControllerBase;

/**
 * Class Hiking Weather Controller
 * To process weather requests for specific hiking locations
 * @package Drupal\HikingWeather\Controller
 */
class HikingWeatherController extends ControllerBase {

    // Define tag and RegEx to match
    private $weatherTag = 'WEATHER';
    private $weatherRegEx = '/\[(.*:\s)?(-?(?:\d+|\d*\.\d+))?,(-?(?:\d+|\d*\.\d+))?\]/';
	public $apikey = '1234567890123456';

    /**
     * Content for the default page
     * URL: /hikingweather
     * @return array containing markup for Hiking Weather Forecast
     */
    public function content() {
        $homeForecast = $this->getForecast(35.8958, -83.9411);

        return array(
            '#type'   => 'markup',
            '#markup' => $homeForecast,
        );

    }

    /**
     * Alter the node content when the [WEATHER] tag is found
     *
     * TAG format: [WEATHER: $lat, $lng]
     * WHERE:
     *     $lat is a decimal value -90.0 to +90.0 representing latitude
     *     $lng is a decimal value -180.0 to +180.0 representing longitude
     *
     * @param $build is the current node render array, which will be altered
     *
     * @return $found if the tag was actually found and replaced
     */
    public function hiking_weather_node_view_alter(&$build) {

        $found = FALSE;

        foreach ($build AS $dtl) {

            // Is the tag present
            $tagPresent = stripos($this->weatherTag, $dtl->markup);

            if ($tagPresent !== FALSE) {
                $found = TRUE;

                // Identify the tag, latitude and longitude
                $geoTagged = $this->parse_weather_tag($dtl->markup);

                // Use API Call to retrieve the current forecast nearest this location
                $forecast = $this->getForecast($geoTagged['lat'], $geoTagged['lng']);

                // Embed the rendered HTML into the markup
                $dtl->markup = str_replace($geoTagged['tag'], $forecast, $dtl->markup);
            }
        }

        return $found;
    }

    /**
     * Get the Hiking Weather Forecast for a geolocation
     *
     * @param float $lat is the decimal latitude - e.g. 35.89
     * @param float $lng is the decimal longitude - e.g. -83.94
     *
     * If not given, then defaults are used for home
     *
     * @return string rendering a DIV for the Forecast block
     */
    public function getForecast($lat = 0.00, $lng = 0.00) {

        // Locate images directory
        $images_dir = '/' . \Drupal::moduleHandler()
                                   ->getModule('hiking_weather')
                                   ->getPath() . '/images/';

        // Set defaults
        $default_lat  = 35.895833;
        $default_lng  = -83.94111;
        $default_icon = $images_dir . 'default_weather.gif';
        $default_cond = 'Always variable';
        $wuicon       = $images_dir . "wundergroundLogo_4c_horz.png";
        // Check args
        $lat = (float) $lat;
        $lng = (float) $lng;

        if ($lat == 0.00) {
            $lat = $default_lat;  // OR: 35.53.45 N;
        }

        if ($lng == 0.00) {
            $lng = $default_lng; // OR  84.3.32 W;
        }

        $weather_api = 'http://api.wunderground.com/api';

        // Get the weather forecast from WonderGround.com
        $json_string = file_get_contents($weather_api . '/' . $this->apikey . '/geolookup/forecast/q/' . $lat . ',' . $lng . ".json");
        $parsed_json = json_decode($json_string);
        if ($parsed_json && (json_last_error() == JSON_ERROR_NONE)) {
            $icon_url   = (isset($parsed_json->forecast->simpleforecast->forecastday[0]->icon_url) ? $parsed_json->forecast->simpleforecast->forecastday[0]->icon_url : $default_icon);
            $conditions = (isset($parsed_json->forecast->simpleforecast->forecastday[0]->conditions) ? $parsed_json->forecast->simpleforecast->forecastday[0]->conditions : $default_cond);

            // Map icon file to image on this system, since Drupal rejects Cross Site Images
            if (file_exists($images_dir . basename($icon_url))) {
                $icon_img = $images_dir . basename($icon_url);
            }
            else {
                $icon_img = $default_icon;
            }

            if (!$conditions || (strlen($conditions) == 0)) {
                $conditions = $default_cond;
            }
        }
        else {
            $icon_url   = $default_icon;
            $conditions = $default_cond;
        }

        // Render DIV for the weather block with class hiking_forecast
        return '<div><p class="hiking_forecast"><img src="' . $icon_img . '" title="Weather Icon">' . $conditions . '</p><img src="' . $wuicon . '" width="90px" title="Weather forecast courtesy of www.wunderground.com"></div>' . PHP_EOL;
    }

    /**
     * Parse the string with a known WEATHER tag to get geolocation
     *
     * @internal Expects weatherTag, weatherRegEx class variables
     *
     * @param string $tagText containing a weather tag
     * EG: "[WEATHER: 1.23, -4.56]"
     *
     * @return array containing actual tag, lat, lng values - by default 0s
     */
    public function parse_weather_tag($tagText = '') {
        $geo     = ['tag' => '', 'lat' => 0.00, 'lng' => 0.00];
        $matches = [];

        // Guard against empty strings
        if (strlen($tagText) == 0) {
            return $geo;
        }

        // Find the tag known to be present
        $found = preg_match($this->weatherRegEx, $tagText, $matches);

        if ($found && (strcasecmp(trim($matches[1]), $this->weatherTag . ':') == 0)) {
            $geo['tag'] = $matches[0];
            $geo['lat'] = (float) $matches[2];
            $geo['lng'] = (float) $matches[3];
        }

        return $geo;
    }
}