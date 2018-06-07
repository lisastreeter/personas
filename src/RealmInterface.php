<?php

namespace Drupal\personas;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining a Realm entity.
 */
interface RealmInterface extends ConfigEntityInterface {

  /**
   * Realm ID for core roles.
   */
  const CORE_REALM = 'core_realm';

  /**
   * Returns the realm description.
   *
   * @return string
   *   The realm description.
   */
  public function getDescription();

  /**
   * Returns a list of permissions assigned to the realm.
   *
   * @return array
   *   The permissions assigned to the realm.
   */
  public function getPermissions();

  /**
   * Checks if the realm has a permission.
   *
   * @param string $permission
   *   The permission to check for.
   *
   * @return bool
   *   TRUE if the realm has the permission, FALSE if not.
   */
  public function hasPermission($permission);

  /**
   * Add a permission to the realm.
   *
   * @param string $permission
   *   The permission to add.
   *
   * @return $this
   */
  public function addPermission($permission);

  /**
   * Removes a permission from the realm.
   *
   * @param string $permission
   *   The permission to remove.
   *
   * @return $this
   */
  public function removePermission($permission);

  /**
   * Returns the weight.
   *
   * @return int
   *   The weight of this realm.
   */
  public function getWeight();

  /**
   * Sets the weight to the given value.
   *
   * @param int $weight
   *   The desired weight.
   *
   * @return $this
   */
  public function setWeight($weight);

}
