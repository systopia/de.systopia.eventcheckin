<?php
/*-------------------------------------------------------+
| SYSTOPIA Event Checkin                                 |
| Copyright (C) 2021 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de)                 |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*/


require_once __DIR__ . '/vendor/autoload.php';
require_once 'eventcheckin.civix.php';

// phpcs:disable
use Civi\RemoteToolsDispatcher;
use CRM_Eventcheckin_ExtensionUtil as E;

// phpcs:enable

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function eventcheckin_civicrm_config(&$config)
{
    _eventcheckin_civix_civicrm_config($config);
    // register events (with our own wrapper to avoid duplicate registrations)
    $dispatcher = new RemoteToolsDispatcher();

    // EVENTMESSAGES.TOKENS
    $dispatcher->addUniqueListener(
        'civi.eventmessages.tokens',
        ['CRM_Eventcheckin_Tokens', 'addTokens']
    );
    $dispatcher->addUniqueListener(
        'civi.eventmessages.tokenlist',
        ['CRM_Eventcheckin_Tokens', 'listTokens']
    );
}

/**
 * Define custom (Drupal) permissions
 */
function eventcheckin_civicrm_permission(&$permissions) {
    $permissions['event checkin'] = E::ts('Check-In Event Participants');
    $permissions['remote event checkin'] = E::ts('RemoteContacts: Check-In Event Participants');
}

/**
 * Set permissions EventCheckin API
 */
function eventcheckin_civicrm_alterAPIPermissions($entity, $action, &$params, &$permissions) {
    $permissions['event_checkin']['verify']  = ['event checkin', 'remote event checkin'];
    $permissions['event_checkin']['confirm'] = ['event checkin', 'remote event checkin'];
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function eventcheckin_civicrm_install()
{
    _eventcheckin_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function eventcheckin_civicrm_enable()
{
    _eventcheckin_civix_civicrm_enable();
}

// --- Functions below this ship commented out. Uncomment as required. ---

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_preProcess
 */
//function eventcheckin_civicrm_preProcess($formName, &$form) {
//
//}

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_navigationMenu
 */
//function eventcheckin_civicrm_navigationMenu(&$menu) {
//  _eventcheckin_civix_insert_navigation_menu($menu, 'Mailings', array(
//    'label' => E::ts('New subliminal message'),
//    'name' => 'mailing_subliminal_message',
//    'url' => 'civicrm/mailing/subliminal',
//    'permission' => 'access CiviMail',
//    'operator' => 'OR',
//    'separator' => 0,
//  ));
//  _eventcheckin_civix_navigationMenu($menu);
//}
