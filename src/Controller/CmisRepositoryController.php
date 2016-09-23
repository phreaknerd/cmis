<?php

namespace Drupal\cmis\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\cmis\CmisBrowser;

/**
 * Class CmisRepositoryController.
 *
 * @package Drupal\cmis\Controller
 */
class CmisRepositoryController extends ControllerBase {

  private $browser;

  /**
   * Construct.
   * 
   * @param string $config
   * @param string $folder_id
   */
  public function __construct($config, $folder_id) {
    if (!empty($config) && !empty($folder_id)) {
      $this->initBrowser($config, $folder_id);
    }
  }

  /**
   * Browse.
   *
   * @param type $config
   * @param type $folder_name
   * 
   * @return string
   *   Return cmis browser render array or warning.
   */
  public function browse($config = '', $folder_id = '') {
    if (empty($this->browser)) {
      $this->initBrowser($config, $folder_id);
    }
    $cacheable = $this->browser->getConnection()->getConfig()->getCmisCacheable();
    return $this->browser->browse(!$cacheable);
  }

  /**
   * Get prperties
   *
   * @param string $config
   * @param string $document_id
   *
   * @return array
   *    Return properties table render array.
   */
  public function getProperties($config = '', $document_id = '') {
    if (empty($this->browser)) {
      $this->initBrowser($config, $document_id);
    }
    return $this->browser->getDocumentProperties();
  }

  /**
   * Object delete verify popup.
   *
   * @param string $config
   * @param string $document_id
   */
  public function objectDeleteVerify($config = '', $object_id = '') {
    if ($parent = \Drupal::request()->query->get('parent')) {
      if (empty($this->browser)) {
        $this->initBrowser($config, $object_id);
      }
      if ($this->browser->getConnection() && $current = $this->browser->getCurrent()) {
        $type = $current->getBaseTypeId()->__toString();
        $name = $current->getName();


        $args = [
          '@type' => str_replace('cmis:', '', $type),
          '@name' => $name,
        ];

        $url = \Drupal\Core\Url::fromUserInput('/cmis/object-delete/' . $config . '/' . $object_id);
        $link_options = [];
        if (isset($parent)) {
          $link_options['query'] = ['parent' => $parent];
        }
        $url->setOptions($link_options);
        $path = \Drupal\Core\Link::fromTextAndUrl(t('Delete'), $url)->toRenderable();
        $link = render($path);

        return [
          '#theme' => 'cmis_object_delete_verify',
          '#title' => $this->t('Are you sure you want to delete @type name @name', $args),
          '#description' => $this->t('This action cannot be undone.'),
          '#link' => $link,
        ];
      }
      return [
        '#theme' => 'cmis_object_delete_verify',
        '#title' => $this->t("Object can't delete"),
        '#description' => $this->t('Object not found in repository.'),
        '#link' => '',
      ];
    }

    return [
      '#theme' => 'cmis_object_delete_verify',
      '#title' => $this->t("Object can't delete"),
      '#description' => $this->t('Without parent object definition can not delete the object.'),
      '#link' => '',
    ];
  }

  /**
   * Object delete popup.
   *
   * @param string $config
   * @param string $document_id
   */
  public function objectDelete($config = '', $object_id = '') {
    if ($parent = \Drupal::request()->query->get('parent')) {
      if (empty($this->browser)) {
        $this->initBrowser($config, $object_id);
      }
      if ($this->browser->getConnection() && $current = $this->browser->getCurrent()) {
        $object = $this->browser->getConnection()->getSession()->getObject($current);
        $type = $object->getBaseTypeId()->__toString();
        $name = $object->getName();

        $args = [
          '@type' => str_replace('cmis:', '', $type),
          '@name' => $name,
        ];

        $object->delete(TRUE);

        drupal_set_message($this->t('The @type name @name has now been deleted.', $args));
        return $this->redirect('cmis.cmis_repository_controller_browser', ['config' => $config, 'folder_id' => $parent]);
      }
    }

    drupal_set_message($this->t('Without parent object definition can not delete the object.'), 'warning');
    return $this->redirect('cmis.cmis_repository_controller_browser', ['config' => $config]);
  }

  /**
   * Init browser.
   *
   * @param string $config
   * @param string $folder_id
   *
   * @return array
   */
  private function initBrowser($config, $folder_id) {
    if (!empty($config)) {
      $browser = new CmisBrowser($config, $folder_id);
      if ($browser->getConnection()) {
        $this->browser = $browser;
      }
      else {
        return $this->connectionError($config);
      }
    }
    else {
      return $this->configureError();
    }
  }

  /**
   * Get browser.
   *
   * @return object
   */
  public function getBrowser() {
    return $this->browser;
  }
  
  /**
   * Prepare configure error.
   *
   * @return array
   */
  private function configureError() {
    return array(
      '#markup' => $this->t('No configure defined. Please go to CMIS configure page and create configure.'),
    );
  }

  /**
   * Prepare connection error.
   *
   * @param string $config
   *
   * @return array
   */
  private function connectionError($config) {
    return array(
      '#markup' => $this->t('No connection ready of config: @config. Please go to CMIS configure page and create properly configure.', ['@config' => $config]),
    );
  }

}
