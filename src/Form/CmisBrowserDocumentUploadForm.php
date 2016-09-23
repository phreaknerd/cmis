<?php

namespace Drupal\cmis\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class CmisBrowserDocumentUploadForm.
 *
 * @package Drupal\cmis\Form
 */
class CmisBrowserDocumentUploadForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cmis_browser_document_upload_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $params = \Drupal::routeMatch();
    $directory = file_directory_temp();
    $directory_is_writable = is_writable($directory);
    if (!$directory_is_writable) {
      drupal_set_message($this->t('The directory %directory is not writable.', ['%directory' => $directory]), 'error');
    }
    $form['local_file'] = array(
      '#type' => 'file',
      '#title' => $this->t('Local file'),
      '#description' => $this->t('Choose the local file to uploading'),
    );

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Document description'),
      '#description' => $this->t('Enter the document description'),
      '#default_value' => $form_state->getValue('description'),
    ];

    $form['config'] = [
      '#type' => 'hidden',
      '#default_value' => $params->getParameter('config'),
    ];

    $form['folder_id'] = [
      '#type' => 'hidden',
      '#default_value' => $params->getParameter('folder_id'),
    ];

    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Upload'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $form_state->setRedirect(
        'cmis.cmis_repository_controller_browser', ['config' => $values['config'], 'folder_id' => $values['folder_id']]
    );
    $directory = file_directory_temp();

    $filename = $directory . '/' . $_FILES['files']['name']['local_file'];
    if (!is_uploaded_file($_FILES['files']['tmp_name']['local_file']) || !copy($_FILES['files']['tmp_name']['local_file'], $filename)) {
      // Can't create file.
      drupal_set_message($this->t("File can't uploaded."), 'warning');
      return;
    }

    // Open repository.
    if ($repository = new \Drupal\cmis\Controller\CmisRepositoryController($values['config'], $values['folder_id'])) {
      if (!empty($repository->getBrowser()->getConnection()->validObjectName($_FILES['files']['name']['local_file'], 'cmis:document', $values['folder_id']))) {
        // Document exists. Delete file from local.
        unlink($filename);
        drupal_set_message($this->t("The document name @name exists in folder.", ['@name' => $_FILES['files']['name']['local_file']]), 'warning');
        return;
      }

      $session = $repository->getBrowser()->getConnection()->getSession();
      $properties = array(
        \Dkd\PhpCmis\PropertyIds::OBJECT_TYPE_ID => 'cmis:document',
        \Dkd\PhpCmis\PropertyIds::NAME => $_FILES['files']['name']['local_file'],
      );
      if (!empty($values['description'])) {
        $properties[\Dkd\PhpCmis\PropertyIds::DESCRIPTION] = $values['description'];
      }

      // Create document.
      try {
        $document = $session->createDocument(
            $properties, $session->createObjectId($values['folder_id']), \GuzzleHttp\Stream\Stream::factory(fopen($filename, 'r'))
        );
        // Delete file from local.
        unlink($filename);
        drupal_set_message($this->t("Document name @name has been created.", ['@name' => $_FILES['files']['name']['local_file']]));
      }
      catch (Exception $exc) {
        drupal_set_message($this->t("Document name @name couldn't create.", ['@name' => $_FILES['files']['name']['local_file']]), 'warning');
      }
    }
  }

}
