# WIP
* Improve the current code
* Saving Views in JSON
* Improve the live JSONs alter
* Implementation live JSONs sync with frontend

# Description
This module is for saving the content in JSON files. With this method, we can recover the entity data from JSON files instead of the database. And we can generate JSON files for all entities from the website and copy these JSON files to another server with a frontend framework such as NextJS to use this information without making requests to Drupal.

The JSONs files are generated with the core Drupal serialize, therefore you can alter the serielize and normalize without affect this module.

# Instructions
The first step is configure the entity type that will be saved in JSON, for that we have this form with the main configuration of module: /admin/config/static-custom-api/settings.

There are two methods for generate JSON:
* In live: The entity is created, updated or deleted in JSONs file also.
* In Batch: The JSONs files are created massively. For that we have two methods:
    * UI with batch api: Slow but works
    * Drush commands: This is the fast and recomended solution.




# WIP
* Improve the current code
* Saving Views in JSON
* Improve the live JSONs alter
* Implementation live JSONs sync with frontend

# Description
This module is for saving the content in JSON files. With this method, we can recover the entity data from JSON files instead of the database. And we can generate JSON files for all entities from the website and copy these JSON files to another server with a frontend framework such as NextJS to use this information without making requests to Drupal.

The JSONs files are generated with the core Drupal serialize, therefore you can alter the serielize and normalize without affect this module.

# Instructions
The first step is configure the entity type that will be saved in JSON, for that we have this form with the main configuration of module: /admin/config/static-custom-api/settings.



# How Works

## First Step
Drupal generates JSONs of all selected entities and alias.  

There are two methods for generate JSON:
* In live: The entity is created, updated or deleted in JSONs file also.
* In Batch: The JSONs files are created massively. For that we have two methods:
    * UI with batch api: Slow but works
    * Drush commands: This is the fast and recomended solution.

The process use the Drupal serializer (@serializer), this Drupal method returns the entity in JSON format and you can save in a file with the files system service:

```php
    $json_entity = $this->serializer->serialize($entity, 'json', []);
    $this->fileSystem->saveData($json_entity, $file_path, FileSystemInterface::EXISTS_REPLACE); 
```

## Second Step
You can access to JSONs data with the endpoints or copy the folder with JSON in a FrontEnd framework:

POST:
/{lang}/static-api/get-entity-alias
```json
{ "alias": "/node/4" }
```

POST|GET:
/{lang}/static-api/get-entity/{entity_type}/{id}

If you want pass the folder JSON to the frontend framework, this module provides the endpoint and library for NextJS in auxiliarFiles from this module.

