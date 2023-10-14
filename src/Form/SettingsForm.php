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

    $form['base_group'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Base Configuration'),
    );

    $form['base_group']["directory"] = [
      '#type' => 'textfield',
      '#title' => t('Directory'),
      '#description' => t('Enter the directory path where JSON files will be stored.'),
      '#default_value' => "public://custom-build",
      '#required' => TRUE,
    ];
   
    $form['base_group']["content_types"] = [
        '#type' => 'checkboxes',
        '#title' => t('Entity Types'),
        '#options' => $this->getAllContentTypes(),
        '#description' => t('Choose the entity types you want to include in your custom content in JSON format.'),
        '#default_value' => $config->get('content_types'),
    ];

    $form['front_sync_group'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Front End Sincronization'),
    );

    $form['front_sync_group']['sync_front'] = array(
      '#type' => 'checkbox',
      '#title' => t('Sync with FrontEnd.'),
      '#description' => t('Check this box to enable synchronization with the Front End.'),
      '#default_value' => $config->get('sync_front'),
    );

    $form['front_sync_group']["url_front"] = [
      '#type' => 'textfield',
      '#title' => t('End Point FrontEnd'),
      '#description' => t('Enter the endpoint URL for the Front End synchronization.'),
      '#default_value' => $config->get('url_front'),

    ];

    $form['front_sync_group']["password_frontend"] = [ 
      "#type" => "textfield", 
      "#title" => t("Password Frontend"), 
      "#description" => t('Enter the password for Front End synchronization.'),
      "#default_value" => $config->get('password_frontend'), 
    ];

    $form['front_sync_group']['generate_button'] = [
      '#type' => 'button',
      '#value' => t('Generate Random String'),
      '#ajax' => [
        'callback' => [$this, 'generateRandomString'],
        'wrapper' => 'random-string-wrapper',
      ],
    ];

    $form['front_sync_group']['password_frontend']['#prefix'] = '<div id="random-string-wrapper">';
    $form['front_sync_group']['password_frontend']['#suffix'] = '</div>';

    return parent::buildForm($form, $form_state);
  }

  public function generateRandomString(array &$form, FormStateInterface $form_state) { 
    $random_string = $this->generate_hex_password(40); 
    $form['front_sync_group']["password_frontend"]["#value"] = $random_string; 
    return $form['front_sync_group']["password_frontend"]; 
  }


  private function generate_hex_password($length) {
    $bytes = random_bytes($length);  
    $hex_password = bin2hex($bytes);  
    return $hex_password;
  }



  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('static_custom_api.settings');
    $config->set('content_types', $form_state->getValue('content_types'));
    $config->set('directory', $form_state->getValue('directory'));
    $config->set('sync_front', $form_state->getValue('sync_front'));
    $config->set('url_front', $form_state->getValue('url_front'));
    $config->set('password_frontend', $form_state->getValue('password_frontend'));

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
