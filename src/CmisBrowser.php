<?php

namespace Drupal\cmis;

use Drupal\cmis\CmisConnectionApi;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Symfony\Component\HttpFoundation\Response;

/**
 * Description of CmisBrowser
 *
 * @author dj
 */
class CmisBrowser {

  /**
   * Configuration id.
   *
   * @var string
   *    the configuration id
   */
  protected $config;

  /**
   * Connection object
   *
   * @var object
   *    the connection object.
   */
  protected $connection;

  /**
   * Content data
   *
   * @var array
   *    the renderable content data
   */
  protected $data;

  /**
   * Parent folders list data
   *
   * @var array
   *    the renderable breadcrumb data
   */
  protected $breadcrumbs = [];

  /**
   * Folder id.
   *
   * @var string
   *    the folder id to browse
   */
  protected $folder_id;

  /**
   * Current object.
   *
   * @var object
   *    the current object
   */
  protected $current;

  /**
   * Popup.
   *
   * @var type
   *    the browser popup flag 
   */
  protected $popup;

  /**
   * Base type id.
   * 
   * @var string
   *    the object base type id
   */
  protected $baseTypeId = 'cmis:folder';

  /**
   * Constructing the object.
   *
   * @param type $config
   * @param type $folder_id
   */
  public function __construct($config = '', $folder_id = '') {
    if (!empty($config)) {
      $this->init($config, $folder_id);
    }
  }


  /**
   * Call from ajaxify url.
   *
   * @param string $config
   * @param string $folder_id
   */
  public function ajaxCall($config, $folder_id) {
    $this->init($config, $folder_id);
    if ($this->connection && !empty($this->current)) {
      $response = new AjaxResponse();
      $content = render($this->browse());
      $response->addCommand(new HtmlCommand('#cmis-browser-wrapper', $content));

      return $response;
    }
  }

  /**
   * Get document by id.
   *
   * @param type $config
   * @param type $document_id
   */
  public function getDocument($config = '', $document_id = '') {
    $this->init($config, $document_id, 'cmis:document');
    if ($this->connection && !empty($this->current) &&
        $this->current->getBaseTypeId()->__toString() == 'cmis:document') {
      $id = $this->current->getId();
      try {
        $content = $this->current->getContentStream($id);
      }
      catch (CMISException $e) {
        $headers = ['' => 'HTTP/1.1 503 Service unavailable'];
        $response = new Response($content, 503, $headers);
        $response->send();
        exit();
      }

      $mime = $this->current->getContentStreamMimeType();
      $headers = [
        'Cache-Control' => 'no-cache, must-revalidate',
        'Content-type' => $mime,
      ];
      if ($mime != 'text/html') {
        $headers['Content-Disposition'] = 'attachment; filename="' . $this->current->getName() . '"';
      }
      $response = new Response($content, 200, $headers);
      $response->send();

      print($content);
      exit();
    }
  }

  /**
   * Get document properties.
   * 
   * @return array
   *    the renderable array
   */
  public function getDocumentProperties() {
    if ($this->connection && !empty($this->current)) {
      $type_id = $this->current->getBaseTypeId()->__toString();
      $path = [];
      if ($type_id == 'cmis:document') {
        $url = \Drupal\Core\Url::fromUserInput('/cmis/document/' . $this->config . '/' . $this->current->getId());
        $path = \Drupal\Core\Link::fromTextAndUrl(t('Download'), $url)->toRenderable();
      }

      return [
        '#theme' => 'cmis_content_properties',
        '#object' => $this->current,
        '#download' => render($path),
      ];
    }
  }

  /**
   * Init variables.
   *
   * @param type $config
   * @param type $folder_id
   */
  private function init($config, $folder_id, $type = 'cmis:folder') {
    $this->config = $config;
    $this->folder_id = $folder_id;
    $this->connection = new CmisConnectionApi($this->config);
    if (!empty($this->connection->getHttpInvoker())) {
      $popup = \Drupal::request()->query->get('type');
      $this->popup = ($popup == 'popup');
      $this->baseTypeId = \Drupal::request()->query->get('cmis_base_type_id');
      $this->connection->setDefaultParameters();

      if (empty($this->folder_id)) {
        $root_folder = $this->connection->getRootFolder();
        $this->folder_id = $root_folder->getId();
        $this->current = $root_folder;
      }
      else {
        if (!empty($this->baseTypeId)) {
          $type = $this->baseTypeId;
        }
        $this->current = $this->connection->getObjectById($this->folder_id);
      }
    }
  }

  /**
   * Get connection.
   *
   * @return object
   */
  public function getConnection() {
    return $this->connection;
  }

  /**
   * Set General parameters.
   */
  private function setParameters() {
    if ($this->connection) {
      $this->connection->setDefaultParameters();
    }
  }

  /**
   * Browse.
   * 
   * @return array
   *   Return cmis browser render array.
   */
  public function browse() {
    if ($this->connection && !empty($this->current)) {
      $type_id = $this->current->getBaseTypeId()->__toString();
      $name = $this->current->getName();

      $this->setBreadcrumbs($this->current);
      $this->printFolderContent($this->current);

      $table_header = array(
        t('Name'),
        t('Details'),
        t('Author'),
        t('Created'),
        t('Description'),
        t('Operation'),
      );

      return array(
        '#theme' => 'cmis_browser',
        '#header' => $table_header,
        '#elements' => $this->data,
        '#breadcrumbs' => $this->breadcrumbs,
      );
    }

    return [];
  }

