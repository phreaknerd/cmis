<?php

namespace Drupal\cmis;

use Dkd\PhpCmis\Enum\BindingType;
use Dkd\PhpCmis\SessionParameter;

/**
 * Description of CmisConnectionApi
 *
 * @author dj
 */
class CmisConnectionApi {

  /**
   * Configuration
   *
   * @var object
   *    the configuration entity
   */
  private $config;

  /**
   * Http invoker
   *
   * @var object
   *    the Http invoker
   */
  private $httpInvoker;

  /**
   * Parameters
   *
   * @var array
   *    the parameters for connection type 
   */
  private $parameters;

  /**
   * Session factory
   *
   * @var object
   *    the session factory for connection
   */
  private $sessionFactory;

  /**
   * Session
   *
   * @var type
   *    the session of connection
   */
  private $session;

  /**
   * Root folder
   *
   * @var type
   *    the root folder of alfresco repository
   */
  private $rootFolder;

  /**
   * {@inheritdoc}
   */
  public function __construct($config = '') {
    $this->checkClient();
    $this->setConfig($config);
  }

  /**
   * Check if cmis exists in drupal root vendor.
   * 
   * The module use php-cmis-client library but the current version 1.0
   * depend the guzzle 5 version. Drupal use the guzzle 6 version.
   * In order to we able using this client we need install it to module vendor
   * folder.
   *
   * To install temporary to time when will ready new version of cmis client
   * you need to go to cmis module root folder (eg. modules/cmis or 
   * modules/contrib/cmis) and call the next command:
   *    composer require dkd/php-cmis
   */
  public function checkClient() {
    // If not exists load it from cmis module vendor folder.
    if (!class_exists('BindingType')) {
      // Load CMIS using classes if composer not able to install them
      // to root vendor folder because guzzle 5 dependency.
      $path = drupal_get_path('module', 'cmis');
      if (file_exists($path . '/vendor/autoload.php')) {
        require_once($path . '/vendor/autoload.php');
      }
      else {
        throw new \Exception('Php CMIS Client library is not properly installed.');
      }
    }
  }

  /**
   * Set the configuration fom configuration id.
   *
   * @param string $config_id
   */
  private function setConfig($config_id) {
    $storage = \Drupal::entityTypeManager()->getStorage('cmis_connection_entity');
    if ($this->config = $storage->load($config_id)) {
      $this->setHttpInvoker();
    }
  }

  /**
   * Get configuration of this connection.
   *
   * @return type
   */
  public function getConfig() {
    return $this->config;
  }

  /**
   * Set Http invoker.
   */
  private function setHttpInvoker() {
    $guzzle = [
      'defaults' => [
        'auth' => [
          $this->config->getCmisUser(),
          $this->config->getCmisPassword(),
        ],
      ],
    ];

    $this->httpInvoker = new \GuzzleHttp\Client($guzzle);
  }

  /**
   * Get Http invoker.
   *
   * @return object
   */
  public function getHttpInvoker() {
    return $this->httpInvoker;
  }

  /**
   * Set default parameters.
   */
  public function setDefaultParameters() {
    $parameters = [
      SessionParameter::BINDING_TYPE => BindingType::BROWSER,
      SessionParameter::BROWSER_URL => $this->getConfig()->getCmisUrl(),
      SessionParameter::BROWSER_SUCCINCT => FALSE,
      SessionParameter::HTTP_INVOKER_OBJECT => $this->getHttpInvoker(),
    ];

    $this->setParameters($parameters);
  }

  /**
   * Set parameters.
   *
   * @param array $parameters
   */
  public function setParameters($parameters) {
    $this->parameters = $parameters;
    $this->setSessionFactory();
  }

  /**
   * Get parameters.
   *
   * @return array
   */
  public function getParameters() {
    return $this->parameters;
  }

  /**
   * Set session factory.
   */
  private function setSessionFactory() {
    $this->sessionFactory = new \Dkd\PhpCmis\SessionFactory();
    $this->setRepository();
  }

  /**
   * Get session factory.
   *
   * @return type
   */
  public function getSessionFactory() {
    return $this->sessionFactory;
  }

  /**
   * Set repository.
   */
  private function setRepository() {
    $repository_id = $this->config->getCmisRepository();
    // If no repository id is defined use the first repository
    if ($repository_id === NULL || $repository_id == '') {
      $repositories = $this->sessionFactory->getRepositories($this->parameters);
      $this->parameters[\Dkd\PhpCmis\SessionParameter::REPOSITORY_ID] = $repositories[0]->getId();
    }
    else {
      $this->parameters[\Dkd\PhpCmis\SessionParameter::REPOSITORY_ID] = $repository_id;
    }

    $this->session = $this->sessionFactory->createSession($this->parameters);
    $this->setRootFolder();
  }

  /**
   * Get session.
   *
   * @return object
   */
  public function getSession() {
    return $this->session;
  }

  /**
   * Set the root folder of the repository.
   */
  private function setRootFolder() {
    $this->rootFolder = $this->session->getRootFolder();
  }

  /**
   * Get root folder of repository.
   *
   * @return object
   */
  public function getRootFolder() {
    return $this->rootFolder;
  }

  /**
   * Get object by object id.
   *
   * @return object
   *    Return the current object or null.
   */
  public function getObjectById($id = '') {
    if (empty($id)) {
      return NULL;
    }

    if (!empty($this->validObjectId($id) || !empty($this->validObjectId($id, 'cmis:document')))) {
      $cid = $this->session->createObjectId($id);
      $object = $this->session->getObject($cid);

      return $object;
    }
    
    return NULL;
  }

  /**
   * Check the id is valid object.
   *
   * @param string $id
   * @param string $type
   *
   * @return object
   *     the result object or empty array
   */
  public function validObjectId($id, $type = 'cmis:folder') {
    $query = "SELECT * FROM $type WHERE cmis:objectId='$id'";
    $result = $this->session->query($query);

    return $result;
  }
  
  /**
   * Check the name is valid object.
   *
   * @param string $name
   * @param string $type
   *
   * @return object
   *     the result object or empty array
   */
  public function validObjectName($name, $type = 'cmis:folder') {
    $query = "SELECT * FROM $type WHERE cmis:name='$name'";
    $result = $this->session->query($query);

    return $result;
  }

}
