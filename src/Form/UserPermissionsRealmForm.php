<?php

namespace Drupal\personas\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\personas\RealmInterface;
use Drupal\user\RoleInterface;
use Drupal\user\Form\UserPermissionsForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the user permissions administration form for a realm.
 */
class UserPermissionsRealmForm extends UserPermissionsForm {

  /**
   * The realm for this form.
   *
   * @var \Drupal\personas\RealmInterface
   */
  protected $realm;

  /**
   * {@inheritdoc}
   *
   * Override user.permissions service with personas.realm_permissions.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('personas.realm_permissions'),
      $container->get('entity.manager')->getStorage('user_role'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'personas_user_permissions_realm';
  }

  /**
   * {@inheritdoc}
   *
   * Filter roles based on realm assignment.
   */
  protected function getRoles() {
    $roles = $this->roleStorage->loadMultiple();
    $realm_match = NULL;
    if ($realm = $this->realm) {
      $realm_id = $realm->id();
      $realm_match = ($realm_id == RealmInterface::CORE_REALM) ? NULL : $realm_id;
    }
    $roles = array_filter($roles, function($role) use ($realm_match) {
      return (($role->id() === RoleInterface::ANONYMOUS_ID) ||
        ($role->id() === RoleInterface::AUTHENTICATED_ID) ||
        ($role->getThirdPartySetting('personas', 'realm') === $realm_match));
    });
    return $roles;
  }

  /**
   * {@inheritdoc}
   *
   * @param RealmInterface $realm
   *   The realm for this form.
   */
  public function buildForm(array $form, FormStateInterface $form_state, RealmInterface $persona_realm = NULL) {
    $this->realm = $persona_realm;
    $this->permissionHandler->setRealm($persona_realm);
    $form = parent::buildForm($form, $form_state);
    return $form;
  }

}
