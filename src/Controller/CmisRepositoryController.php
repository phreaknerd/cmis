<?php

/**
 * @file
 * Contains \Drupal\cmis\Controller\CmisRepositoryController.
 */

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
   * Browse.
   *
   * @param type $config
   * @param type $folder_name
   * 
   * @return string
   *   Return cmis browser render array or warning.
   */
  public function browse($config = '', $folder_id = '') {
    $this->initBrowser($config, $folder_id);
    return $this->browser->browse();
  }
  
  public function getProperties($config = '', $document_id = '') {
    $this->initBrowser($config, $document_id);
    return $this->browser->getDocumentProperties();
  }

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
   * 
   * @return type
   */
  private function configureError() {
    return array(
      '#markup' => $this->t('No configure defined. Please go to CMIS configure page and create configure.'),
    );
  }

  /**
   * 
   * @param type $config
   * @return type
   */
  private function connectionError($config) {
    return array(
      '#markup' => $this->t('No connection ready of config: @config. Please go to CMIS configure page and create properly configure.', ['@config' => $config]),
    );
  }

}
