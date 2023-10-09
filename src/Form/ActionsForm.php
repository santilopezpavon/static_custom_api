<?php

namespace Drupal\static_custom_api\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\static_custom_api\Service\Core\AliasCache;
use Drupal\static_custom_api\Service\Core\FilesCache;
use Drupal\static_custom_api\Service\JsonsGeneratorService;


/**
 * Form class for actions example.
 */
class ActionsForm extends FormBase {

  protected $aliasCache;
  protected $filesCache;
  protected $jsonGenerator;

  /**
   * Constructor.
   */
  public function __construct(AliasCache $aliasCache, FilesCache $filesCache, JsonsGeneratorService $jsonGenerator) {
    $this->aliasCache = $aliasCache;
    $this->filesCache = $filesCache;
    $this->jsonGenerator = $jsonGenerator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('static_custom_api.alias_cache'),
      $container->get('static_custom_api.files_cache'),
      $container->get('static_custom_api.jsons_generators')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'static_custom_api.actions_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Create a message element for displaying messages.
    $form['message'] = [
      '#markup' => '<div id="message"></div>',
    ];

    // Create a submit button for Action 1.
    $form['action1'] = [
      '#type' => 'submit',
      '#value' => $this->t('Generate All JSONs'),
      '#submit' => ['::submitForm'],
      '#action_type' => 'json',
    ];

    $form['action2'] = [
      '#type' => 'submit',
      '#value' => $this->t('Generate All Alias'),
      '#submit' => ['::submitForm'],
      '#action_type' => 'alias',
    ];

    return $form;
  }
  

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $action_type = $form_state->getTriggeringElement()['#action_type'];
    switch ($action_type) {
      case 'json':
        $this->generateJsons();
        break;

      case 'alias':
        $this->generateAliases();
        break;
    }
  }

  /**
   * Generate JSONs for cacheable entity types.
   */
  private function generateJsons() {
    $batch = $this->jsonGenerator->generateBatchDataEntities();  

    // Start the batch process.
    batch_set($batch);
  }

  /**
   * Generate aliases JSONs for cacheable entity types and languages.
   */
  private function generateAliases() {
  
    $batch = $this->jsonGenerator->generateBatchDataAliases(); 
    // Start the batch process.
    batch_set($batch);
  }

  

  
}
