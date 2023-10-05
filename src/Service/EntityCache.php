<?php

namespace Drupal\static_custom_api\Service;

use Drupal\static_custom_api\Service\FilesCache;
use Symfony\Component\Serializer\Serializer;
use Drupal\Core\Entity\EntityTypeManager;

/**
 * Custom service for caching and retrieving entities.
 *
 * This service provides methods to retrieve entities either from the database or
 * from cached JSON files, and it handles nested entities as well.
 */
class EntityCache {
  
    /**
     * The FilesCache service.
     *
     * @var \Drupal\static_custom_api\Service\FilesCache
     */
    protected $filesCache;

    /**
     * The Symfony Serializer service.
     *
     * @var \Symfony\Component\Serializer\Serializer
     */
    protected $serializer;

    /**
     * The EntityTypeManager service.
     *
     * @var \Drupal\Core\Entity\EntityTypeManager
     */
    protected $entityManager;

    /**
     * Constructs an EntityCache object.
     *
     * @param \Drupal\static_custom_api\Service\FilesCache $filesCache
     *   The FilesCache service.
     * @param \Symfony\Component\Serializer\Serializer $serializer
     *   The Symfony Serializer service.
     * @param \Drupal\Core\Entity\EntityTypeManager $entityManager
     *   The EntityTypeManager service.
     */
    public function __construct(FilesCache $filesCache, Serializer $serializer, EntityTypeManager $entityManager) {
        $this->filesCache = $filesCache;
        $this->serializer = $serializer;
        $this->entityManager = $entityManager;
    } 

    /**
     * Retrieves an entity from the database.
     *
     * @param string $entity_type
     *   The entity type.
     * @param int $id
     *   The entity ID.
     * @param string $lang
     *   The language code.
     *
     * @return array|false
     *   The entity data as an array or FALSE if not found.
     */
    public function getEntityFromDatabase($entity_type, $id, $lang) {
        $storage = $this->entityManager->getStorage($entity_type);
        $entity_db = $storage->load($id);
        
        if (
            !empty($entity_db) && 
            method_exists($entity_db, "hasTranslation") &&
            $entity_db->hasTranslation($lang)
        ) {
            $entity_db = $entity_db->getTranslation($lang);
        }
        $json_entity = $this->serializer->serialize($entity_db, 'json', []);
        $entity = json_decode($json_entity, true);
        foreach ($entity as $field_name => &$value_field) {            
            foreach ($value_field as &$value) {
                if (
                    is_array($value) &&
                    array_key_exists("target_type", $value) && 
                    $this->filesCache->isEntityTypeJsonAble($value["target_type"])
                ) {
                    $value["entity"] = $this->getEntityFromDatabase($value["target_type"], $value["target_id"], $lang);
                }
            }
        }
        return $entity;
    }

    /**
     * Retrieves an entity from cached JSON.
     *
     * @param string $entity_type
     *   The entity type.
     * @param int $id
     *   The entity ID.
     * @param string $lang
     *   The language code.
     *
     * @return array|false
     *   The entity data as an array or FALSE if not found.
     */
    public function getEntityFromJSON($entity_type, $id, $lang) {
        $entity = $this->filesCache->getEntityFile($entity_type, $id, $lang);

        foreach ($entity as $field_name => &$value_field) {            
            foreach ($value_field as &$value) {
                if (
                    is_array($value) && 
                    array_key_exists("target_type", $value) && 
                    $this->filesCache->isEntityTypeJsonAble($value["target_type"])
                ) {
                   $value["entity"] = $this->getEntityFromJSON($value["target_type"], $value["target_id"], $lang);
                }
            }
        }
        return $entity;
    }
}