  /**
   * Add folder objects to render array.
   * 
   * @param \Dkd\PhpCmis\Data\FolderInterface $folder
   */
  protected function printFolderContent(\Dkd\PhpCmis\Data\FolderInterface $folder) {
    foreach ($folder->getChildren() as $children) {
      $type_id = $children->getBaseTypeId()->__toString();
      $name = $children->getName();
      $id = $children->getId();
      switch ($type_id) {
        case 'cmis:folder':
          $url = \Drupal\Core\Url::fromUserInput('/cmis/browser/nojs/' . $this->config . '/' . $id);
          $link_options = array(
            'attributes' => array(
              'class' => array(
                'use-ajax',
              ),
            ),
          );

          if ($this->popup) {
            $link_options['query'] = ['type' => 'popup'];
          }

          $url->setOptions($link_options);

          $link = \Drupal\Core\Link::fromTextAndUrl($name, $url)->toRenderable();
          $this->setElement($children, 'cmis_browser_folder_item', $link);
          break;
        case 'cmis:document':
          $this->setElement($children, 'cmis_browser_document_item', $name, $id);
          break;
        default:
          $element = [
            '#theme' => 'cmis_browser_other_item',
            '#element' => $name,
          ];
          $this->data[] = [render($element)];
      }
    }
  }

  /**
   * Set element to render array.
   * 
   * @param type $children
   */
  protected function setElement($children, $theme, $data, $id = '') {
    $author = $children->getCreatedBy();
    $created = $children->getCreationDate()->format('Y-m-d H:i:s');
    $description = $children->getDescription();

    $title = '';
    if ($title_property = $children->getProperty('cm:title')) {
      $title = $title_property->getFirstValue();
    }

    $size = 0;
    if ($size_property = $children->getProperty('cmis:contentStreamLength')) {
      $size = $size_property->getFirstValue();
    }

    $mime_type = '';
    $link = '';
    if ($theme == 'cmis_browser_document_item') {
      $mime_type = $children->getContentStreamMimeType();
      if ($this->popup) {
        $url = \Drupal\Core\Url::fromUserInput('/');
        $link_options = array(
          'attributes' => array(
            'class' => array(
              'cmis-field-insert',
            ),
            'id' => $children->getProperty('cmis:objectId')->getFirstValue(),
            'name' => $data,
          ),
        );
        $url->setOptions($link_options);
        $path = \Drupal\Core\Link::fromTextAndUrl(t('Choose'), $url)->toRenderable();
        $link = render($path);
      }

      $url = \Drupal\Core\Url::fromUserInput('/cmis/document/' . $this->config . '/' . $id);
      $path = \Drupal\Core\Link::fromTextAndUrl($data, $url)->toRenderable();
      $data = render($path);
    }
    if (!$this->popup) {
      $url = \Drupal\Core\Url::fromUserInput('/cmis/object-properties/' . $this->config . '/' . $children->getId());
      $link_options = array(
        'attributes' => array(
          'class' => ['use-ajax'],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => \Drupal\Component\Serialization\Json::encode([
            'height' => 400,
            'width' => 700
          ]),
        ),
        'query' => [
          'cmis_base_type_id' => $children->getBaseTypeId()->__toString(),
          'process' => 'documentProperties',
        ],
      );
      $url->setOptions($link_options);
      $path = \Drupal\Core\Link::fromTextAndUrl(t('Properties'), $url)->toRenderable();
      $link = render($path);
    }

    $element = [
      '#theme' => $theme,
      '#element' => $data,
    ];

    $details = [
      '#theme' => 'cmis_browser_document_details',
      '#title' => $title,
      '#mime_type' => $mime_type,
      '#size' => number_format($size, 0, '', ' '),
    ];

    $this->data[] = [
      render($element),
      render($details),
      $author,
      $created,
      $description,
      $link
    ];
  }

  /**
   * Create breadcrumbs from parent folders.
   *
   * @param type $folder
   */
  protected function setBreadcrumbs($folder) {
    $name = $folder->getName();
    $id = $folder->getId();
    $this->setBreadcrumb($name, $id);
    if ($parent = $folder->getFolderParent()) {
      $this->setBreadcrumbs($parent);
    }
  }

  /**
   * Prepare a breadcrumb url.
   *
   * @param type $label
   * @param type $name
   */
  protected function setBreadcrumb($label, $id = '') {
    $path = '/cmis/browser/nojs/' . $this->config;
    if (!empty($id)) {
      $path .= '/' . $id;
    }
    $url = \Drupal\Core\Url::fromUserInput($path);
    $link_options = array(
      'attributes' => array(
        'class' => array(
          'use-ajax',
        ),
      ),
    );
    $url->setOptions($link_options);
    array_unshift($this->breadcrumbs, \Drupal\Core\Link::fromTextAndUrl($label, $url)->toRenderable());
  }

}
