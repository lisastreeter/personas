<?php

namespace Drupal\personas;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\personas\RealmInterface;

/**
 * Defines a class to build a listing of user role entities.
 *
 * @see \Drupal\user\Entity\Role
 */
class RoleListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'personas_admin_roles_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['realm'] = $this->t('Realm');
    $header['label'] = t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $realm_id = $entity->getThirdPartySetting('personas', 'realm');
    $realm_id = ($realm_id) ? $realm_id : RealmInterface::CORE_REALM;
    $realm = \Drupal::service('entity_type.manager')->getStorage('persona_realm')->load($realm_id);
    $row['realm'] = $realm->label();
    $row['label'] = $entity->label();
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    if ($entity->hasLinkTemplate('edit-permissions-form')) {
      $operations['permissions'] = [
        'title' => t('Edit permissions'),
        'weight' => 20,
        'url' => $entity->urlInfo('edit-permissions-form'),
      ];
    }
    return $operations;
  }

}
