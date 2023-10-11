<?php

namespace Drupal\static_custom_api\Service\Sync;


class SyncFront {

    private $end_point; 
    
    private $client;

    private $doSync = FALSE;

    private $base_folder;

    public function __construct() {
        $this->client = \Drupal::httpClient();  
        $this->end_point =  \Drupal::config("static_custom_api.settings")->get("url_front"); 
        $this->doSync = \Drupal::config("static_custom_api.settings")->get("url_front_bool"); 
        $this->base_folder = \Drupal::config("static_custom_api.settings")->get("directory"); 

    }

    public function deleteFile($file) {
        if($this->doSync === FALSE) {
            return FALSE;
        }
        try {
            $request = $this->client->delete($this->end_point, [
                'json' => [
                  'fileName'=> $this->processFileName($file)
                ]
            ]);
            return json_decode($request->getBody());
        } catch (\Throwable $th) {
            //throw $th;
        }
        return FALSE;
    }

    public function updateFile($file, $data_file) {
        if($this->doSync === FALSE) {
            return FALSE;
        }
        try {
            $request = $this->client->post($this->end_point, [
                'json' => [
                  'fileName'=> $this->processFileName($file),
                  "data" =>  $data_file
                ]
            ]);
            return json_decode($request->getBody());
        } catch (\Throwable $th) {
            //throw $th;
        }

        return FALSE;        
    }    

    private function processFileName($fileName) {
        return str_replace($this->base_folder, '', $fileName);       
    }
}
