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
 * Form controller class
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/quickform/
 */
class CRM_Eventcheckin_Form_Settings extends CRM_Core_Form {
  public const CHECKIN_BUTTONS_UNDEFINED      = 0;
  public const CHECKIN_BUTTONS_TOP            = 1;
  public const CHECKIN_BUTTONS_BOTTOM         = 2;
  public const CHECKIN_BUTTONS_TOP_AND_BOTTOM = 3;

  public function buildQuickForm() {
    // TOKEN SETTINGS
    $this->add(
        'text',
        'external_link',
        E::ts('Custom Link'),
        [
          'class' => 'huge',
          'placeholder' => 'civicrm/event/check-in?token={token}',
        ],
        FALSE
    );
    $this->add(
        'select',
        'token_timeout',
        E::ts('Token Timeout'),
        [
          ''         => E::ts('never'),
          '+1 week'  => E::ts('1 week'),
          '+1 month' => E::ts('1 month'),
        ],
        FALSE,
        [
          'class' => 'crm-select2',
        ]
    );

    // CHECK-IN SETTINGS
    $this->add(
        'select',
        'checkin_status_list',
        E::ts('Check-In Possible Status'),
        $this->getParticipantStatusTypes(),
        FALSE,
        [
          'class' => 'crm-select2 huge',
          'multiple' => 'multiple',
          'placeholder' => E::ts('disabled'),
        ]
    );
    $this->add(
        'select',
        'checked_in_status_list',
        E::ts('Checked-In Status'),
        $this->getParticipantStatusTypes(),
        FALSE,
        [
          'class' => 'crm-select2 huge',
          'multiple' => 'multiple',
          'placeholder' => E::ts('disabled'),
        ]
    );
    $this->add(
        'select',
        'verification_fields',
        E::ts('Fields to show for Verification'),
        CRM_Eventcheckin_CheckinFields::getFieldList(),
        FALSE,
        [
          'class' => 'crm-select2 huge',
          'multiple' => 'multiple',
          'placeholder' => E::ts('none'),
        ]
    );
    $this->add(
        'select',
        'button_position',
        E::ts('Check-In Buttons'),
        [
          self::CHECKIN_BUTTONS_TOP => E::ts('On Top'),
          self::CHECKIN_BUTTONS_BOTTOM => E::ts('At the Bottom'),
          self::CHECKIN_BUTTONS_TOP_AND_BOTTOM => E::ts('Top and Bottom'),
        ],
        TRUE,
        [
          'class' => 'crm-select2',
        ]
    );

    $this->setDefaults([
      'external_link' => Civi::settings()->get('event_checkin_link'),
      'token_timeout' => Civi::settings()->get('event_checkin_timeout'),
      'checkin_status_list' => Civi::settings()->get('event_checkin_status_list'),
      'checked_in_status_list' => Civi::settings()->get('event_checked_in_status_list'),
      'verification_fields' => Civi::settings()->get('event_verification_fields'),
      'button_position' => Civi::settings()->get('event_button_position'),
    ]);

    $this->addButtons(
        [
            [
              'type' => 'submit',
              'name' => E::ts('Save'),
              'isDefault' => TRUE,
            ],
        ]
    );
    parent::buildQuickForm();
  }

  public function postProcess() {
    $values = $this->exportValues();
    Civi::settings()->set('event_checkin_link', $values['external_link']);
    Civi::settings()->set('event_checkin_timeout', $values['token_timeout']);
    Civi::settings()->set('event_checkin_status_list', $values['checkin_status_list']);
    Civi::settings()->set('event_checked_in_status_list', $values['checked_in_status_list']);
    Civi::settings()->set('event_verification_fields', $values['verification_fields']);
    Civi::settings()->set('event_button_position', $values['button_position']);

    CRM_Core_Session::setStatus(
        E::ts('Settings Updated'),
        E::ts('Success'),
        'info'
    );
    parent::postProcess();
  }

  /**
   * Get all participant status types
   */
  protected function getParticipantStatusTypes() {
    static $participant_status_list = NULL;
    if ($participant_status_list === NULL) {
      $participant_status_list = [];
      $query = civicrm_api3('ParticipantStatusType', 'get', [
        'option.limit' => 0,
        'return'       => 'id,label',
      ]);
      foreach ($query['values'] as $status) {
        $participant_status_list[$status['id']] = $status['label'];
      }
    }
    return $participant_status_list;
  }

}
