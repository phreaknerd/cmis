<?php

namespace Drupal\cmis;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of CMIS connection entities.
 */
class CmisConnectionEntityListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('CMIS connection');
    $header['id'] = $this->t('Machine name');
    $header['process'] = $this->t('Browse');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $url = \Drupal\Core\Url::fromUserInput('/cmis/browser/' . $entity->id());
    $link = \Drupal\Core\Link::fromTextAndUrl($this->t('Browse'), $url);
    $row['process'] = $link;
    return $row + parent::buildRow($entity);
  }

}
