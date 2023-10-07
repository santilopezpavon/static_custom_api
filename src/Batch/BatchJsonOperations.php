<?php

namespace Drupal\static_custom_api\Batch;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\static_custom_api\Service\AliasCache;
use Drupal\static_custom_api\Service\FilesCache;

class BatchJsonOperations implements ContainerInjectionInterface {

  protected static $aliasCache;
  protected static $filesCache;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Get the 'static_custom_api.alias_cache' and 'static_custom_api.files_cache' services.
    static::$aliasCache = $container->get('static_custom_api.alias_cache');
    static::$filesCache = $container->get('static_custom_api.files_cache');
    
    // Check if necessary services are available.
    if (!static::$aliasCache || !static::$filesCache) {
      throw new \RuntimeException('Failed to load necessary services.');
    }
    
    // Return a new instance of this class.
    return new static();
  }

  /**
   * Handle batch completion.
   *
   * @param bool $success
   *   Whether the batch operation was successful.
   * @param array $results
   *   Results of batch operations.
   * @param array $operations
   *   Batch operations to be processed.
   */
  public static function importFinished($success, $results, $operations) {
    $messenger = \Drupal::messenger();
    if ($success) {
      // Display a success message to the user.
      $messenger->addMessage('The JSONs creation has completed successfully.');
    } else {
      // Display an error message to the user.
      $messenger->addError('The JSONs creation encountered errors.');
    }
  }

  /**
   * Generate alias files for a content type.
   *
   * @param string $type_content
   *   The content type.
   * @param int $id_content
   *   The content ID.
   * @param string $lang
   *   The language code.
   * @param array $context
   *   The batch context.
   */
  public static function generateAliasFiles($type_content, $id_content, $lang, &$context = []) {
    $aliasCache = \Drupal::service("static_custom_api.alias_cache");
      // Generate alias files for the given content type, ID, and language.
      $aliasCache->saveAlias($type_content, $id_content, $lang);
  }

  /**
   * Generate entity files for a content type.
   *
   * @param string $type_content
   *   The content type.
   * @param int $id_content
   *   The content ID.
   * @param array $context
   *   The batch context.
   */
  public static function generateEntityFiles($type_content, $id_content, &$context = []) {
    $filesCache = \Drupal::service("static_custom_api.files_cache");
      // Get the entity storage for the given content type.
      $storage = \Drupal::entityTypeManager()->getStorage($type_content);
      $entity = $storage->load($id_content);

      if (!empty($entity) && method_exists($entity, "isTranslatable") && $entity->isTranslatable()) {
        $trans_languages = $entity->getTranslationLanguages();
        foreach ($trans_languages as $lang_code_trans => $trans_language) {
          // Generate entity files for each translation of the entity.
          if($entity->hasTranslation($lang_code_trans)) {
            $entity_trans = $entity->getTranslation($lang_code_trans);
            $filesCache->saveEntity($entity_trans);  
          }
        }
      } elseif (!empty($entity)) {
        // Generate entity files for the default language.
        $filesCache->saveEntity($entity);
      }
  }
}
