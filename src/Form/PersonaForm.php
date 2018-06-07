<?php

namespace Drupal\personas\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\personas\RealmInterface;
use Drupal\user\RoleInterface;

/**
 * Class PersonaForm.
 *
 * @package Drupal\personas\Form
 */
class PersonaForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $persona = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#size' => 30,
      '#required' => TRUE,
      '#default_value' => $persona->label(),
      '#maxlength' => 64,
      '#description' => $this->t('The name for this persona. Examaple: "Intern", "Staff Member", "Manager"'),
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#size' => 30,
      '#maxlength' => 64,
      '#required' => TRUE,
      '#disabled' => !$persona->isNew(),
      '#default_value' => $persona->id(),
      '#machine_name' => [
        'exists' => '\Drupal\personas\Entity\Persona::load',
      ],
    ];

    $form['realms'] = [
      '#type' => 'fieldset',
      '#title' => 'Realms of responsibility (roles)',
      '#tree' => TRUE,
    ];
    $role_data = $this->getRoleOptionsByRealm();
    $realms = $this->entityTypeManager->getStorage('persona_realm')->loadMultiple();
    foreach ($realms as $realm) {
      // Skip realms without roles.
      if (!isset($role_data[$realm->id()])) {
        continue;
      }
      $form['realms'][$realm->id()] = [
        '#type' => 'checkboxes',
        '#title' => $realm->label(),
        '#options' => $role_data[$realm->id()],
        '#default_value' => $persona->getRoles(),
        '#weight' => $realm->getWeight(),
      ];
    }
    return parent::form($form, $form_state);
  }

  /**
   * {@inheritDoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    // Extract roles from the realms form element.
    $roles = [];
    array_walk($form_state->getValue('realms'), function(&$value, $key) use (&$roles) {
      $roles = array_merge($roles, $value);
    });
    $form_state->setValue('roles', $roles);

    $persona = parent::buildEntity($form, $form_state);
    return $persona;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $persona = $this->entity;
    $status = $persona->save();

    $edit_link = $this->entity->link($this->t('Edit'));
    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Persona.', [
          '%label' => $persona->label(),
        ]));
        $this->logger('persona')->notice('Persona %label has been updated.', [
          '%label' => $persona->label(), 'link' => $edit_link,
        ]);
        break;

      default:
        drupal_set_message($this->t('Saved the %label Persona.', [
          '%label' => $persona->label(),
        ]));
        $this->logger('persona')->notice('Persona %label has been added.', [
          '%label' => $persona->label(), 'link' => $edit_link,
        ]);
    }
    $form_state->setRedirectUrl($persona->urlInfo('collection'));
  }

  protected function getRoleOptions() {
    $storage = $this->entityTypeManager->getStorage('user_role');
    return array_reduce($storage->loadMultiple(), function ($options, $role) {
      $skip = [RoleInterface::ANONYMOUS_ID, RoleInterface::AUTHENTICATED_ID];
      if (!in_array($role->id(), $skip)) {
        $options[$role->id()] = $role->label();
      }
      return $options;
    }, []);
  }

  protected function getRoleOptionsByRealm() {
    $skip = [RoleInterface::ANONYMOUS_ID, RoleInterface::AUTHENTICATED_ID];
    $realms = [];
    $roles = $this->entityTypeManager->getStorage('user_role')->loadMultiple();
    foreach ($roles as $role) {
      if (!in_array($role->id(), $skip)){
        $realm_id = $role->getThirdPartySetting('personas', 'realm');
        $realm_id = ($realm_id) ? $realm_id : RealmInterface::CORE_REALM;
        $realms[$realm_id][$role->id()] = $role->label();
      }
    }
    return $realms;
  }
}
