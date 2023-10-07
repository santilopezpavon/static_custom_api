<?php

namespace Drupal\static_custom_api\Commands;

use Drush\Commands\DrushCommands;
use Drupal\static_custom_api\Service\JsonsGeneratorService;

/**
 * A Drush command file.
 *
 * In addition to this file, you need a drush.services.yml
 * in the root of your module, and a composer.json file that provides
 * the name of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class StaticApiCommands extends DrushCommands {

  private $jsonGeneratorService;

  /**
   * Constructor.
   *
   * @param \Drupal\static_custom_api\Service\JsonsGeneratorService $jsonGeneratorService
   *   The JSON generator service.
   */
  public function __construct(JsonsGeneratorService $jsonGeneratorService) {
    $this->jsonGeneratorService = $jsonGeneratorService;
  }   

  /**
   * Command to generate alias JSONs.
   *
   * This command generates alias JSONs.
   *
   * @command static-custom-api:alias-generator
   */
  public function aliasGenerator() {
    // Generate batch data for alias generation.
    $data_batch_prepared = $this->jsonGeneratorService->generateBatchDataAliases();

    // Execute the batch process for Drush.
    $this->jsonGeneratorService->executeBatchForDrush($data_batch_prepared);
  }

  /**
   * Command to generate entity JSONs.
   *
   * This command generates entity JSONs.
   *
   * @command static-custom-api:entities-generator
   */
  public function entitiesGenerator() {
    // Generate batch data for entity generation.
    $data_batch_prepared = $this->jsonGeneratorService->generateBatchDataEntities();

    // Execute the batch process for Drush.
    $this->jsonGeneratorService->executeBatchForDrush($data_batch_prepared);
  }

}
