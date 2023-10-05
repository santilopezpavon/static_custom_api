<?php
namespace Drupal\static_custom_api\Service;

use Drupal\Core\File\FileSystemInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Custom service for storing and retrieving entities in JSON format.
 *
 * This service allows for saving and retrieving entities in JSON format to
 * improve the performance of the Drupal application. It is used for specific
 * entities that are marked as "cacheable."
 */
class FilesCache {

    private $base_folder_files = 'custom-build';
    private $entity_type_cached;
    
    /**
     * The File System service.
     *
     * @var \Drupal\Core\File\FileSystemInterface
     */
    private $fileSystem;

    /**
     * The Symfony Serializer service.
     *
     * @var \Symfony\Component\Serializer\Serializer
     */
    private $serializer;

    /**
     * Constructs a new FilesCache object.
     *
     * @param \Drupal\Core\File\FileSystemInterface $fileSystem
     *   The file system service.
     * @param \Symfony\Component\Serializer\Serializer $serializer
     *   The serializer service.
     */
    public function __construct(FileSystemInterface $fileSystem, Serializer $serializer) {
        $this->fileSystem = $fileSystem;
        $this->serializer = $serializer;
        $this->entity_type_cached = [];

        $config = \Drupal::config("static_custom_api.settings")->get("content_types_array");
        if(is_array($config)) {
            $this->entity_type_cached = \Drupal::config("static_custom_api.settings")->get("content_types_array");
        } 
    }   

    /**
     * Saves an entity in JSON format.
     *
     * @param object $entity
     *   The entity to be saved.
     *
     * @return bool
     *   TRUE if the entity was successfully saved, otherwise FALSE.
     */
    public function saveEntity($entity) {        
        if (!$this->isEntityTypeJsonAble($entity->getEntityTypeId())) {
            return FALSE;
        }     

        $entity_data_for_json = $this->getEntityDataForSaveJson($entity);
        
        $subfolder_directory = $this->getSubFolderEntityJson(
            $entity_data_for_json["target_type"],
            $entity_data_for_json["id"]
        ); 

        $this->prepareFolder($subfolder_directory);

        $path_file = $this->getPathFile($entity_data_for_json);        

        $this->saveEntityInJson($entity, $path_file["real_path_file"], $path_file["file_url"]);

        return TRUE;
    }

    /**
     * Checks if the entity type is cacheable.
     *
     * @param string $target_type
     *   The entity type to be checked.
     *
     * @return bool
     *   TRUE if the entity type is cacheable, otherwise FALSE.
     */
    public function isEntityTypeJsonAble($target_type) {
        return in_array($target_type, $this->entity_type_cached);
    }

    /**
     * Retrieves an entity in JSON format.
     *
     * @param string $target_type
     *   The entity type.
     * @param int|string $id
     *   The entity ID.
     * @param string $lang
     *   The language code.
     *
     * @return mixed
     *   The entity data in JSON format, or FALSE if not found.
     */
    public function getEntityFile($target_type, $id, $lang) {
        if (!in_array($target_type, $this->entity_type_cached)) {
            return FALSE;
        }

        $path_file = $this->getPathFile([
            "target_type" => $target_type,
            "id" => $id,
            "lang" => $lang
        ]);
        $path_file_real = $path_file["real_path_file"];
        
        if (file_exists($path_file_real)) {
            return json_decode(file_get_contents($path_file_real), true);
        } 

        return FALSE;
    }

    /**
     * Saves an entity in JSON format to a file.
     *
     * @param object $entity
     *   The entity to be saved.
     * @param string $file_path
     *   The file path to save the entity JSON.
     * @param string $file_url
     *   The file URL of the saved JSON.
     *
     * @throws \Exception
     *   Throws an exception if there is an error during the save operation.
     */
    private function saveEntityInJson($entity, $file_path, $file_url) {
        $json_entity = $this->serializer->serialize($entity, 'json', []);
        $json_entity = json_decode($json_entity, true);
        
        // Fix: Bug in serialize overrides Types.
        $json_entity["type_legacy"] = [
            "bundle" => $entity->bundle(),
            "type" => $entity->getEntityTypeId(),
            "id" => $entity->id()
        ];

        $json_entity = json_encode($json_entity);
        $this->fileSystem->saveData($json_entity, $file_path, FileSystemInterface::EXISTS_REPLACE); 
    }

    /**
     * Generates the file path and URL for the entity JSON.
     *
     * @param array $entity_data_for_json
     *   An array of entity data for generating the path and URL.
     *
     * @return array
     *   An array containing 'path_file', 'real_path_file', and 'file_url'.
     */
    public function getPathFile($entity_data_for_json) {
        $subfolder_directory = $this->getSubFolderEntityJson(
            $entity_data_for_json["target_type"],
            $entity_data_for_json["id"]
        );

        $file_name = $this->getFileName(
            $entity_data_for_json["target_type"],
            $entity_data_for_json["id"],
            $entity_data_for_json["lang"]
        );

        $path_file = $subfolder_directory . "" . $file_name;

        return [
            "path_file" => $path_file,
            "real_path_file" => $this->fileSystem->realpath($path_file),
            "file_url" => file_create_url($path_file)
        ];
    }

    /**
     * Prepares a folder for storing entity JSON files.
     *
     * @param string $folder
     *   The folder path to prepare.
     */
    private function prepareFolder($folder) {
        $this->fileSystem->prepareDirectory($folder, FileSystemInterface::CREATE_DIRECTORY);
    }

    /**
     * Retrieves entity data for saving in JSON format.
     *
     * @param object $entity
     *   The entity to retrieve data from.
     *
     * @return array
     *   An array of entity data.
     */
    private function getEntityDataForSaveJson($entity) {
        return [
            "target_type" => $entity->getEntityTypeId(),
            "bundle" => $entity->bundle(),
            "id" => $entity->id(),
            "lang" => $entity->language()->getId()
        ];
    }

    /**
     * Generates a filename for the entity JSON.
     *
     * @param string $target_type
     *   The entity type.
     * @param int $id
     *   The entity ID.
     * @param string $lang
     *   The language code.
     *
     * @return string
     *   The generated filename.
     */
    private function getFileName($target_type, $id, $lang = 'neutral') {
        return $target_type . "--" . $id . "--" . $lang . ".json";
    }

    /**
     * Generates the subfolder path for storing entity JSON based on the entity ID.
     *
     * @param string $target_type
     *   The entity type.
     * @param int|string $id
     *   The entity ID or string identifier.
     *
     * @return string
     *   The generated subfolder path.
     */
    private function getSubFolderEntityJson($target_type, $id) {
        if (is_numeric($id)) {
            $folder1 = number_format($id / 200, 0);
            $folder2 = number_format($folder1 / 200, 0);
            $folder3 = number_format($folder2 / 200, 0);
            return "public://custom-build" . "/" . $target_type . "/" . $folder1 . "/" . $folder2 . "/" . $folder3 . "/";
        } else {
            return "public://custom-build" . "/" . $target_type . "/";
        }    
    }
}
