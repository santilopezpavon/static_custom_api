<?php

namespace Drupal\static_custom_api\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;

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


    $form["directory"] = [
      '#type' => 'textfield',
      '#title' => t('Directory'),
      '#default_value' => "public://custom-build",
      '#required' => TRUE,
    ];
   
    $form["content_types"] = [
        '#type' => 'checkboxes',
        '#title' => t('Entity Types'),
        '#options' => $this->getAllContentTypes(),
        '#default_value' => $config->get('content_types'),
    ];

    $form['sync_front'] = array(
      '#type' => 'checkbox',
      '#title' => t('Sync with FrontEnd.'),
      '#default_value' => $config->get('sync_front'),
    );

    $form["url_front"] = [
      '#type' => 'textfield',
      '#title' => t('End Point FrontEnd'),
      '#default_value' => $config->get('url_front'),

    ];



    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('static_custom_api.settings');
    $config->set('content_types', $form_state->getValue('content_types'));
    $config->set('directory', $form_state->getValue('directory'));
    $config->set('sync_front', $form_state->getValue('sync_front'));
    $config->set('url_front', $form_state->getValue('url_front'));

    $nonAssociativeArray = [];

    foreach ($form_state->getValue('content_types') as $key => $value) {
        if ($value !== 0) {
            $nonAssociativeArray[] = $key;
        }
    }

    if($form_state->getValue('sync_front') == "1") {
      $config->set('url_front_bool', TRUE);
    } else {
      $config->set('url_front_bool', FALSE);
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
