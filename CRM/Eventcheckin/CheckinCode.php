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

class CRM_Eventcheckin_CheckinCode {
  public const PARTICIPANT_CODE_USAGE = 'checkin';

  /**
   * Generates a check-in token for the given participant
   *
   * @throws \Exception
   */
  public static function generate(int $participantId): string {
    return CRM_Remotetools_SecureToken::generateEntityToken(
        'Participant',
        $participantId,
        self::getExpirationDate($participantId),
        self::PARTICIPANT_CODE_USAGE
    );
  }

  /**
   * Validate a given check-in token and return the participant ID if valid
   *
   * @param string $code
   *   the code submitted
   *
   * @return int|null The participant ID or, if invalid, null.
   */
  public static function validate(string $code): ?int {
    $participantId = CRM_Remotetools_SecureToken::decodeEntityToken(
        'Participant',
        $code,
        self::PARTICIPANT_CODE_USAGE
    );

    return $participantId;
  }

  /**
   * Generate a check-in link with the given token,
   *  using the configuration values
   *
   * @param string $token
   *   the token submitted
   */
  public static function generateLink(string $token): string {
    $external_link = Civi::settings()->get('event_checkin_link');
    if (is_string($external_link) && '' !== $external_link) {
      /** @var string $link */
      $link = preg_replace('/\{code\}/', $token, $external_link);
      if (substr($link, 0, 8) == 'civicrm/') {
        $link = CRM_Utils_System::url($link, '', TRUE);
      }
    }
    else {
      $link = CRM_Utils_System::url('civicrm/event/check-in', 'token=' . $token, TRUE);
    }
    return $link;
  }

  /**
   * Calculate the expiry date (if any) based on the settings
   */
  public static function getExpirationDate(int $participantId) {
    // todo: here we could implement settings like '1 hour after the event started' based on the participant id
    return Civi::settings()->get('event_checkin_timeout');
  }

  /**
   * Execute the actual check-in of the contact
   *
   * @param string $token
   *   the token submitted
   *
   * @param integer $participantStatusId
   *   the target participant status
   *
   * @throws \Exception if something goes wrong
   */
  public static function checkInParticipant(string $token, int $participantStatusId): void {
    // get participant
    $participantId = CRM_Remotetools_SecureToken::decodeEntityToken('Participant', $token, 'checkin');
    if (!$participantId) {
      throw new CRM_Core_Exception(E::ts('Invalid Token'));
    }

    // verify participant (yes, again)
    civicrm_api3('EventCheckin', 'verify', ['token' => $token]);

    // finally: update participant status
    civicrm_api3('Participant', 'create', [
      'id' => $participantId,
      'participant_status_id' => $participantStatusId,
    ]);
  }

}
