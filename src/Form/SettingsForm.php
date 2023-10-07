<?php

namespace Drupal\static_custom_api\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class SettingsForm extends ConfigFormBase {

  private $entity_types_compatibles = ["node", "media", "paragraph", "menu", "taxonomy_term"];

  protected function getEditableConfigNames() {
    return [
      'static_custom_api.settings',
    ];
  }

  public function getFormId() {
    return 'static_custom_api.settings';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('static_custom_api.settings');

       // Obtén el servicio 'path.alias_manager'.
       /* $alias_cache = \Drupal::service('static_custom_api.alias_cache');
        $origin = $alias_cache->saveAlias("node", 2, "es");
        $site_languages = \Drupal::languageManager()->getLanguages();

      kint($site_languages);*/
        /*$all_entity_types = $config->get("content_types_array");
        foreach ($all_entity_types as $entity_type) {
          $result = \Drupal::entityQuery($entity_type)->execute();
          foreach ($result as $id_entity) {
            $origin = $alias_cache->saveAlias($entity_type, $id_entity);
          }          
        }*/
/*
       // Obtén todos los alias.
       $aliases = $aliasManager->getAll();
       dump($aliases);
       // Itera sobre los alias y haz lo que necesites con ellos.
       foreach ($aliases as $source => $alias) {
         // Haz algo con $source (ruta original) y $alias (alias).
       }
    */

    $form["content_types"] = [
        '#type' => 'checkboxes',
        '#title' => t('Entity Types'),
        '#options' => $this->getAllContentTypes(),
        '#default_value' => $config->get('content_types'),
    ];


    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('static_custom_api.settings');
    $config->set('content_types', $form_state->getValue('content_types'));

    $nonAssociativeArray = [];

    foreach ($form_state->getValue('content_types') as $key => $value) {
        if ($value !== 0) {
            $nonAssociativeArray[] = $key;
        }
    }

    $config->set('content_types_array', $nonAssociativeArray);


    $config->save();

    parent::submitForm($form, $form_state);
  }

  
  private function getAllContentTypes() {
    $definitions = \Drupal::entityTypeManager()->getDefinitions();
    $options = [];
    foreach ($definitions as $key => $value) {
      if(in_array($key, $this->entity_types_compatibles)) {
        $options[$key] = $value->getLabel() . " (" . $key . ")";
      }
    }
    return $options;

  }

}
