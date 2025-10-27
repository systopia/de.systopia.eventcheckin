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
 * EventCheckin.confirm will:
 *  - receive the token
 *  - verify it
 *  - update participant status
 *
 * @param array $spec
 *   API specification blob
 */
function _civicrm_api3_event_checkin_confirm_spec(&$spec)
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
    $spec['status_id'] = [
        'name'         => 'status_id',
        'api.required' => 0,
        'title'        => E::ts('Future Participant Status ID'),
        'description'  => E::ts('Submit a token to be verified'),
    ];
}

/**
 * EventCheckin.confirm will:
 *  - receive the token
 *  - verify it
 *  - update participant status
 *
 * @param array $spec
 *   API specification blob
 */
function civicrm_api3_event_checkin_confirm($params)
{
    // 1) VERIFY (might throw an exception)
    $verification_result = civicrm_api3('EventCheckin', 'verify', $params);

    // 2) (RE-)VALIDATE TOKEN
    $participant_id = CRM_Remotetools_SecureToken::decodeEntityToken('Participant', $params['token'], 'checkin');
    if (!$participant_id) {
        throw new CRM_Core_Exception(E::ts("Invalid Token"));
    }

    // 3) DETERMINE REQUESTED STATUS
    $allowed_status_options = $verification_result['checkin_options'];
    if (empty($params['status_id'])) {
        if (count($allowed_status_options) > 1) {
            throw new CRM_Core_Exception(E::ts("You have to provide the status ID if more than two are configured."));
        } else {
            $one_status_tuple = array_keys($allowed_status_options);
            $requested_status_id = (int) reset($one_status_tuple);
        }
    } else {
        $requested_status_id = (int) $params['status_id'];
    }

    // 4) VERIFY REQUESTED STATUS AND UPDATE PARTICIPANT
    if (isset($allowed_status_options[$requested_status_id])) {
        civicrm_api3('Participant', 'create', [
            'id' => $participant_id,
            'status_id' => $requested_status_id
        ]);
    } else {
        throw new CRM_Core_Exception(E::ts("Invalid participant status requested."));
    }

    return civicrm_api3_create_success();
}