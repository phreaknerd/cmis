<?php

namespace Drupal\cmis\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\cmis\CmisElement;

/**
 * Class CmisQueryForm.
 *
 * @package Drupal\cmis\Form
 */
class CmisQueryForm extends FormBase {

  /**
   * Configuration.
   *
   * @var string
   *    the configuration id
   */
  protected $config;

  /**
   * Connection.
   *
   * @var object
   *    the connection object
   */
  protected $connection;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cmis_query_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $parameters = \Drupal::request()->query->all();
    unset($parameters['type']);
    $configuration_options = cmis_get_configurations();
    unset($configuration_options['_none']);
    $first_config = reset($configuration_options);
    $input = $form_state->getUserInput();
    $user_inputs = array_merge($parameters, $input);
    if (!empty($user_inputs)) {
      $form_state->setUserInput($user_inputs);
    }
    $input = $user_inputs;

    $form['config'] = array(
      '#type' => 'select',
      '#title' => $this->t('Configuration'),
      '#description' => $this->t('Select the configuration for repository.'),
      '#options' => $configuration_options,
      '#default_value' => !empty($input['config']) ? $input['config'] : key($first_config),
    );

    $form['query_string'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Query string'),
      '#description' => $this->t('Enter a valid CMIS query.'),
      '#default_value' => !empty($input['query_string']) ? $input['query_string'] : '',
    );

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Run'),
      '#ajax' => array(
        'callback' => '::ajaxGetResult',
        'wrapper' => 'query-result-wrapper',
      ),
    ];

    $result = '';
    if (!empty($input['query_string']) &&
        !empty($input['config'])) {
      $this->config = $input['config'];
      if (empty($this->connection)) {
        $this->connection = new \Drupal\cmis\CmisConnectionApi($this->config);
      }
      if (!empty($this->connection->getHttpInvoker())) {
        $result = $this->queryExec($this->config, $input['query_string']);
      }
    }

    $form['result'] = [
      '#markup' => $result,
      '#prefix' => '<div id="query-result-wrapper">',
      '#suffix' => '</div>',
    ];

    $form['#attached'] = [
      'library' => [
        'cmis/cmis-query',
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // No submit handling here.
  }

  /**
   * Execute query string.
   *
   * @param string $config
   * @param string $query
   *
   * @return string
   */
  public function queryExec($config = '', $query = '') {
    $content = '';
    if (empty($config)) {
      if (!empty($this->config)) {
        $config = $this->config;
      }
      else {
        return $content;
      }
    }

    if (!empty($query)) {
      $this->connection->setDefaultParameters();
      $session = $this->connection->getSession();
      $results = $session->query($query);
      $content = $this->prepareResult($results, $query);
    }

    return $content;
  }

  /**
   * Prepare results to rendered table.
   *
   * @param array $results
   *
   * @return string
   */
  private function prepareResult($results, $query) {
    $content = '';
    $rows = [];
    $table_header = array(
      t('Name'),
      t('Details'),
      t('Author'),
      t('Created'),
      t('Description'),
      t('Operation'),
    );
    $root = $this->connection->getRootFolder();
    $element = new CmisElement($this->config, FALSE, NULL, $query, $root->getId());
    if ($session = $this->connection->getSession()) {
      foreach ($results as $result) {
        $id = $result->getPropertyValueById('cmis:objectId');
        $cid = $session->createObjectId($id);
        if ($object = $session->getObject($cid)) {
          $element->setElement('query', $object);
          $rows[] = $element->getData();
        }
      }

      if (!empty($rows)) {
        $table = [
          '#theme' => 'cmis_browser',
          '#header' => $table_header,
          '#elements' => $rows,
        ];

        $content = render($table);
      }
    }

    return $content;
  }

  /**
   * Submit button ajax callback.
   *
   * @param array $form
   * @param FormStateInterface $form_state
   *
   * @return array
   */
  public function ajaxGetResult(array &$form, FormStateInterface $form_state) {
    return $form['result'];
  }

}
