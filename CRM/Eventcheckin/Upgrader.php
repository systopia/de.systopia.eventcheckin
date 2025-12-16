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

declare(strict_types = 1);

use CRM_Eventcheckin_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_Eventcheckin_Upgrader extends CRM_Extension_Upgrader_Base {

  /**
   * Create the required custom data
   */
  public function install(): void {
    // install necessary data
    $customData = new CRM_Remotetools_CustomData(E::LONG_NAME);
    $customData->syncOptionGroup(E::path('resources/option_group_remote_contact_roles.json'));
  }

  /**
   * Adding the remote contact role
   *
   * @return TRUE on success
   * @throws Exception
   */
  public function upgrade_0001(): bool {
    $this->ctx->log->info('Adding remote contact role');
    $customData = new CRM_Remotetools_CustomData(E::LONG_NAME);
    $customData->syncOptionGroup(E::path('resources/option_group_remote_contact_roles.json'));
    return TRUE;
  }

}
