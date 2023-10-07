<?php
namespace Drupal\static_custom_api\Service;

use Drupal\Core\File\FileSystemInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;


class AliasCache {
    
    private $base_folder_files = 'public://custom-build/alias';

    private $alias_manager;   
    
    private $fileSystem;

    private $filesCache;

    public function __construct() {
        $this->alias_manager = \Drupal::service('path_alias.manager');
        $this->fileSystem = \Drupal::service('file_system');
        $this->filesCache = \Drupal::service('static_custom_api.files_cache');
    } 

    public function saveAlias($entity_type, $id_entity, $lang) {
        $origin_url = $this->getOriginUrl($entity_type, $id_entity);
        $alias = $this->getAliasFromOriginUrl($origin_url, $lang);
        if($alias === NULL) {
            return NULL;
        }
        $url = Url::fromUri('internal:' . $origin_url);
       

        $directory = $this->base_folder_files . "/" . $lang . "" . $alias;
        $this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);

        $data_alias = [
            "entity_type" => $entity_type,
            "id_entity" => $id_entity,
            "lang" => $lang
        ];

        $data_alias_json = json_encode($data_alias);
        $this->fileSystem->saveData($data_alias_json, $directory . "/data.json", FileSystemInterface::EXISTS_REPLACE); 
        

    }

    public function getOriginUrl($entity_type, $id_entity) {
        return "/" . $entity_type . "/" . $id_entity;
    }

    public function getAliasFromOriginUrl($origin_url, $lang) {
        $alias = $this->alias_manager->getAliasByPath($origin_url, $lang);
        if($origin_url !== $alias) {
            return $alias;
        } else {
            return NULL;
        }
    }

    public function getEntityCacheByAlias($alias, $lang_code) {
        $real_path_alias = $this->fileSystem->realpath($this->base_folder_files . "/" . $lang_code . $alias . "/data.json");
        if(file_exists($real_path_alias)) {
            return json_decode(file_get_contents($real_path_alias), true);
        }
        $array_alias = explode("/", $alias);
        if(count($array_alias) === 3) {
            $files_cache = $this->filesCache->getPathFile([
                "target_type" => $array_alias[1],
                "id" => $array_alias[2],
                "lang" => $lang_code
            ]);

            if(file_exists($files_cache["real_path_file"])) {
                return  [
                    "entity_type" =>$array_alias[1],
                    "id_entity" => $array_alias[2],
                    "lang" => $lang_code
                ];
            }

        }
        return NULL;
    }
}