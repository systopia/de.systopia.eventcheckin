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
 * Event Check-In form
 */
class CRM_Eventcheckin_Form_CheckIn extends CRM_Core_Form {
  /**
   * @var string|null the token currently being used */
  protected ?string $token = NULL;

  public function buildQuickForm(): void {
    // title
    $this->setTitle(E::ts('Event Check-In'));

    // get token
    // @phpstan-ignore assign.propertyType
    $this->token = CRM_Utils_Request::retrieve('token', 'String', $this);

    // verify token
    try {
      $checkin_data = civicrm_api3('EventCheckin', 'verify', ['token' => $this->token]);
      $this->assign('fields', $checkin_data['values']);

      // add buttons
      $buttons = [];
      foreach ($checkin_data['checkin_options'] as $status_id => $status_label) {
        $buttons[] = [
          'type'  => "submit_{$status_id}",
          'name' => E::ts('Check-In (%1)', [1 => $status_label]),
        ];
      }
      $this->addButtons($buttons);

      // add button position
      // @phpstan-ignore cast.int
      $button_position = (int) Civi::settings()->get('event_button_position');
      $this->assign('show_buttons_top', (int) (
           $button_position == CRM_Eventcheckin_Form_Settings::CHECKIN_BUTTONS_TOP
        || $button_position == CRM_Eventcheckin_Form_Settings::CHECKIN_BUTTONS_TOP_AND_BOTTOM));
      $this->assign('show_buttons_bottom', (int) (
           $button_position == CRM_Eventcheckin_Form_Settings::CHECKIN_BUTTONS_BOTTOM
        || $button_position == CRM_Eventcheckin_Form_Settings::CHECKIN_BUTTONS_TOP_AND_BOTTOM
        || $button_position == CRM_Eventcheckin_Form_Settings::CHECKIN_BUTTONS_UNDEFINED));

    }
    catch (CRM_Core_Exception $ex) {
      $error_message = $ex->getMessage();
      $this->assign('status_type', 'error');
      $this->assign('status_message', $error_message);

      // show the fields anyway, if we have a participant ID:
      $participant_id = CRM_Remotetools_SecureToken::decodeEntityToken('Participant', $this->token ?? '', 'checkin');
      if ($participant_id) {
        $fields = CRM_Eventcheckin_CheckinFields::getParticipantFields($participant_id);
        $this->assign('fields', $fields);
      }
    }

    Civi::resources()->addStyleUrl(E::url('css/CheckIn.css'));

    parent::buildQuickForm();
  }

  public function postProcess(): void {
    $values = $this->exportValues();
    foreach ($values as $submitted_field => $submitted_value) {
      if (!empty($submitted_value) && preg_match('/^_qf_CheckIn_submit_[0-9]+$/', $submitted_field)) {
        $check_in_status = (int) substr($submitted_field, 19);
        if ($check_in_status) {
          CRM_Eventcheckin_CheckinCode::checkInParticipant($this->token ?? '', $check_in_status);
          $this->assign('status_type', 'success');
          $this->assign('status_message', 'Contact is now checked in.');
          break;
        }
      }
    }
    parent::postProcess();
  }

}
