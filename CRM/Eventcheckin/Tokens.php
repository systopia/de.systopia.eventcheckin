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

use Civi\EventMessages\MessageTokenList;
use CRM_Eventcheckin_ExtensionUtil as E;
use Civi\EventMessages\MessageTokens as MessageTokens;
use chillerlan\QRCode\QRCode;

class CRM_Eventcheckin_Tokens {

  public const TEMPLATE_CODE_LINK          = 'event_checkin_link';
  public const TEMPLATE_CODE_TOKEN         = 'event_checkin_code';
  public const TEMPLATE_CODE_TOKEN_QR_DATA = 'event_checkin_code_data';
  public const TEMPLATE_CODE_TOKEN_QR_IMG  = 'event_checkin_code_img';

  /**
   * Register our tokens with the EventMessages extension
   *
   * @param \Civi\EventMessages\MessageTokenList $tokenList
   *   token list event
   */
  public static function listTokens(MessageTokenList $tokenList): void {
    $tokenList->addToken(
        '$' . self::TEMPLATE_CODE_TOKEN,
        E::ts('Check-In Token')
    );
    $tokenList->addToken(
        '$' . self::TEMPLATE_CODE_LINK,
        E::ts('Check-In Link')
    );
    $tokenList->addToken(
        '$' . self::TEMPLATE_CODE_TOKEN_QR_IMG,
        E::ts('Check-In QR-Code (image)')
    );
    $tokenList->addToken(
        '$' . self::TEMPLATE_CODE_TOKEN_QR_DATA,
        E::ts('Check-In QR-Code (data)')
    );
  }

  /**
   * Add some tokens to an event message:
   *  - cancellation token
   *
   * @param \Civi\EventMessages\MessageTokens $messageTokens
   *   the token list
   */
  public static function addTokens(MessageTokens $messageTokens): void {
    $tokens = $messageTokens->getTokens();
    if (empty($tokens['participant']['id'])) {
      // no participant, no check-in tokens
      return;
    }

    // check which tokens we want
    $add_token_and_link = TRUE;
    // if the requiresToken function exists, we can check if we actually need to do anything
    $render_qr_code = $messageTokens->requiresToken(self::TEMPLATE_CODE_TOKEN_QR_IMG)
              || $messageTokens->requiresToken(self::TEMPLATE_CODE_TOKEN_QR_DATA);
    if (!$render_qr_code) {
      $add_token_and_link = $messageTokens->requiresToken(self::TEMPLATE_CODE_LINK)
                  || $messageTokens->requiresToken(self::TEMPLATE_CODE_TOKEN);
    }

    // now: let's go
    $participant_id = (int) $tokens['participant']['id'];
    if ($add_token_and_link || $render_qr_code) {
      // add the token
      $token = CRM_Eventcheckin_CheckinCode::generate($participant_id);
      $messageTokens->setToken(self::TEMPLATE_CODE_TOKEN, $token, FALSE);

      // generate the link
      $link = CRM_Eventcheckin_CheckinCode::generateLink($token);
      $messageTokens->setToken(self::TEMPLATE_CODE_LINK, $link, FALSE);
    }

    // also add the QR codes if requested
    if ($add_token_and_link) {
      // generate the data
      $qr_code = new QRCode();
      $qr_code_data = $qr_code->render($link);
      assert(is_string($qr_code_data));
      $messageTokens->setToken(self::TEMPLATE_CODE_TOKEN_QR_DATA, $qr_code_data, FALSE);

      // compile an img tag for convenience
      $qr_code_alt_text = E::ts('Checkin QR Code');
      $qr_code_img = "<img alt=\"{$qr_code_alt_text}\" src=\"{$qr_code_data}\"/>";
      $messageTokens->setToken(self::TEMPLATE_CODE_TOKEN_QR_IMG, $qr_code_img, FALSE);
    }
  }

}
