<?php

namespace Drupal\static_custom_api\Service\Sync;

/**
 * Defines the SyncFront class.
 *
 * This class handles synchronization with a frontend system.
 *
 * @package Drupal\static_custom_api\Service\Sync
 */
class SyncFront {

    /**
     * The API endpoint for synchronization.
     *
     * @var string
     */
    private $end_point;

    /**
     * The HTTP client for making API requests.
     *
     * @var \GuzzleHttp\ClientInterface
     */
    private $client;

    /**
     * Flag to indicate whether synchronization is enabled.
     *
     * @var bool
     */
    private $doSync = FALSE;

    /**
     * The base folder for files.
     *
     * @var string
     */
    private $base_folder;

    /**
     * The limit for error log messages.
     *
     * @var int
     */
    private $errorLogLimit;

    /**
     * Constructs a new SyncFront object.
     *
     * @param \GuzzleHttp\ClientInterface $http_client
     *   The HTTP client for making API requests.
     * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
     *   The configuration factory for retrieving settings.
     */
    public function __construct($http_client, $config_factory) {
        $this->client = $http_client;  
        $config = $config_factory->get('static_custom_api.settings');
        $this->end_point =  $config->get("url_front"); 
        $this->doSync = $config->get("url_front_bool"); 
        $this->base_folder = $config->get("directory"); 
        $this->errorLogLimit = 255; // Limit for error log messages.
    }

    /**
     * Deletes a file from the frontend system.
     *
     * @param string $file
     *   The file to be deleted.
     *
     * @return mixed
     *   The result of the deletion operation.
     */
    public function deleteFile($file) {
        if (!$this->doSync) {
            return FALSE;
        }
        try {
            $request = $this->client->delete($this->end_point, [
                'json' => [
                  'fileName' => $this->processFileName($file)
                ]
            ]);
            return json_decode($request->getBody());
        } catch (\Throwable $th) {
            \Drupal::logger("deleteFile")->error(substr($th->getMessage(), 0, $this->errorLogLimit));
        }
        return FALSE;
    }

    /**
     * Updates a file on the frontend system.
     *
     * @param string $file
     *   The file to be updated.
     * @param mixed $fileData
     *   The data to update the file.
     *
     * @return mixed
     *   The result of the update operation.
     */
    public function updateFile($file, $fileData) {
        if (!$this->doSync) {
            return FALSE;
        }
        try {
            $request = $this->client->post($this->end_point, [
                'json' => [
                  'fileName' => $this->processFileName($file),
                  "data" => $fileData
                ]
            ]);
            return json_decode($request->getBody());
        } catch (\Throwable $th) {
            \Drupal::logger("updateFile")->error(substr($th->getMessage(), 0, $this->errorLogLimit));
        }
        return FALSE;        
    }

    /**
     * Processes a file name by removing the base folder.
     *
     * @param string $fileName
     *   The file name to process.
     *
     * @return string
     *   The processed file name.
     */
    private function processFileName($fileName) {
        return str_replace($this->base_folder, '', $fileName);       
    }
}
