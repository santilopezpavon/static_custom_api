# WIP
* SECURIZATION ENDPOINT NEXTJS TO LIVE ALTER ENTITIES
* Improve the current code
* Saving Views in JSON (Axiliar module)
* Remove batch api by UI, is more slow than drush command
* Minimal Alter normalize for return clean data (Auxiliar module)
* Entity to JSON taking into account the bundle

# Description
This module is designed to facilitate the storage of content in JSON files within the Drupal framework. Using this approach, we can retrieve entity data from JSON files rather than solely relying on the database. Furthermore, it enables the generation of JSON files for all entities within the website, which can be copied to another server equipped with a frontend framework like NextJS. This allows for the consumption of this data without the need for continuous requests to the Drupal backend.

Following the initial data transfer, there are two available options:

* Option 1: Manual File Transfer - You can manually copy the JSON files to the target server.
* Option 2: Dynamic File Transfer - In this option, Drupal interacts with an entity, sending relevant information about this interaction to the frontend platform. This module provides the necessary logic for this process on the frontend, particularly in the context of NextJS.


The JSON files are generated using the core Drupal serialization method. Consequently, you can customize the serialization and normalization without affecting the functionality of this module.

For more information on Drupal Serialization, please refer to:
https://www.drupal.org/docs/drupal-apis/serialization-api/serialization-api-overview


# Instructions

The first step involves configuring the entity types to be stored as JSON. This configuration is performed via a form accessible at: /admin/config/static-custom-api/settings.


# How Works

## First Step
Drupal generates JSON files for all selected entities and aliases. There are two methods for generating JSON files:

* Live Mode: JSON files are created, updated, or deleted as entities change.
* Batch Mode: JSON files are generated in batches. Two methods are available for batch generation:
    * User Interface (UI) with batch API: Although slower, it works well.
    * Drush Commands: This is the recommended and faster solution for batch generation.

The process uses the Drupal serializer (@serializer). This Drupal method converts entities into JSON format, which can then be saved as files using the file system service:


```php
$json_entity = $this->serializer->serialize($entity, 'json', []);
$this->fileSystem->saveData($json_entity, $file_path, FileSystemInterface::EXISTS_REPLACE); 
```

## Second Step
Accessing JSON data can be achieved through API endpoints, or you can copy the folder containing JSON files to a frontend framework.

**API Endpoints:**

POST: /{lang}/static-api/get-entity-alias
Body params:
    alias: This is the Entity Alias that we need get.
Query params:
    force(Optional): This params is for get the information from DB instead JSON files, the value does not affect the result. 
```json
{ "alias": "/node/4" }
```

POST|GET: /{lang}/static-api/get-entity/{entity_type}/{id}
Query params:
    force(Optional): This params is for get the information from DB instead JSON files, the value does not affect the result. 

Should you wish to transfer the JSON folder to a frontend framework, this module provides the necessary endpoints and library for NextJS within the 'auxiliarFiles' directory of this module. The folder structure for NextJS usage should be organized as follows:


```bash 
/app/service/DrupalStatic.js
/pages/api/
           staticdata.js
           staticdataupdate.js
```
The JSON files will be stored in a folder named /custom-build at the root of the NextJS application.




