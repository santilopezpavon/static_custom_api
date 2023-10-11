<?php

namespace Drupal\static_custom_api\Service\LiveEntities;

use Drupal\static_custom_api\Service\Core\FilesCache;
use Symfony\Component\Serializer\Serializer;
use Drupal\Core\Entity\EntityTypeManager;

/**
 * Custom service for caching and retrieving entities.
 *
 * This service provides methods to retrieve entities either from the database or
 * from cached JSON files, and it handles nested entities as well.
 */
class EntityLiveAlter {
  
    /**
     * The FilesCache service.
     *
     * @var \Drupal\static_custom_api\Service\Core\FilesCache
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

    protected $aliasCache;

    /**
     * Constructs an EntityCache object.
     *
     * @param \Drupal\static_custom_api\Service\Core\FilesCache $filesCache
     *   The FilesCache service.
     * @param \Symfony\Component\Serializer\Serializer $serializer
     *   The Symfony Serializer service.
     * @param \Drupal\Core\Entity\EntityTypeManager $entityManager
     *   The EntityTypeManager service.
     */
    public function __construct(FilesCache $filesCache, Serializer $serializer, EntityTypeManager $entityManager, $aliasCache) {
        $this->filesCache = $filesCache;
        $this->serializer = $serializer;
        $this->entityManager = $entityManager;
        $this->aliasCache = $aliasCache;
    }

    public function deleteEntityCache($entity) {
        $this->deleteAlias($entity);
        if (!$this->filesCache->isEntityTypeJsonAble($entity->getEntityTypeId())) {
            return FALSE;
        }   
        // Delete Entity
        $data_remove = $this->filesCache->removeEntity($entity);  
        \Drupal::service("module_handler")->invokeAll('static_custom_api_remove', [&$data_remove]);      
    }

    public function updateEntityCache($entity) {
        $this->updateAlias($entity);
        if (!$this->filesCache->isEntityTypeJsonAble($entity->getEntityTypeId())) {
            return FALSE;
        }    
        // Create or update entity
        $data_save = $this->filesCache->saveEntity($entity);
        \Drupal::service("module_handler")->invokeAll('static_custom_api_save', [&$data_save]); 
    }

    public function deleteAlias($entity) {
        $info_alias = $this->getInfoAliasEntity($entity);
        if(empty($info_alias)) {
            return NULL;
        }

        // Remove Alias.
        $data_remove = $this->aliasCache->removeAliasByAlias($info_alias["alias"], $info_alias["lang"]);
        \Drupal::service("module_handler")->invokeAll('static_custom_api_remove', [&$data_remove]);   
        // Remove Entity in Current Lang. Todo: Revisar porque la entidad ya está eliminada.
        /*$storage = $this->entityManager->getStorage($info_alias["target_type"]);
        $entity_db = $storage->load($info_alias["target_id"]);
        if(!empty($entity_db) && method_exists($entity_db, "hasTranslation") && $entity_db->hasTranslation($info_alias["lang"])) {
            $entity_db = $entity_db->getTranslation($info_alias["lang"]);
            $this->filesCache->removeEntity($entity_db);
        }*/
        
    }

    public function updateAlias($entity) {
        $info_alias = $this->getInfoAliasEntity($entity);
        if(empty($info_alias)) {
            return NULL;
        }

        $entity_serialized = $this->filesCache->getEntityFile($info_alias["target_type"], $info_alias["target_id"], $info_alias["lang"]);
        $anterior_alias = $entity_serialized["alias_legacy"];  

        // Update Alias
        if($anterior_alias != $info_alias["alias"]) { // Update

            // Remove Alias
            $data_remove = $this->aliasCache->removeAliasByAlias($anterior_alias, $info_alias["lang"]);
            \Drupal::service("module_handler")->invokeAll('static_custom_api_remove', [&$data_remove]);  

            // Create Alias
            $data_save = $this->aliasCache->saveAlias(
                $info_alias["target_type"], 
                $info_alias["target_id"],
                $info_alias["lang"],
                $info_alias["alias"]
            ); 
            \Drupal::service("module_handler")->invokeAll('static_custom_api_save', [&$data_save]);

            // Update Entity
            $storage = $this->entityManager->getStorage($info_alias["target_type"]);
            $entity_db = $storage->load($info_alias["target_id"]);
            if(method_exists($entity_db, "hasTranslation") && $entity_db->hasTranslation($info_alias["lang"])) {
                $entity_db = $entity_db->getTranslation($info_alias["lang"]);
            }
            $data_save_2 = $this->filesCache->saveEntity($entity_db);

            \Drupal::service("module_handler")->invokeAll('static_custom_api_save', [&$data_save_2]);

            
        } else { 
            // Create alias
            $this->aliasCache->saveAlias(
                $info_alias["target_type"], 
                $info_alias["target_id"],
                $info_alias["lang"],
                $info_alias["alias"]
            ); 
        }


        // removeAliasByAlias($alias, $lang)
    }

    public function getInfoAliasEntity($entity) {
        if($entity->getEntityTypeId() !== 'path_alias') {
            return NULL;
        }

        $alias = $entity->getAlias();
        $source_path = $entity->getPath();

        $array_explode = explode("/", $source_path);

        return [
            "alias" => $alias,
            "source_path" => $source_path,
            "lang" => $entity->language()->getId(),
            "target_type" => $array_explode[1],
            "target_id" => $array_explode[2]
        ];

    }
}
