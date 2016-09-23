<?php

namespace Drupal\cmis\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class CmisCreateFolder.
 *
 * @package Drupal\cmis\Form
 */
class CmisCreateFolderForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cmis_create_folder';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $params = \Drupal::routeMatch();

    $form['folder_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Folder name'),
      '#description' => $this->t('Enter the new folder name'),
      '#maxlength' => 255,
      '#size' => 64,
      '#required' => TRUE,
    ];

    $form['folder_description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Folder description'),
      '#description' => $this->t('Enter the folder description'),
    ];

    $form['config'] = [
      '#type' => 'hidden',
      '#default_value' => $params->getParameter('config'),
    ];

    $form['folder_id'] = [
      '#type' => 'hidden',
      '#default_value' => $params->getParameter('folder_id'),
    ];

    $form['operation']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Create folder'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if (!empty($values['folder_name'])) {
      $repository = new \Drupal\cmis\Controller\CmisRepositoryController($values['config'], $values['folder_id']);
      if (!empty($repository->getBrowser()->getConnection()->validObjectName($values['folder_name']))) {
        drupal_set_message($this->t("The folder name @folder_name exists in folder.", ['@folder_name' => $values['folder_name']]), 'warning');
        $form_state->setRedirect(
            'cmis.cmis_repository_controller_browser', ['config' => $values['config'], 'folder_id' => $values['folder_id']]
        );
        return;
      }
      $session = $repository->getBrowser()->getConnection()->getSession();
      $properties = array(
        \Dkd\PhpCmis\PropertyIds::OBJECT_TYPE_ID => 'cmis:folder',
        \Dkd\PhpCmis\PropertyIds::NAME => $values['folder_name'],
      );
      if (!empty($values['folder_description'])) {
        $properties[\Dkd\PhpCmis\PropertyIds::DESCRIPTION] = $values['folder_description'];
      }
      $session->createFolder(
          $properties, $session->createObjectId($values['folder_id'])
      );
    }

    $form_state->setRedirect(
        'cmis.cmis_repository_controller_browser', ['config' => $values['config'], 'folder_id' => $values['folder_id']]
    );
  }

}
