<?php

namespace Drupal\personas\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\personas\RealmInterface;

/**
 * Defines the Realm entity.
 *
 * @ConfigEntityType(
 *   id = "persona_realm",
 *   label = @Translation("Realm"),
 *   handlers = {
 *     "access" = "Drupal\personas\RealmAccessControlHandler",
 *     "list_builder" = "Drupal\personas\RealmListBuilder",
 *     "form" = {
 *       "default" = "Drupal\personas\Form\RealmForm",
 *       "delete" = "Drupal\personas\Form\RealmDeleteForm"
 *     },
 *   },
 *   config_prefix = "persona_realm",
 *   admin_permission = "administer realms",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "weight" = "weight",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "collection" = "/admin/people/realms",
 *     "add-form" = "/admin/people/realms/add",
 *     "edit-form" = "/admin/people/realms/manage/{persona_realm}",
 *     "delete-form" = "/admin/people/realms/manage/{persona_realm}/delete",
 *     "realm-roles-form" = "/admin/people/realms/manage/{persona_realm}/roles",
 *     "add-role-form" = "/admin/people/realms/manage/{persona_realm}/roles/add",
 *     "permissions-form" = "/admin/people/realms/manage/{persona_realm}/permissions"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "weight",
 *     "permissions",
 *   }
 * )
 */
class Realm extends ConfigEntityBase implements RealmInterface {

  /**
   * The Realm ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Realm label.
   *
   * @var string
   */
  protected $label;

  /**
   * Description of the realm.
   *
   * @var string
   */
  protected $description;

  /**
   * The weight of this realm in administrative listings.
   *
   * @var int
   */
  protected $weight;

  /**
   * The permissions belonging to this realm.
   *
   * @var array
   */
  protected $permissions = array();

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function getPermissions() {
    return $this->permissions;
  }

  /**
   * {@inheritdoc}
   */
  public function hasPermission($permission) {
    return in_array($permission, $this->permissions);
  }

  /**
   * {@inheritdoc}
   */
  public function addPermission($permission) {
    if (!$this->hasPermission($permission)) {
      $this->permissions[] = $permission;
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removePermission($permission) {
    $this->permissions = array_diff($this->permissions, [$permission]);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->get('weight');
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight($weight) {
    $this->set('weight', $weight);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function postLoad(EntityStorageInterface $storage, array &$entities) {
    parent::postLoad($storage, $entities);
    // Sort the queried realms by their weight.
    // See \Drupal\Core\Config\Entity\ConfigEntityBase::sort().
    uasort($entities, 'static::sort');
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    if (!isset($this->weight) && ($realms = $storage->loadMultiple())) {
      // Set a realm weight to make this new realm last.
      $max = array_reduce($realms, function ($max, $realm) {
        return $max > $realm->weight ? $max : $realm->weight;
      });
      $this->weight = $max + 1;
    }

    if (!$this->isSyncing()) {
      // Permissions are always ordered alphabetically to avoid conflicts in the
      // exported configuration.
      sort($this->permissions);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);

    /** @var \Drupal\user\RoleStorageInterface $role_storage */
    $role_storage = \Drupal::service('entity_type.manager')->getStorage('user_role');

    // Delete the roles of a deleted realm.
    $delete_roles = [];
    $roles = $role_storage->loadMultiple();

    /** @var \Drupal\personas\RealmInterface $entity */
    foreach ($entities as $entity) {
      $realm_match = $entity->id();
      $delete_roles = array_merge($delete_roles, array_filter($roles, function($role) use ($realm_match) {
        return $role->getThirdPartySetting('personas', 'realm') === $realm_match;
      }));
    }
    $role_storage->delete($delete_roles);
  }
}
