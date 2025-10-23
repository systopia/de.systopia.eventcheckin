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

require_once 'eventcheckin.civix.php';

use CRM_Eventcheckin_ExtensionUtil as E;

/**
 * EventCheckin.verify will:
 *  - receive the token
 *  - verify it
 *  - return data about the recipient
 *  - return possible participant status options when checked in
 *
 * @param array $spec
 *   API specification blob
 */
function _civicrm_api3_event_checkin_verify_spec(&$spec)
{
    $spec['remote_contact_id'] = [
        'name'         => 'remote_contact_id',
        'api.required' => 0,
        'title'        => E::ts('Remote Contact ID'),
        'description'  => E::ts('You can identify via a remote contact ID'),
    ];
    $spec['token'] = [
        'name'         => 'token',
        'api.required' => 1,
        'title'        => E::ts('Event-Check-In Token'),
        'description'  => E::ts('Submit a token to be verified'),
    ];
}

/**
 * EventCheckin.verify will:
 *  - receive the token
 *  - verify it
 *  - return data about the recipient
 *  - return possible participant status options when checked in
 *
 * @param array $spec
 *   API specification blob
 */
function civicrm_api3_event_checkin_verify($params)
{
    // 1) VERIFY PERMISSION
    if (!CRM_Core_Permission::check('event checkin')) {
        // user does NOT have the event check-in permission, so check for remote
        if (CRM_Core_Permission::check('remote event checkin')) {
            // verify and check the remote user
            if (empty($params['remote_contact_id'])) {
                throw new CRM_Core_Exception(E::ts("remote_contact_id is mandatory with the 'remote event checkin' permission"));
            } else {
                $remote_contact_id = $params['remote_contact_id'];
                $contact_id = CRM_Remotetools_Contact::getByKey($remote_contact_id);
                if (empty($contact_id)) {
                    throw new CRM_Core_Exception(E::ts("remote_contact_id is not valid."));
                } else {
                    if (!CRM_Remotetools_ContactRoles::hasRole($contact_id, 'remote-event-check-in')) {
                        throw new CRM_Core_Exception(E::ts("Remote contact does not have the 'remote-event-check-in' role."));
                    }
                }
            }
        } else {
            // this shouldn't happen, because the API spec permissions should enforce this:
            throw new CRM_Core_Exception(E::ts("Permission denied"));
        }
    }

    // 2) VALIDATE TOKEN
    $participant_id = CRM_Remotetools_SecureToken::decodeEntityToken('Participant', $params['token'], 'checkin');
    if (!$participant_id) {
        throw new CRM_Core_Exception(E::ts("Invalid Token"));
    }

    // verify that the participant exists
    $participant = CRM_Eventcheckin_CheckinFields::getParticipant($participant_id);
    if (empty($participant)) {
        throw new CRM_Core_Exception(E::ts("Participant [%1] doesn't exist (any more).", [1 => $participant_id]));
    }

    // get the possible current status list
    $valid_status_list = Civi::settings()->get('event_checkin_status_list');
    if (empty($valid_status_list)) {
        throw new CRM_Core_Exception(E::ts("No eligible statuses for checkin configured."));
    }

    // get the possible future status list
    $checked_in_status_list = CRM_Eventcheckin_CheckinFields::getCheckedInStatusList();
    if (empty($checked_in_status_list)) {
        throw new CRM_Core_Exception(E::ts("No eligible target statuses for checkin configured."));
    }

    // verify that the participant has an eligible status
    if (!in_array($participant['participant_status_id'], $valid_status_list)) {
        throw new CRM_Core_Exception(E::ts("Participant's status is currently '%1', and is therefore not eligible for check-in",
         [1 => $participant['participant_status']]));
    }

    // check if the event is still active
    $event = CRM_Eventcheckin_CheckinFields::getEventForParticipant($participant_id);
    if (empty($event['is_active'])) {
        throw new CRM_Core_Exception(E::ts("Event is not active any more"));
    }

    // gather the data
    $null = null;
    $fields = CRM_Eventcheckin_CheckinFields::getParticipantFields($participant_id);
    return civicrm_api3_create_success($fields, $params, 'EventCheckin', 'verify', $null,
                                       ['checkin_options' => $checked_in_status_list]);
}
