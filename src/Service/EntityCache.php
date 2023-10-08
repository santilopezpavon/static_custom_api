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

    protected $aliasCache;

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
    public function __construct(FilesCache $filesCache, Serializer $serializer, EntityTypeManager $entityManager, $aliasCache) {
        $this->filesCache = $filesCache;
        $this->serializer = $serializer;
        $this->entityManager = $entityManager;
        $this->aliasCache = $aliasCache;
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
    public function getEntityFromJSON($entity_type, $id, $lang, $recursive = TRUE, $maxDeep = 4, &$currentDeep = NULL) {
        $entity = $this->filesCache->getEntityFile($entity_type, $id, $lang);

        if($recursive === TRUE) {
            foreach ($entity as $field_name => &$value_field) {            
                foreach ($value_field as &$value) {
                    if (
                        is_array($value) && 
                        array_key_exists("target_type", $value) && 
                        $this->filesCache->isEntityTypeJsonAble($value["target_type"])
                    ) {
                       $value["entity"] = $this->getEntityFromJSON($value["target_type"], $value["target_id"], $lang, $recursive);
                    }
                }
            }
        }
       
        return $entity;
    }

    public function deleteEntityCache($entity) {
        $this->deleteAlias($entity);
        if (!$this->filesCache->isEntityTypeJsonAble($entity->getEntityTypeId())) {
            return FALSE;
        }   
        $this->filesCache->removeEntity($entity);        
    }

    public function updateEntityCache($entity) {
        $this->updateAlias($entity);
        if (!$this->filesCache->isEntityTypeJsonAble($entity->getEntityTypeId())) {
            return FALSE;
        }    

        $this->filesCache->saveEntity($entity);
    }

    public function deleteAlias($entity) {
        $info_alias = $this->getInfoAliasEntity($entity);
        if(empty($info_alias)) {
            return NULL;
        }

        $this->aliasCache->removeAliasByAlias($info_alias["alias"], $info_alias["lang"]);
        
        $storage = $this->entityManager->getStorage($info_alias["target_type"]);
        $entity_db = $storage->load($info_alias["target_id"]);
        if(!empty($entity_db) && method_exists($entity_db, "hasTranslation") && $entity_db->hasTranslation($info_alias["lang"])) {
            $entity_db = $entity_db->getTranslation($info_alias["lang"]);
            $this->filesCache->removeEntity($entity_db);
        }
        
    }

    public function updateAlias($entity) {
        $info_alias = $this->getInfoAliasEntity($entity);
        if(empty($info_alias)) {
            return NULL;
        }

        $entity_serialized = $this->filesCache->getEntityFile($info_alias["target_type"], $info_alias["target_id"], $info_alias["lang"]);
        $anterior_alias = $entity_serialized["alias_legacy"];  

        if($anterior_alias != $info_alias["alias"]) { // Update
            $this->aliasCache->removeAliasByAlias($anterior_alias, $info_alias["lang"]);

            $this->aliasCache->saveAlias(
                $info_alias["target_type"], 
                $info_alias["target_id"],
                $info_alias["lang"],
                $info_alias["alias"]
            ); 

            $storage = $this->entityManager->getStorage($info_alias["target_type"]);
            $entity_db = $storage->load($info_alias["target_id"]);
            if(method_exists($entity_db, "hasTranslation") && $entity_db->hasTranslation($info_alias["lang"])) {
                $entity_db = $entity_db->getTranslation($info_alias["lang"]);
            }
            $this->filesCache->saveEntity($entity_db);

            
        } else { // Create
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
