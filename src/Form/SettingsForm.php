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

    $form["password_frontend"] = [ 
      "#type" => "textfield", 
      "#title" => t("Password Frontend"), 
      "#default_value" => $config->get('password_frontend'), 
    ];

    $form['generate_button'] = [
      '#type' => 'button',
      '#value' => t('Generate Random String'),
      '#ajax' => [
        'callback' => [$this, 'generateRandomString'],
        // Especifica el elemento del formulario que se va a actualizar.
        'wrapper' => 'random-string-wrapper',
      ],
    ];

    $form['password_frontend']['#prefix'] = '<div id="random-string-wrapper">';
    $form['password_frontend']['#suffix'] = '</div>';

    



    return parent::buildForm($form, $form_state);
  }

  // Define la función que genera el string aleatorio y lo asigna al campo de texto. 
  public function generateRandomString(array &$form, FormStateInterface $form_state) { 
    // Genera un string aleatorio de 10 caracteres usando letras y números. 
    $random_string = $this->generate_hex_password(40); 
    // Asigna el string aleatorio al valor del campo de texto. 
    $form["password_frontend"]["#value"] = $random_string; 
    // Devuelve el elemento del formulario que se ha actualizado. 
    return $form["password_frontend"]; 
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
