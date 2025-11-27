## Basic Configuration

Visit the configuration page at `civicrm/admin/eventcheckin/settings` which will
among some other settings allow you to

* Configure which participant status will be eligible for check-in
* Define which status registrations can be changed to when checking in (there
  will be one *Check in* button per status)
* The data to be displayed on the check-in screen

Once that ist done, create at least one message template that contains the token
for the QR-Codes or checkin links (e.g. `{$event_checkin_code_img})`, include it
in automated event e-mail or use it when sending out e-mail or creating
documents (e.g. event tickets) manually.

Placeholders (Smarty variables) that can be used are:

* `{$event_checkin_code}` - generates a unique check-in-link
* `{$event_checkin_code_img}` - generates a unique check-in-link presented as
  a QR Code with fixed width
* `{$event_checkin_code_data}` - generates a unique check-in-link presented
  as a QR Code that can be html-formatted as an image

Please refer to the
[*Event Communication* extension's documentation](https://github.com/systopia/de.systopia.eventmessages)
to learn more about its features.

## Permissions

In order to check in participants, your user has to have the
"Check-In Event Participants" (`event checkin`) as well as the "Access
CiviCRM" permissions. You may want to create a separate role e.g. called "Event
Check-in Staff" if you want people to only be able to check in event
participants but not access any other CiviCRM data.

If you're using the remote check-in feature, the API user has to have the
"RemoteContacts: Check-In Event Participants" (`remote event checkin`)
permission, and the user contact identified by the `remote_contact_id` must
have the "Remote Check-In User" role.
