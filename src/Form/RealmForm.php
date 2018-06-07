<?php

namespace Drupal\personas\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\personas\RealmInterface;
use Drupal\user\PermissionHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class RealmForm.
 *
 * @package Drupal\personas\Form
 */
class RealmForm extends EntityForm {

  /**
   * The permission handler.
   *
   * @var \Drupal\user\PermissionHandlerInterface
   */
  protected $permissionHandler;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

    /**
   * Constructs a new UserPermissionsForm.
   *
   * @param \Drupal\user\PermissionHandlerInterface $permission_handler
   *   The permission handler.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(PermissionHandlerInterface $permission_handler, ModuleHandlerInterface $module_handler) {
    $this->permissionHandler = $permission_handler;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.permissions'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $realm = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#size' => 30,
      '#required' => TRUE,
      '#default_value' => $realm->label(),
      '#maxlength' => 64,
      '#description' => $this->t('The name for this realm. Examples: "Blog articles", "Product reviews", "Promotions"'),
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#size' => 30,
      '#maxlength' => 64,
      '#required' => TRUE,
      '#disabled' => !$realm->isNew(),
      '#default_value' => $realm->id(),
      '#machine_name' => [
        'exists' => '\Drupal\personas\Entity\Realm::load',
      ],
    ];

    $form['description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#default_value' => $realm->getDescription(),
    ];

    // Do not include permissions form for Core realm.
    if ($realm->id() == RealmInterface::CORE_REALM) {
      return parent::form($form, $form_state);
    }

    // Use realm permissions for default permission values.
    $realm_permissions = $realm->getPermissions();
    $realm_permissions = array_fill_keys($realm_permissions, 1);

    // All permissions options.
    $permissions = $this->permissionHandler->getPermissions();
    $permissions_by_provider = [];
    foreach ($permissions as $permission_name => $permission) {
      $permissions_by_provider[$permission['provider']][$permission_name] = $permission;
    }

    $form['permissions_container'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Permissions by Provider'),
      '#tree' => TRUE,
    ];

    foreach ($permissions_by_provider as $provider => $permissions) {
      $form['permissions_container'][$provider] = [
        '#type' => 'details',
        '#title' => $this->t('@provider', [
          '@provider' => $this->moduleHandler->getName($provider)
        ]),
        '#tree' => TRUE,
      ];
      $header = [
        'permission' => $this->t('Permissions'),
      ];
      $rows = [];
      foreach ($permissions as $perm => $perm_item) {
        $perm_item += [
          'description' => '',
          'restrict access' => FALSE,
          'warning' => !empty($perm_item['restrict access']) ? $this->t('Warning: Give to trusted roles only; this permission has security implications.') : '',
        ];
        $rows[$perm] = [
          'permission' => [
            'data' => [
              '#type' => 'inline_template',
              '#template' => '<div class="permission"><span class="title">{{ title }}</span>{% if description or warning %}<div class="description">{% if warning %}<em class="permission-warning">{{ warning }}</em> {% endif %}{{ description }}</div>{% endif %}</div>',
              '#context' => [
                'title' => $perm_item['title'],
                'description' => $perm_item['description'],
                'warning' => $perm_item['warning'],
              ],
            ],
          ],
        ];
      }

      $form['permissions_container'][$provider]['permissions'] = [
        '#type' => 'tableselect',
        '#header' => $header,
        '#options' => $rows,
        '#default_value' => $realm_permissions,
      ];
      $form['permissions_container'][$provider]['#open'] = ($realm->isNew() || !empty(array_intersect_key($rows, $realm_permissions)));
    }

    return parent::form($form, $form_state);
  }

  /**
   * {@inheritDoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    $permissions = [];
    if ($this->entity->id() != RealmInterface::CORE_REALM) {
      foreach($form_state->getValue('permissions_container') as $provider => $perms) {
        $permissions = array_merge($permissions, array_filter(array_shift($perms)));
      }
      // Because of weirdness in UserPermissionsForm::buildForm, need to
      // require these two permissions if node module exists.
      if ($this->moduleHandler->moduleExists('node')) {
        $permissions += [
          'view own unpublished content' => 'view own unpublished content',
          'access content' => 'access content',
        ];
      }
    }
    $form_state->setValue('permissions', $permissions);

    $realm = parent::buildEntity($form, $form_state);
    return $realm;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $realm = $this->entity;
    $realm_id = $realm->id();

    // Handle permissions for non-core realms.
    if ($realm_id != RealmInterface::CORE_REALM) {
      $permissions = $form_state->getValue('permissions');

      // Revoke removed permissions from all realm roles.
      $removed_permissions = array_diff($realm->getPermissions(), $permissions);
      if (!empty($removed_permissions)) {
        $roles = \Drupal::service('entity_type.manager')->getStorage('user_role')->loadMultiple();
        $roles = array_filter($roles, function($role) use ($realm_id) {
          return $role->getThirdPartySetting('personas', 'realm') === $realm_id;
        });
        foreach ($roles as $role) {
          $revoke_permissions = array_intersect($role->getPermissions(), $removed_permissions);
          if (!empty($revoke_permissions)) {
            foreach ($revoke_permissions as $permission) {
              $role->revokePermission($permission);
            }
            $role->trustData()->save();
          }
        }
      }
    }

    $status = $realm->save();

    $edit_link = $this->entity->link($this->t('Edit'));
    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Realm.', [
          '%label' => $realm->label(),
        ]));
        $this->logger('realm')->notice('Realm %label has been updated.', [
          '%label' => $realm->label(), 'link' => $edit_link,
        ]);
        break;

      default:
        drupal_set_message($this->t('Saved the %label Realm.', [
          '%label' => $realm->label(),
        ]));
        $this->logger('realm')->notice('Realm %label has been added.', [
          '%label' => $realm->label(), 'link' => $edit_link,
        ]);
    }
    $form_state->setRedirectUrl($realm->urlInfo('collection'));
  }

}
