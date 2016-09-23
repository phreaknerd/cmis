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
   * @var boolean
   *    the browser popup flag 
   */
  protected $popup;

  /**
   * Cacheable.
   *
   * @var boolean
   *    the browser cacheable flag
   */
  protected $cacheable;

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
        // TODO: testing this.
        $headers = ['' => 'HTTP/1.1 503 Service unavailable'];
        $response = new Response($content, 503, $headers);
        $response->send();
        exit();
      }

      $mime = $this->current->getContentStreamMimeType();
      $headers = [
        'Cache-Control' => 'no-cache, must-revalidate',
        'Content-type' => $mime,
        'Content-Disposition' => 'attachment; filename="' . $this->current->getName() . '"',
      ];
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
   * @param string $config
   * @param string $folder_id
   */
  private function init($config, $folder_id) {
    $this->config = $config;
    $this->folder_id = $folder_id;
    $this->connection = new CmisConnectionApi($this->config);
    //$cacheable = $this->connection->getConfig()->getCmisCacheable();
    // TODO: find out the best cache options.
    //$cache_parameters = [
    //  'contexts' => ['user'],
    //  'max-age' => $cacheable ? 300 : 0,
    //];
    //$this->cacheable = $cache_parameters;
    if (!empty($this->connection->getHttpInvoker())) {
      $popup = \Drupal::request()->query->get('type');
      $this->popup = ($popup == 'popup');
      $this->connection->setDefaultParameters();

      if (empty($this->folder_id)) {
        $root_folder = $this->connection->getRootFolder();
        $this->folder_id = $root_folder->getId();
        $this->current = $root_folder;
      }
      else {
        $this->current = $this->connection->getObjectById($this->folder_id);
      }
    }
  }

  /**
   * Get current object.
   * 
   * @return object
   */
  public function getCurrent() {
    return $this->current;
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
  public function browse($reset = FALSE) {
    if ($this->connection && !empty($this->current)) {

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

      $browse = [
        '#theme' => 'cmis_browser',
        '#header' => $table_header,
        '#elements' => $this->data,
        '#breadcrumbs' => $this->breadcrumbs,
        '#operations' => $this->prepareOperations(),
          //'#cache' => $this->cacheable,
      ];

      return $browse;
    }

    return [];
  }

  /**
   * Prepare operation links.
   *
   * @return string
   */
  private function prepareOperations() {
    $routes = [
      '/cmis/browser-create-folder/' => t('Create folder'),
      '/cmis/browser-upload-document/' => t('Add document'),
    ];

    $links = [];
    foreach ($routes as $route => $title) {
      $url = \Drupal\Core\Url::fromUserInput($route . $this->config . '/' . $this->current->getId());
      $link_options = array(
        'attributes' => array(
          'class' => ['use-ajax'],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => \Drupal\Component\Serialization\Json::encode([
            'height' => 400,
            'width' => 700
          ]),
        ),
      );
      $url->setOptions($link_options);
      $path = \Drupal\Core\Link::fromTextAndUrl($title, $url)->toRenderable();
      $links[] = [
        '#markup' => render($path),
        '#wrapper_attributes' => [
          'class' => ['object-properties']
        ],
      ];
    }

    $list = [
      '#theme' => 'item_list',
      '#items' => $links,
      '#type' => 'ul',
    ];

    return render($list);
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
              //'#cache' => $this->cacheable,
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
    $operations = '';
    if ($theme == 'cmis_browser_document_item') {
      $this->prepareDocumentElement($children, $data, $operations, $id);
    }
    if (!$this->popup) {
      $this->preparePropertiesLink($children, $operations);
    }

    $element = [
      '#theme' => $theme,
      '#element' => $data,
        //'#cache' => $this->cacheable,
    ];

    $details = [
      '#theme' => 'cmis_browser_document_details',
      '#title' => $title,
      '#mime_type' => $mime_type,
      '#size' => number_format($size, 0, '', ' '),
        //'#cache' => $this->cacheable,
    ];

    $this->data[] = [
      render($element),
      render($details),
      $author,
      $created,
      $description,
      $operations
    ];
  }

  /**
   * Prepare document element.
   *
   * @param type $children
   * @param type $data
   * @param type $operations
   * @param type $id
   */
  private function prepareDocumentElement($children, &$data, &$operations, $id) {
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
      $operations = render($path);
    }

    $url = \Drupal\Core\Url::fromUserInput('/cmis/document/' . $this->config . '/' . $id);
    $path = \Drupal\Core\Link::fromTextAndUrl($data, $url)->toRenderable();
    $data = ['#markup' => render($path)];
  }

  /**
   * Prepare properties link.
   *
   * @param type $children
   * @param type $operations
   */
  private function preparePropertiesLink($children, &$operations) {
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
    );
    $url->setOptions($link_options);
    $path = \Drupal\Core\Link::fromTextAndUrl(t('Properties'), $url)->toRenderable();
    $links[] = [
      '#markup' => render($path),
      '#wrapper_attributes' => [
        'class' => ['object-properties']
      ],
    ];

    $url = \Drupal\Core\Url::fromUserInput('/cmis/object-delete-verify/' . $this->config . '/' . $children->getId());
    $link_options = array(
      'attributes' => array(
        'class' => ['use-ajax'],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => \Drupal\Component\Serialization\Json::encode([
          'height' => 120,
          'width' => 600,
        ]),
      ),
      'query' => ['parent' => $this->current->getId()],
    );
    $url->setOptions($link_options);
    $path = \Drupal\Core\Link::fromTextAndUrl(t('Delete'), $url)->toRenderable();
    $links[] = [
      '#markup' => render($path),
      '#wrapper_attributes' => [
        'class' => ['object-delete']
      ],
    ];

    $list = [
      '#theme' => 'item_list',
      '#items' => $links,
      '#type' => 'ul',
    ];

    $operations = render($list);
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
