<?php

namespace Drupal\cmis;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining CMIS connection entities.
 */
interface CmisConnectionEntityInterface extends ConfigEntityInterface {
  public function getCmisUrl();
  public function getCmisUser();
  public function getCmisPassword();
  public function getCmisRepository();
  
}
