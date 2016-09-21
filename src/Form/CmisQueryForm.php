<?php

namespace Drupal\cmis\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

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
    $configuration_options = cmis_get_configurations();
    unset($configuration_options['_none']);
    $first_config = reset($configuration_options);
    $config = $form_state->getValue('config');

    $form['config'] = array(
      '#type' => 'select',
      '#title' => $this->t('Configuration'),
      '#description' => $this->t('Select the configuration for repository.'),
      '#options' => $configuration_options,
      '#default_value' => !empty($config) ? $config : key($first_config),
    );

    $form['query_string'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Query string'),
      '#description' => $this->t('Enter a valid CMIS query.'),
    );

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('send'),
      '#ajax' => array(
        'callback' => '::ajaxGetResult',
        'wrapper' => 'query-result-wrapper',
      ),
    ];

    $result = '';
    $input = $form_state->getUserInput();
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

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

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
      $content = $this->prepareResult($results);
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
  private function prepareResult($results) {
    $content = '';
    $rows = [];
    $header =  [];
    foreach ($results as $result) {
      $row = [];
      foreach ($result->getProperties() as $property) {
        $key = $property->getId();
        if (!in_array($key, $header)) {
          $header[] = $key;
        }
        $row[] = _cmis_get_property($key, $property);
      }
      $rows[] = $row;
    }
    
    if (!empty($rows)) {
      $table = [
        '#theme' => 'table',
        '#header' => $header,
        '#rows' => $rows,
      ];
      
      $content = render($table);
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
