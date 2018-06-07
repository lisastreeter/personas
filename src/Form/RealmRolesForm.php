<?php

namespace Drupal\personas\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\personas\RealmInterface;
use Drupal\user\RoleInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides roles listing form for a realm.
 */
class RealmRolesForm extends FormBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs an RealmRolesForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'personas_realm_roles_form';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\personas\RealmInterface $persona_realm
   *   The realm to display the roles form for.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, RealmInterface $persona_realm = NULL) {
    $roles = $this->entityTypeManager->getStorage('user_role')->loadMultiple();
    $realm_match = NULL;
    if ($persona_realm) {
      $realm_id = $persona_realm->id();
      $realm_match = ($realm_id == RealmInterface::CORE_REALM) ? NULL : $realm_id;
    }
    $roles = array_filter($roles, function($role) use ($realm_match) {
      return $role->getThirdPartySetting('personas', 'realm') === $realm_match;
    });

    $form['roles'] = [
      '#type' => 'table',
      '#tree' => TRUE,
      '#id' => 'draggable-table',
      '#header' => [$this->t('Name'), $this->t('Weight'), $this->t('Operations')],
      '#empty' => $this->t('No roles available'),
      '#tabledrag' => [
        [
          'table_id' => 'draggable-table',
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'table-order-weight',
        ],
      ],
    ];
    foreach ($roles as $key => $role) {
      $form['roles'][$key] = [
        '#attributes' => ['class' => ['draggable']],
        'name' => ['#plain_text' => $this->t($role->label())],
        'weight' => [
          '#type' => 'weight',
          '#title_display' => 'invisible',
          '#default_value' => $role->getWeight(),
          '#attributes' => ['class' => ['table-order-weight']],
        ],
        'operations' => [
          '#type' => 'operations',
          '#links' => $this->getOperations($role),
        ],
      ];
    }

    $form['actions'] = [
      '#type' => 'actions',
      '#tree' => FALSE,
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * Gets the role list's operations.
   *
   * @param \Drupal\user\RoleInterface $role
   *   The role the operations are for.
   *
   * @return array
   *   An associative array of operation link data for this list, keyed by
   *   operation name, containing the following key-value pairs:
   *   - title: The localized title of the operation.
   *   - url: An instance of \Drupal\Core\Url for the operation URL.
   *   - weight: The weight of this operation.
   */
  protected function getOperations(RoleInterface $role) {
    $operations = [];
    if ($role->access('update') && $role->hasLinkTemplate('edit-form')) {
      $operations['edit'] = [
        'title' => $this->t('Edit'),
        'weight' => 10,
        'url' => $role->urlInfo('edit-form'),
      ];
    }
    if ($role->access('delete') && $role->hasLinkTemplate('delete-form')) {
      $operations['delete'] = [
        'title' => $this->t('Delete'),
        'weight' => 100,
        'url' => $role->urlInfo('delete-form'),
      ];
    }
    if ($role->hasLinkTemplate('edit-permissions-form')) {
      $operations['permissions'] = [
        'title' => t('Edit permissions'),
        'weight' => 20,
        'url' => $role->urlInfo('edit-permissions-form'),
      ];
    }
    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    dpm($form_state);
    parent::submitForm($form, $form_state);
/*
    foreach ($form_state->getValue($this->entitiesKey) as $id => $value) {
      if (isset($this->entities[$id]) && $this->entities[$id]->get($this->weightKey) != $value['weight']) {
        // Save entity only when its weight was changed.
        $this->entities[$id]->set($this->weightKey, $value['weight']);
        $this->entities[$id]->save();
      }
    }
*/
    drupal_set_message($this->t('The configuration options have been set.'));
  }
}
