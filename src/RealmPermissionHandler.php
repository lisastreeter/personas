<?php

namespace Drupal\personas;

use Drupal\user\PermissionHandler;
use Drupal\personas\RealmInterface;

/**
 * Handler for personas.realm_permissions service.
 *
 * {@inheritdoc}
 */
class RealmPermissionHandler extends PermissionHandler {

  /**
   * The realm.
   *
   * @var \Drupal\personas\RealmInterface
   */
  protected $realm;

  /**
   * Sets the realm for the permission handler.
   *
   * @param RealmInterface $realm
   *   The realm.
   */
  public function setRealm(RealmInterface $realm) {
    $this->realm = $realm;
  }

  /**
   * {@inheritdoc}
   *
   * Filter permissions based on realm configuration.
   */
  public function getPermissions() {
    // If realm is not set, provide all permissions.
    $permissions = $this->buildPermissionsYaml();
    if (isset($this->realm)) {
      $realm_permissions = $this->realm->getPermissions();
      if (!empty($realm_permissions)) {
        $realm_permissions = array_flip($realm_permissions);
        $permissions = array_intersect_key($permissions, $realm_permissions);
      }
    }
    return $this->sortPermissions($permissions);
  }

}
