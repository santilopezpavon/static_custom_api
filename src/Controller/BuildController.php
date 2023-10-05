<?php

namespace Drupal\static_custom_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\custom_api\Controller\ApiControllerBaseQueries;

/**
 * Clase controladora para el ejemplo de batch api.
 */
class BuildController extends ControllerBase {

  /**
   * Método que muestra un formulario para iniciar el proceso batch.
   *
   * @return array
   *   Un array renderizable con el formulario.
   */
  public function mostrarFormulario() {


    /* $entity_type = "node";
    $bundle = "article";
    dump(\Drupal::entityTypeManager()->getDefinitions());*/
/*
    $storage = \Drupal::entityTypeManager()->getStorage(
      "node"
    );
    $entity = $storage->load(1);
    $schema = \Drupal::service("custom_api.entity_control_fields_show")->generateContextEntity($entity, ["display"=> 'default']);
    $entity_json = \Drupal::service("custom_api.entity_normalize")->convertJson($entity, $schema);
    //\Drupal::service("custom_api.entity_control_fields_show");
*/

    $all_entity_types = \Drupal::entityTypeManager()->getDefinitions();
    $files_cache_service = \Drupal::service("static_custom_api.files_cache");
    $entity_types_cacheable = [];
    foreach ($all_entity_types as $entity_type => $value) {
      if($files_cache_service->isEntityTypeJsonAble($entity_type)) {
        $entity_types_cacheable[] = $entity_type;
      }
    }

    
    
    $batch = [
      'title'            => 'Importing CSV...',
      'operations'       => [],
      'init_message'     => 'Starting...',
      'progress_message' => 'Processed @current out of @total.',
      'error_message'    => 'An error occurred during processing',
      'finished'         => '\Drupal\static_custom_api\Batch\MyCustomBatch::importFinished',
    ];

    foreach ($entity_types_cacheable as $type_cacheable) {
      $query = \Drupal::entityQuery('paragraph');
      $results = $query->execute();
      foreach ($results as $key => $value) {
        $batch['operations'][] = [
          '\Drupal\static_custom_api\Batch\MyCustomBatch::importLine',
          [$type_cacheable, $value],
        ];
      }
    }

    
    batch_set($batch);
    return batch_process('user');
  }

}
