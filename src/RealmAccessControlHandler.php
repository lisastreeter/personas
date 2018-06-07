<?php

namespace Drupal\personas;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the realm entity type.
 *
 * @see \Drupal\personas\Entity\Realm
 */
class RealmAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($operation == 'view' && $account->hasPermission('view user role realms')) {
      $access = AccessResult::allowed();
    }
    elseif ($operation == 'delete' && $entity->id() == RealmInterface::CORE_REALM) {
      $access = AccessResult::forbidden();
    }
    else {
      $access = parent::checkAccess($entity, $operation, $account);
    }
    return $access;
  }

}
