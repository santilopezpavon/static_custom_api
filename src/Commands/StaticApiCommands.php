<?php

namespace Drupal\static_custom_api\Commands;

use Drush\Commands\DrushCommands;
use Drupal\static_custom_api\Service\JsonsGeneratorService;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class StaticApiCommands extends DrushCommands {

  private $jsonGeneratorService;

  public function __construct(JsonsGeneratorService $jsonGeneratorService) {
    $this->jsonGeneratorService = $jsonGeneratorService;
  }   

  /**
   * Comando de ejemplo
   * Aquí ya lo que necesitéis, llamadas a BD transformaciones etc...
   *
   * @command static-custom-api:alias-generator
   */

  public function aliasGenerator() {
    $data_batch_prepared = $this->jsonGeneratorService->generateBatchDataAliases();
    $this->jsonGeneratorService->executeBatchForDrush($data_batch_prepared);
  }

  /**
   * Comando de ejemplo
   * Aquí ya lo que necesitéis, llamadas a BD transformaciones etc...
   *
   * @command static-custom-api:entities-generator
   */

   public function entitiesGenerator() {
    $data_batch_prepared = $this->jsonGeneratorService->generateBatchDataEntities();
    $this->jsonGeneratorService->executeBatchForDrush($data_batch_prepared);
  }

}
 

