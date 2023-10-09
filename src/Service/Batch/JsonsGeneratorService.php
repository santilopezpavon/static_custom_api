<?php
namespace Drupal\static_custom_api\Service\Batch;

use Drupal\static_custom_api\Service\Core\AliasCache;
use Drupal\static_custom_api\Service\Core\FilesCache;

class JsonsGeneratorService {

  protected $aliasCache;
  protected $filesCache;

  /**
   * Constructor.
   */
  public function __construct(AliasCache $aliasCache, FilesCache $filesCache) {
    $this->aliasCache = $aliasCache;
    $this->filesCache = $filesCache;
  }

  public function executeBatchForDrush($batch) {
    if(is_array($batch) && array_key_exists("operations", $batch)) {
        $count = 0;
        $total = count($batch["operations"]);
        print_r("Num operations: " . $total);
        $show_process = 5;
    
        foreach ($batch["operations"] as $operation) {
            list($class_name, $method_name) = explode('::', $operation[0]);
            $result = call_user_func_array([$class_name, $method_name], $operation[1]);
            $count++;
            $process = ($count / $total) * 100;
            if($process > $show_process) {
                print_r("Process: " . $process);
                $show_process = $show_process + 5;
            }
        }
    }
  }


  /**
   * Generate JSONs for cacheable entity types.
   */
  public function generateBatchDataEntities() {
     // Get cacheable entity types.
     $entity_types_cacheable = $this->getCacheableEntityTypes();
 
     // Create a batch process.
     $batch = $this->prepareBatchArray("Creating Entities JSONs");
 
     // Add batch operations for each entity type and language.
     foreach ($entity_types_cacheable as $type_cacheable) {
       $entities = $this->getAllEntities($type_cacheable);
       foreach ($entities as $value) {
            $this->addBatchOperation($batch, $type_cacheable, $value, 'json');
       }
     }

     return $batch;
  }

  /**
   * Generate alias JSONs for cacheable entity types and languages.
   */
  public function generateBatchDataAliases() {
      // Get cacheable entity types.
      $entity_types_cacheable = $this->getCacheableEntityTypes();
      $languages_site = \Drupal::languageManager()->getLanguages();
  
      // Create a batch process.
      $batch = $this->prepareBatchArray("Creating alias JSONs");
  
      // Add batch operations for each entity type and language.
      foreach ($entity_types_cacheable as $type_cacheable) {
        $entities = $this->getAllEntities($type_cacheable);
        foreach ($entities as $value) {
          foreach ($languages_site as $languages_code => $languages_value) {
            $this->addBatchOperation($batch, $type_cacheable, $value, 'alias', $languages_code);
          }
        }
      }
      return $batch;
  }

  /**
   * Prepare a batch array.
   *
   * @param string $title
   *   The title for the batch process.
   *
   * @return array
   *   The batch array.
   */
  private function prepareBatchArray($title) {
    return  [
      'title' => $title . '...',
      'operations' => [],
      'init_message' => 'Starting...',
      'progress_message' => 'Processed @current out of @total.',
      'error_message' => 'An error occurred during processing.',
      'finished' => '\Drupal\static_custom_api\Batch\BatchJsonOperations::importFinished',
    ];
  }

  /**
   * Get cacheable entity types.
   *
   * @return array
   *   An array of cacheable entity types.
   */
  private function getCacheableEntityTypes() {
    $all_entity_types = \Drupal::entityTypeManager()->getDefinitions();
    $entity_types_cacheable = [];
    foreach ($all_entity_types as $entity_type => $value) {
      if ($this->filesCache->isEntityTypeJsonAble($entity_type)) {
        $entity_types_cacheable[] = $entity_type;
      }
    }
    return $entity_types_cacheable;
  }

  /**
   * Get all entities of a given entity type.
   *
   * @param string $type_entity
   *   The entity type.
   *
   * @return array
   *   An array of entity IDs.
   */
  private function getAllEntities($type_entity) {
    $entity_query_service = \Drupal::entityQuery($type_entity);
    $results = $entity_query_service->execute();
    return $results;
  }

  /**
   * Add a batch operation to the batch array.
   *
   * @param array $batch
   *   The batch array.
   * @param string $type_cacheable
   *   The cacheable entity type.
   * @param int $value
   *   The entity ID.
   * @param string $operation_type
   *   The type of operation ('json' or 'alias').
   * @param string|null $lang
   *   The language code for alias generation (only for 'alias' operation).
   */
  private function addBatchOperation(array &$batch, $type_cacheable, $value, $operation_type, $lang = NULL) {
    switch ($operation_type) {
      case 'json':
        $batch['operations'][] = [
          '\Drupal\static_custom_api\Batch\BatchJsonOperations::generateEntityFiles',
          [$type_cacheable, $value],
        ];
        break;

      case 'alias':
        $batch['operations'][] = [
          '\Drupal\static_custom_api\Batch\BatchJsonOperations::generateAliasFiles',
          [$type_cacheable, $value, $lang],
        ];
        break;
    }
  }

}
