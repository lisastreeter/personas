<?php

namespace Drupal\personas\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\personas\RealmInterface;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Use personas form instead of user form for permissions.
    if ($route = $collection->get('user.admin_permissions')) {
      $route->setDefaults([
        '_form' => '\Drupal\personas\Form\UserPermissionsRealmForm',
        '_title' => 'Core permissions',
        'persona_realm' => RealmInterface::CORE_REALM,
      ]);
    }

    // Use personas form instead of user form for role permissions.
    if ($route = $collection->get('entity.user_role.edit_permissions_form')) {
      $route->setDefault('_form', '\Drupal\personas\Form\UserPermissionsRoleSpecificForm');
    }
  }

}
