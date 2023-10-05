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

    return $form;
  }

  /**
   * Submit handler for Action 1.
   */
  public function action1Submit(array &$form, FormStateInterface $form_state) {
    // Get the required services.
    $files_cache_service = \Drupal::service("static_custom_api.files_cache");
    $entity_query_service = \Drupal::entityQuery('paragraph');

    // Get cacheable entity types.
    $entity_types_cacheable = $this->getCacheableEntityTypes($files_cache_service);

    // Create a batch process.
    $batch = [
      'title' => $this->t('Importing JSONs...'),
      'operations' => [],
      'init_message' => $this->t('Starting...'),
      'progress_message' => $this->t('Processed @current out of @total.'),
      'error_message' => $this->t('An error occurred during processing.'),
      'finished' => '\Drupal\static_custom_api\Batch\BatchJsonOperations::importFinished',
    ];

    // Get paragraph entities.
    $paragraph_entities = $this->getParagraphEntities($entity_query_service);

    // Add batch operations for each paragraph entity.
    foreach ($entity_types_cacheable as $type_cacheable) {
      foreach ($paragraph_entities as $value) {
        $this->addBatchOperation($batch, $type_cacheable, $value);
      }
    }

    // Start the batch process.
    batch_set($batch);
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
  private function getParagraphEntities($entity_query_service) {
    $results = $entity_query_service->execute();
    return $results;
  }

  /**
   * Helper function to add a batch operation.
   */
  private function addBatchOperation(&$batch, $type_cacheable, $value) {
    $batch['operations'][] = [
      '\Drupal\static_custom_api\Batch\BatchJsonOperations::importLine',
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
