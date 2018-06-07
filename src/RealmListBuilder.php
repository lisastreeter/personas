<?php

namespace Drupal\personas;

use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a listing of Realm entities.
 */
class RealmListBuilder extends DraggableListBuilder {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'personas_admin_realms_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Realm');
    $header['description'] = $this->t('Description');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['description']['data'] = ['#markup' => $entity->getDescription()];
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    if ($entity->hasLinkTemplate('realm-roles-form')) {
      $operations['roles'] = [
        'title' => t('List roles'),
        'weight' => 0,
        'url' => $entity->urlInfo('realm-roles-form'),
      ];
    }

    if ($entity->hasLinkTemplate('permissions-form')) {
      $operations['permissions'] = [
        'title' => t('Manage permissions'),
        'weight' => 20,
        'url' => $entity->urlInfo('permissions-form'),
      ];
    }
    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    drupal_set_message(t('The realm settings have been updated.'));
  }

}
