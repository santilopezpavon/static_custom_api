<?php

namespace Drupal\static_custom_api\Service\Core;

use Drupal\Core\File\FileSystemInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\path_alias\AliasManager;
use Drupal\static_custom_api\Service\Core\FilesCache;

/**
 * Provides a service for managing and caching entity aliases.
 */
class AliasCache {

    /**
     * The base folder for storing alias cache files.
     *
     * @var string
     */
    private $base_folder_files;

    /**
     * The alias manager service.
     *
     * @var \Drupal\path_alias\AliasManager
     */
    private $alias_manager;

    /**
     * The file system service.
     *
     * @var \Drupal\Core\File\FileSystemInterface
     */
    private $fileSystem;

    /**
     * The custom files cache service.
     *
     * @var \Drupal\static_custom_api\Service\Core\FilesCache
     */
    private $filesCache;

    /**
     * Constructs an AliasCache object.
     *
     * @param \Drupal\path_alias\AliasManager $alias_manager
     *   The alias manager service.
     * @param \Drupal\Core\File\FileSystemInterface $fileSystem
     *   The file system service.
     * @param \Drupal\static_custom_api\Service\Core\FilesCache $filesCache
     *   The custom files cache service.
     */
    public function __construct(AliasManager $alias_manager, FileSystemInterface $fileSystem, FilesCache $filesCache) {
        $this->alias_manager = $alias_manager;
        $this->fileSystem = $fileSystem;
        $this->filesCache = $filesCache;
        $this->base_folder_files =  \Drupal::config("static_custom_api.settings")->get("directory") . '/alias';
    }

    /**
     * Saves an entity alias in the cache.
     *
     * @param string $entity_type
     *   The entity type.
     * @param int $id_entity
     *   The entity ID.
     * @param string $lang
     *   The language code.
     * @param string|null $current_alias
     *   The current alias, if any.
     *
     * @return null|string
     *   The saved alias or NULL if not saved.
     */
    public function saveAlias($entity_type, $id_entity, $lang, $current_alias = NULL) {
        $origin_url = $this->getOriginUrl($entity_type, $id_entity);
        $alias = $this->getAliasFromOriginUrl($origin_url, $lang);

        if ($current_alias !== NULL) {
            $alias = $current_alias;
        }

        if ($alias === NULL) {
            return NULL;
        }

        $directory = $this->base_folder_files . "/" . $lang . "" . $alias;
        $this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);

        $data_alias = [
            "entity_type" => $entity_type,
            "id_entity" => $id_entity,
            "lang" => $lang
        ];

        $data_alias_json = json_encode($data_alias);
        $this->fileSystem->saveData($data_alias_json, $directory . "/data.json", FileSystemInterface::EXISTS_REPLACE);
    
        return [
            "fileName" => $directory . "/data.json",
            "data" => $data_alias
        ];
    }

    /**
     * Retrieves the original URL for an entity.
     *
     * @param string $entity_type
     *   The entity type.
     * @param int $id_entity
     *   The entity ID.
     *
     * @return string
     *   The original URL.
     */
    public function getOriginUrl($entity_type, $id_entity) {
        return "/" . $entity_type . "/" . $id_entity;
    }

    /**
     * Retrieves an alias for a given URL and language.
     *
     * @param string $origin_url
     *   The original URL.
     * @param string $lang
     *   The language code.
     *
     * @return string|null
     *   The alias or NULL if not found.
     */
    public function getAliasFromOriginUrl($origin_url, $lang) {
        $alias = $this->alias_manager->getAliasByPath($origin_url, $lang);
        if ($origin_url !== $alias) {
            return $alias;
        } else {
            return NULL;
        }
    }

    /**
     * Retrieves entity cache data by alias and language code.
     *
     * @param string $alias
     *   The alias.
     * @param string $lang_code
     *   The language code.
     *
     * @return array|null
     *   The entity cache data or NULL if not found.
     */
    public function getEntityCacheByAlias($alias, $lang_code) {
        $real_path_alias = $this->fileSystem->realpath($this->base_folder_files . "/" . $lang_code . $alias . "/data.json");
        if (file_exists($real_path_alias)) {
            return json_decode(file_get_contents($real_path_alias), true);
        }
        $array_alias = explode("/", $alias);
        if (count($array_alias) === 3) {
            $files_cache = $this->filesCache->getPathFile([
                "target_type" => $array_alias[1],
                "id" => $array_alias[2],
                "lang" => $lang_code
            ]);

            if (file_exists($files_cache["real_path_file"])) {
                return  [
                    "entity_type" => $array_alias[1],
                    "id_entity" => $array_alias[2],
                    "lang" => $lang_code
                ];
            }

        }
        return NULL;
    }

    /**
     * Removes an alias by its alias and language code.
     *
     * @param string $alias
     *   The alias.
     * @param string $lang
     *   The language code.
     */
    public function removeAliasByAlias($alias, $lang) {
        $path_file_real = $this->fileSystem->realpath($this->base_folder_files . "/" . $lang . $alias);
        if (file_exists($path_file_real)) {
            $this->fileSystem->deleteRecursive($path_file_real);
        }

        return [
            "fileName" => $this->base_folder_files . "/" . $lang . $alias
        ];
    }  

    /**
     * Removes an alias by entity.
     *
     * @param mixed $entity
     *   The entity to remove the alias for.
     */
    public function removeAliasByEntity($entity) {
        if (!$this->filesCache->isEntityTypeJsonAble($entity->getEntityTypeId())) {
            return FALSE;
        } 
        $entity_data_for_json = $this->filesCache->getEntityDataForSaveJson($entity);
        
        $origin_url = $this->getOriginUrl($entity_data_for_json["target_type"], $entity_data_for_json["id"]);
        $alias = $this->getAliasFromOriginUrl($origin_url, $entity_data_for_json["lang"]);

        if ($alias === NULL) {
            return NULL;
        }      

        $path_file_real = $this->fileSystem->realpath($this->base_folder_files . "/" . $entity_data_for_json["lang"] . $alias . "/data.json");

        if (file_exists($path_file_real)) {
            $this->fileSystem->delete($path_file_real);
        } 
       
    }
}
