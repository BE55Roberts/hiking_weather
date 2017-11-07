<?php
/**
 * FILE:   HikingWeather.php
 * GOAL:   To support Awesome Hiking
 * AUTHOR: Bradley Roberts
 * EMAIL:  Bradley@Roberts.net
 * DATE: 8/28/2017
 */

namespace Drupal\HikingWeather\Controller;

// Add Controller Base
use Drupal\Core\Controller\ControllerBase;

/**
 * Class HikingWeather
 * To process weather requests for specific hiking locations
 * @package Drupal\HikingWeather\Controller
 */
class HikingWeather extends ControllerBase {

	public $apikey = '1234567890123456';

	public function content() {
		return array(
			'#type'   => 'markup',
			'#markup' => $this->t( 'Hello Hiker' ),
		);

	}

	public function getTemperature( $lat = 0.00, $lng = 0.00 ) {

		$location = '';
		$temp_f   = '';

		// Check args
		if ( $lat == 0.00 ) {
			$lat = 35.89583333;  // OR: 35.53.45 N;
		}

		if ( $lng == 0.00 ) {
			$lng = -83.94111111; // OR  84.3.32 W;
		}

		// Get the weather forecast from WonderGound.com
		$json_string = file_get_contents( "http://api.wunderground.com/api/'.$this->apikey.'/geolookup/forecast/q/" . $lat . ',' . $lng . ".json" );
		$parsed_json = json_decode( $json_string );
		if ( $parsed_json && ( json_last_error() == JSON_ERROR_NONE ) ) {
			$location = $parsed_json->{'location'}->{'city'};
			$temp_f   = $parsed_json->{'current_observation'}->{'temp_f'};

		}

		return "Current temperature at ${location} is: ${temp_f}\n";

	}

	public function getIcon( $lat = 0.00, $lng = 0.00 ) {
		return '';
	}
}