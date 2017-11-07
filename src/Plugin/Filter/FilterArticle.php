<?php

/**
 * FILE:   FilterArticle.php
 * GOAL:   To support Awesome Hiking by filtering articles
 * AUTHOR: Bradley Roberts
 * EMAIL:  Bradley@Roberts.net
 * DATE:   09/01/2017
 */

namespace Drupal\hiking_weather\Plugin\Filter;

// OR : namespace modules\custom\hiking_weather\src\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class FilterArticle
 * Looks for WEATHER tags to replace with a forecast object
 *
 * @package Drupal\hiking_weather\Plugin\Filter
 *
 * EG:
 * @Filter(
 *     id = "filter_hiking_weather",
 *     title = @Translation("Hiking Weather Filter"),
 *     description = @Translation("Help potential hikers by providing forecast"),
 *     type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 *     )
 */
class FilterArticle extends FilterBase {

    /**
     * Process the Filter Request
     *
     * @param string $text
     * @param string $langcode
     *
     * @return \Drupal\filter\FilterProcessResult
     */
    public function process($text, $langcode) {

        // Instantiate the controller instance
        $weathering = new \Drupal\hiking_weather\Controller\HikingWeatherController();

        // Identify the tag, latitude and longitude
        $geoTagged = $weathering->parse_weather_tag($text);

        // Use API Call to retrieve the current forecast nearest this location
        $forecast = $weathering->getForecast($geoTagged['lat'], $geoTagged['lng']);

        // Embed the rendered HTML into the markup
        $new_text = str_replace($geoTagged['tag'], $forecast, $text);

        // Instantiate a filter process
        $result = new FilterProcessResult($new_text);

        $result->setAttachments(array(
            'library' => array('hiking_weather/hiking-weather'),
        ));

        return $result;
    }

    /**
     * Setup the Admin Form for this Plugin
     *
     * @param array                                $form
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     *
     * @return array $form containing form element
     */
    public function settingsForm(array $form, FormStateInterface $form_state) {
        $form['hiking_weather'] = array(
            '#type'          => 'input',
            '#title'         => $this->t('API KEY'),
            '#default_value' => $this->settings['api_key'],
            '#description'   => $this->t('Enter the API Key for the Wunderground.com call.'),
        );

        return $form;
    }
}