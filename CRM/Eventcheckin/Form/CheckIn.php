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

use CRM_Eventcheckin_ExtensionUtil as E;

/**
 * Event Check-In form
 */
class CRM_Eventcheckin_Form_CheckIn extends CRM_Core_Form
{
    /** @var string the token currently being used */
    protected $token;

    public function buildQuickForm()
    {
        // title
        $this->setTitle(E::ts("Event Check-In"));

        // get token
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

        } catch (CiviCRM_API3_Exception $ex) {
            $error_message = $ex->getMessage();
            $this->assign('status_type', 'error');
            $this->assign('status_message', $error_message);
        }

        Civi::resources()->addStyleUrl(E::url('css/CheckIn.css'));

        parent::buildQuickForm();
    }

    public function postProcess()
    {
        $values = $this->exportValues();
        foreach ($values as $submitted_field => $submitted_value) {
            if (!empty($submitted_value) && preg_match('/^_qf_CheckIn_submit_[0-9]+$/', $submitted_field)) {
                $check_in_status = (int) substr($submitted_field, 19);
                if ($check_in_status) {
                    CRM_Eventcheckin_CheckinCode::checkInParticipant($this->token, $check_in_status);
                    $this->assign('status_type', 'success');
                    $this->assign('status_message', "Contact is now checked in.");
                    break;
                }
            }
        }
        parent::postProcess();
    }
}