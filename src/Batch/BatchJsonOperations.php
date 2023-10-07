<?php
namespace Drupal\static_custom_api\Batch;

class BatchJsonOperations {

  
  /**
   * Handle batch completion.
   */
  public static function importFinished($success, $results, $operations) {
    $messenger = \Drupal::messenger();
    return 'The JSONs creation has completed.';
  }

  public static function generateAliasFiles($type_content, $id_content, $lang, &$context) {
    $alias_cache_service = \Drupal::service("static_custom_api.alias_cache");
    $alias_cache_service->saveAlias($type_content, $id_content, $lang);
  }

  /**
   * Process a single line.
   */
  public static function generateEntityFiles($type_content, $id_content, &$context) {
    $files_cache_service = \Drupal::service("static_custom_api.files_cache");

    $storage = \Drupal::entityTypeManager()->getStorage($type_content);
    $entity = $storage->load($id_content);

    if(
      !empty($entity) && $entity->isTranslatable() 
    ) {
      $trans_languages = $entity->getTranslationLanguages();
      foreach ($trans_languages as $trans_language) {
        $entity_trans = $entity->getTranslation($trans_language);
        $files_cache_service->saveEntity($entity_trans);
      }
    } elseif(!empty($entity)) {
      $files_cache_service->saveEntity($entity);
    }    
  }

}
