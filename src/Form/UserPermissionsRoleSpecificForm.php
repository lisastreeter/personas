<?php

namespace Drupal\personas\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\personas\RealmInterface;
use Drupal\user\Form\UserPermissionsForm;
use Drupal\user\PermissionHandlerInterface;
use Drupal\user\RoleInterface;
use Drupal\user\RoleStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the user permissions administration form for a specific role.
 */
class UserPermissionsRoleSpecificForm extends UserPermissionsForm {

  /**
   * The specific role for this form.
   *
   * @var \Drupal\user\RoleInterface
   */
  protected $userRole;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs an UserPermissionsRoleSpecific object.
   *
   * @param \Drupal\user\PermissionHandlerInterface $permission_handler
   *   The permission handler.
   * @param \Drupal\user\RoleStorageInterface $role_storage
   *   The role storage.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(PermissionHandlerInterface $permission_handler, RoleStorageInterface $role_storage, ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($permission_handler, $role_storage, $module_handler);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   *
   * Override user.permissions service with personas.realm_permissions.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('personas.realm_permissions'),
      $container->get('entity.manager')->getStorage('user_role'),
      $container->get('module_handler'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getRoles() {
    return [$this->userRole->id() => $this->userRole];
  }

  /**
   * Builds the user permissions administration form for a specific role.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\user\RoleInterface|null $user_role
   *   (optional) The user role used for this form. Defaults to NULL.
   */
  public function buildForm(array $form, FormStateInterface $form_state, RoleInterface $user_role = NULL) {
    $this->userRole = $user_role;
    $realm_id = $user_role->getThirdPartySetting('personas', 'realm');
    $realm_id = ($realm_id) ? $realm_id : RealmInterface::CORE_REALM;
    $realm = $this->entityTypeManager->getStorage('persona_realm')->load($realm_id);
    $this->permissionHandler->setRealm($realm);
    return parent::buildForm($form, $form_state);
  }

}
