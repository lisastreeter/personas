<?php

namespace Drupal\personas\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\personas\RealmInterface;

/**
 * Provides route responses for realms.
 */
class RealmController extends ControllerBase {

  /**
   * Returns a form to add a new role for a realm.
   *
   * @param \Drupal\personas\RealmInterface $persona_realm
   *   The realm this role will be added to.
   *
   * @return array
   *   The role add form render array.
   */
  public function addRole(RealmInterface $persona_realm) {
    $role = $this->entityTypeManager()->getStorage('user_role')->create();
    $role->setThirdPartySetting('personas', 'realm', $persona_realm->id());
    return $this->entityFormBuilder()->getForm($role);
  }

  /**
   * Route title callback.
   *
   * @param \Drupal\personas\RealmInterface $persona_realm
   *   The realm.
   *
   * @return string
   *   The realm label as a render array.
   */
  public function realmTitle(RealmInterface $persona_realm) {
    return ['#markup' => $persona_realm->label(), '#allowed_tags' => Xss::getHtmlTagList()];
  }

}
