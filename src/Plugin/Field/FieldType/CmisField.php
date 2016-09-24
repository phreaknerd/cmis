<?php

namespace Drupal\cmis\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'cmis_field' field type.
 *
 * @FieldType(
 *   id = "cmis_field",
 *   label = @Translation("Cmis Field"),
 *   description = @Translation("Attach a CMIS object to a Drupal entity"),
 *   default_widget = "cmis_field_widget",
 *   default_formatter = "cmis_field_link"
 * )
 */
class CmisField extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'title' => array(
          'type' => 'varchar',
          'length' => 255,
        ),
        'path' => array(
          'type' => 'varchar',
          'length' => 255,
        ),
      ),
    );
  }
 
  /**
   * {@inheritdoc}
  */
  public function isEmpty() {
    $value1 = $this->get('title')->getValue();
    $value2 = $this->get('path')->getValue();
    return empty($value1) && empty($value2);
  }
 
  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    // Add our properties.
    $properties['title'] = DataDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The title of CMIS object.'));
 
    $properties['path'] = DataDefinition::create('string')
      ->setLabel(t('Path'))
      ->setDescription(t('The path of CMIS object.'));
 
    return $properties;
  }
}
