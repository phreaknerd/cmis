<?php

/**
 * @file
 * Contains \Drupal\cmis\Entity\CmisConnectionEntity.
 */

namespace Drupal\cmis\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\cmis\CmisConnectionEntityInterface;

/**
 * Defines the CMIS connection entity.
 *
 * @ConfigEntityType(
 *   id = "cmis_connection_entity",
 *   label = @Translation("CMIS connection"),
 *   handlers = {
 *     "list_builder" = "Drupal\cmis\CmisConnectionEntityListBuilder",
 *     "form" = {
 *       "add" = "Drupal\cmis\Form\CmisConnectionEntityForm",
 *       "edit" = "Drupal\cmis\Form\CmisConnectionEntityForm",
 *       "delete" = "Drupal\cmis\Form\CmisConnectionEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\cmis\CmisConnectionEntityHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "cmis_connection_entity",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
*   },
 *   links = {
 *     "canonical" = "/admin/config/cmis/connection/cmis_connection_entity/{cmis_connection_entity}",
 *     "add-form" = "/admin/config/cmis/connection/cmis_connection_entity/add",
 *     "edit-form" = "/admin/config/cmis/connection/cmis_connection_entity/{cmis_connection_entity}/edit",
 *     "delete-form" = "/admin/config/cmis/connection/cmis_connection_entity/{cmis_connection_entity}/delete",
 *     "collection" = "/admin/config/cmis/connection/cmis_connection_entity"
 *   }
 * )
 */
class CmisConnectionEntity extends ConfigEntityBase implements CmisConnectionEntityInterface {

  /**
   * The CMIS connection ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The CMIS connection label.
   *
   * @var string
   */
  protected $label;

  /**
   * The CMIS connection url.
   *
   * @var string
   */
  protected $cmis_url;

  /**
   * The CMIS connection user.
   *
   * @var string
   */
  protected $cmis_user;

  /**
   * The CMIS connection password.
   *
   * @var string
   */
  protected $cmis_password;

  /**
   * The CMIS connection repository id.
   *
   * @var string
   */
  protected $cmis_repository;
  
  /**
   * Get CMIS url.
   *
   * @return string
   */
  public function getCmisUrl() {
    return $this->cmis_url;
  }

  /**
   * Get CMIS user name.
   *
   * @return string
   */
  public function getCmisUser() {
    return $this->cmis_user;
  }

  /**
   * Get CMIS password
   *
   * @return string
   */
  public function getCmisPassword() {
    return $this->cmis_password;
  }

  /**
   * Get CMIS repository id.
   *
   * @return int
   */
  public function getCmisRepository() {
    return $this->cmis_repository;
  }

}
