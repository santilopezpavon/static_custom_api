<?php

namespace Drupal\static_custom_api\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form class for actions example.
 */
class ActionsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'static_custom_api.actions_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Create a message element for displaying messages.
    $form['message'] = [
      '#markup' => '<div id="message"></div>',
    ];

    // Create a submit button for Action 1.
    $form['action1'] = [
      '#type' => 'submit',
      '#value' => $this->t('Generate All JSONs'),
      '#submit' => ['::action1Submit'],
    ];

    $form['action2'] = [
      '#type' => 'submit',
      '#value' => $this->t('Generate All Alias'),
      '#submit' => ['::action2Submit'],
    ];

    return $form;
  }

  /**
   * Submit handler for Action 1.
   */
  public function action1Submit(array &$form, FormStateInterface $form_state) {
    // Get the required services.
    $files_cache_service = \Drupal::service("static_custom_api.files_cache");

    // Get cacheable entity types.
    $entity_types_cacheable = $this->getCacheableEntityTypes($files_cache_service);

    // Create a batch process.
    $batch = $this->prepareBatchArray("Creating entities JSONs");

    // Add batch operations for each paragraph entity.
    foreach ($entity_types_cacheable as $type_cacheable) {
      $entities = $this->getAllEntities($type_cacheable);
      foreach ($entities as $value) {
        $this->addBatchOperation($batch, $type_cacheable, $value);
      }
    }
   

    // Start the batch process.
    batch_set($batch);
  }

  public function action2Submit(array &$form, FormStateInterface $form_state) {
    // Get the required services.
    $files_cache_service = \Drupal::service("static_custom_api.files_cache");

    // Get cacheable entity types.
    $entity_types_cacheable = $this->getCacheableEntityTypes($files_cache_service);
    $languages_site =  \Drupal::languageManager()->getLanguages();

    // Create a batch process.
    $batch = $this->prepareBatchArray("Creating alias JSONs");
  

    // Add batch operations for each paragraph entity.
    foreach ($entity_types_cacheable as $type_cacheable) {
      $entities = $this->getAllEntities($type_cacheable);
      foreach ($entities as $value) {
        foreach ($languages_site as $languages_code => $languages_value) {
          $this->addBatchOperationAlias($batch, $type_cacheable, $value, $languages_code);
        }
      }
    }
   

    // Start the batch process.
    batch_set($batch);
  }


  private function prepareBatchArray($title) {
    return  [
      'title' => $title . "...",
      'operations' => [],
      'init_message' => $this->t('Starting...'),
      'progress_message' => $this->t('Processed @current out of @total.'),
      'error_message' => $this->t('An error occurred during processing.'),
      'finished' => '\Drupal\static_custom_api\Batch\BatchJsonOperations::importFinished',
    ];

  }


  /**
   * Helper function to get cacheable entity types.
   */
  private function getCacheableEntityTypes($files_cache_service) {
    $all_entity_types = \Drupal::entityTypeManager()->getDefinitions();
    $entity_types_cacheable = [];
    foreach ($all_entity_types as $entity_type => $value) {
      if ($files_cache_service->isEntityTypeJsonAble($entity_type)) {
        $entity_types_cacheable[] = $entity_type;
      }
    }
    return $entity_types_cacheable;
  }

  /**
   * Helper function to get paragraph entities.
   */
  private function getAllEntities($type_entity) {
    $entity_query_service = \Drupal::entityQuery($type_entity);
    $results = $entity_query_service->execute();
    return $results;
  }

  private function addBatchOperationAlias(&$batch, $type_cacheable, $value, $lang) {
    $batch['operations'][] = [
      '\Drupal\static_custom_api\Batch\BatchJsonOperations::generateAliasFiles',
      [$type_cacheable, $value, $lang],
    ];
  }

  /**
   * Helper function to add a batch operation.
   */
  private function addBatchOperation(&$batch, $type_cacheable, $value) {
    $batch['operations'][] = [
      '\Drupal\static_custom_api\Batch\BatchJsonOperations::generateEntityFiles',
      [$type_cacheable, $value],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // No additional form submission code is needed in this example.
  }
}
