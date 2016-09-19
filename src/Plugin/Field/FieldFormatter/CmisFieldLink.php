<?php

/**
 * @file
 * Contains \Drupal\cmis\Plugin\Field\FieldFormatter\CmisFieldLink.
 */

namespace Drupal\cmis\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'cmis_field_link' formatter.
 *
 * @FieldFormatter(
 *   id = "cmis_field_link",
 *   label = @Translation("Cmis field link"),
 *   field_types = {
 *     "cmis_field"
 *   }
 * )
 */
class CmisFieldLink extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
        // Implement default settings.
        ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return array(
        // Implement settings form.
        ) + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    // Implement settings summary.

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = $this->viewValue($item);
    }

    return $elements;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return string
   *   The textual output generated.
   */
  protected function viewValue(FieldItemInterface $item) {
    $url = \Drupal\Core\Url::fromUserInput($item->get('path')->getValue());
    if (empty($url)) {
      return [];
    }
    $path = \Drupal\Core\Link::fromTextAndUrl($item->get('title')->getValue(), $url)->toRenderable();

    return $path;
  }

}
