<?php

namespace Drupal\cmis\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\NestedArray;

/**
 * Plugin implementation of the 'cmis_field_widget' widget.
 *
 * @FieldWidget(
 *   id = "cmis_field_widget",
 *   label = @Translation("Cmis field widget"),
 *   field_types = {
 *     "cmis_field"
 *   }
 * )
 */
class CmisFieldWidget extends WidgetBase {

  private $cmis_configurations = [];

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'size' => 60,
      'placeholder' => '',
      'cmis_configuration' => '',
        ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];

    $elements['size'] = array(
      '#type' => 'number',
      '#title' => t('Size of textfield'),
      '#default_value' => $this->getSetting('size'),
      '#required' => TRUE,
      '#min' => 1,
    );
    $elements['placeholder'] = array(
      '#type' => 'textfield',
      '#title' => t('Placeholder'),
      '#default_value' => $this->getSetting('placeholder'),
      '#description' => t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    );

    if (empty($this->cmis_configurations)) {
      $this->getConfigurations();
    }
    $elements['cmis_configuration'] = array(
      '#type' => 'select',
      '#title' => t('CMIS configuration'),
      '#description' => t('Please choose one from CMIS configuration.'),
      '#options' => $this->cmis_configurations,
      '#require' => TRUE,
      '#default_value' => $this->getSetting('cmis_configuration'),
    );

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    if (empty($this->cmis_configurations)) {
      $this->getConfigurations();
    }
    $summary = [];

    $summary[] = t('Textfield size: !size', array('!size' => $this->getSetting('size')));
    if (!empty($this->getSetting('placeholder'))) {
      $summary[] = t('Placeholder: @placeholder', array('@placeholder' => $this->getSetting('placeholder')));
    }
    $cmis_configuration = $this->getSetting('cmis_configuration');
    if (!empty($cmis_configuration)) {
      $summary[] = t('CMIS configuration: @cmis_configuration', array('@cmis_configuration' => $this->cmis_configurations[$cmis_configuration]));
    }


    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $title = isset($items[$delta]->title) ? $items[$delta]->title : NULL;
    $path = isset($items[$delta]->path) ? $items[$delta]->path : NULL;

    $element = [
      '#prefix' => '<div id="cmis-field-wrapper">',
      '#suffix' => '</div>',
    ];

    $element['title'] = array(
      '#type' => 'textfield',
      '#default_value' => $title,
      '#size' => $this->getSetting('size'),
      '#placeholder' => $this->getSetting('placeholder'),
      '#maxlength' => $this->getFieldSetting('max_length'),
      '#attributes' => [
        'class' => ['edit-field-cmis-field'],
      ],
    );

    $element['path'] = array(
      '#type' => 'hidden',
      '#default_value' => $path,
      '#attributes' => [
        'class' => ['edit-field-cmis-path'],
      ],
    );

    $url = \Drupal\Core\Url::fromUserInput('/cmis/browser/' . $this->getSetting('cmis_configuration'));
    $link_options = array(
      'attributes' => array(
        'class' => ['use-ajax'],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => \Drupal\Component\Serialization\Json::encode([
          'height' => 400,
          'width' => 700
        ]),
      ),
      'query' => ['type' => 'popup'],
    );
    $url->setOptions($link_options);
    $element['cmis_browser'] = \Drupal\Core\Link::fromTextAndUrl(t('Browse'), $url)->toRenderable();
    $element['#attached']['library'][] = 'cmis/cmis-field';

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as &$item) {
      if (!empty($item['path'])) {
        $args = explode('/', $item['path']);
        $id = end($args);
        $item['path'] = '/cmis/document/' . $this->getSetting('cmis_configuration') . '/' . $id;
      }
    }

    return $values;
  }

  /**
   * Get configuration entity to private variable.
   *
   */
  private function getConfigurations() {
    $this->cmis_configurations = cmis_get_configurations();
  }

}
