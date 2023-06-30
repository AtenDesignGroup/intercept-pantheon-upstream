<?php

/**
 * @file
 * Token functions for intercept_event.
 */

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;

/**
 * Implements hook_token_info_alter().
 */
function intercept_event_token_info_alter(&$data) {
  $data['tokens']['event_attendance']['first-name'] = [
    'name' => new TranslatableMarkup('User first name'),
    'description' => new TranslatableMarkup('The first name of this event attendance\'s user.'),
  ];
  $data['tokens']['event_attendance']['full-name'] = [
    'name' => new TranslatableMarkup('User full name'),
    'description' => new TranslatableMarkup('The full name of this event attendance\'s user.'),
  ];
  $data['tokens']['event_registration']['first-name'] = [
    'name' => new TranslatableMarkup('User first name'),
    'description' => new TranslatableMarkup('The first name of this event registration\'s user.'),
  ];
  $data['tokens']['event_registration']['full-name'] = [
    'name' => new TranslatableMarkup('User full name'),
    'description' => new TranslatableMarkup('The full name of this event registration\'s user.'),
  ];
  $data['tokens']['event_registration']['addtocalendar-links'] = [
    'name' => new TranslatableMarkup('Add to calendar links'),
    'description' => new TranslatableMarkup('Hyperlinks that will allow the user to add the event details to their calendar.'),
  ];
}

/**
 * Implements hook_tokens_alter().
 */
function intercept_event_tokens_alter(array &$replacements, array $context, BubbleableMetadata $bubbleable_metadata) {
  if ($context['type'] == 'entity' && count($context['tokens']) > 1) {
    if (isset($replacements['[event_registration:field_event:entity:field_date_time]'])) {
      // Get rid of extra white space on dates in emails.
      $replacements['[event_registration:field_event:entity:field_date_time]'] = new TranslatableMarkup(strip_tags(trim(preg_replace('/\s+/', ' ', $replacements['[event_registration:field_event:entity:field_date_time]']->__toString()))));
    }
  }
}

/**
 * Implements hook_token_info().
 */
function intercept_event_token_info() {

  $info['tokens']['flagging']['event-saved-first-name'] = [
    'name' => t('Saved Event - User first name'),
    'description' => t('The first name of this saved event\'s user.'),
  ];
  $info['tokens']['flagging']['event-saved-full-name'] = [
    'name' => t('Saved event - User full name'),
    'description' => t('The full name of this saved event\'s user.'),
  ];
  $info['tokens']['flagging']['event-saved-title'] = [
    'name' => t('Saved event - Title'),
    'description' => t('The title of the saved event node.'),
  ];
  $info['tokens']['flagging']['event-saved-field-date-time'] = [
    'name' => t('Saved event - Date and time'),
    'description' => t('The date and time of the saved event node.'),
  ];
  $info['tokens']['flagging']['event-saved-url'] = [
    'name' => t('Saved event - URL'),
    'description' => t('A plaintext version of the event\'s URL.'),
  ];
  $info['tokens']['event_attendance']['user-login-link'] = [
    'name' => t('Magic login link'),
    'description' => t('A password reset link.'),
  ];
  return $info;
}

/**
 * Implements hook_tokens().
 */
function intercept_event_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  if ($type == 'event_registration' && !empty($data['event_registration'])) {
    /** @var \Drupal\intercept_event\Entity\EventRegistrationInterface $event_registration */
    $event_registration = $data['event_registration'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'first-name':
          $user = $event_registration->getRegistrant();
          $display_name = $user ? $user->getDisplayName() : '';
          $replacements[$original] = $user && $user->first_name ? $user->first_name : $display_name;
          break;

        case 'full-name':
          $user = $event_registration->getRegistrant();
          $display_name = $user ? $user->getDisplayName() : '';
          $replacements[$original] = $user && $user->full_name ? $user->full_name : $display_name;
          break;

        case 'addtocalendar-links':
          // Create all of the hyperlinks in the same way that it's done
          // in intercept AddToCalendar/helpers.js.

          // Load the event registration's referenced event.
          $event = $event_registration->get('field_event')->referencedEntities()[0];
          $title = $event->getTitle();
          $location = $event->get('field_location')->referencedEntities()[0]->getTitle();
          $description = $event->get('field_text_teaser')->value;
          $start_date = $event->get('field_date_time')->start_date;
          $end_date = $event->get('field_date_time')->end_date;
          // Yahoo doesn't utilize endTime so we need to calulate duration
          $duration = \Drupal::service('intercept_core.utility.dates')->duration($start_date, $end_date);
          $duration = date('H:i', mktime(0, $duration)); // Convert to Yahoo's duration format.

          $start_date = \Drupal::service('intercept_core.utility.dates')->getDrupalDate($start_date);
          $end_date = \Drupal::service('intercept_core.utility.dates')->getDrupalDate($end_date);
          // $start_date_readable = \Drupal::service('intercept_core.utility.dates')->convertTimezone($start_date, 'default')->format('Y-m-d\TH:i:s');
          // $end_date_readable = \Drupal::service('intercept_core.utility.dates')->convertTimezone($end_date, 'default')->format('Y-m-d\TH:i:s');
          $start_date_machine = \Drupal::service('intercept_core.utility.dates')->convertTimezone($start_date, 'UTC')->format('Ymd\THis\Z');
          $end_date_machine = \Drupal::service('intercept_core.utility.dates')->convertTimezone($end_date, 'UTC')->format('Ymd\THis\Z');
          $start_date_iso = str_replace('+00:00', '.000Z', \Drupal::service('intercept_core.utility.dates')->convertTimezone($start_date, 'UTC')->format('c'));
          $end_date_iso = str_replace('+00:00', '.000Z', \Drupal::service('intercept_core.utility.dates')->convertTimezone($end_date, 'UTC')->format('c'));
          $url = $GLOBALS['base_url'] . $event->path->alias;
          $random = time();

          $calendars = [
            'Apple or Outlook (.ics)',
            'Google Calendar',
            'Outlook.com',
            'Yahoo! Calendar'
          ];
          $calendar_urls = [];
          foreach ($calendars as $key => $type) {
            $calendar_url = '';
            switch ($type) {
              case 'Google Calendar':
                $calendar_url = 'https://calendar.google.com/calendar/render';
                $calendar_url .= '?action=TEMPLATE';
                $calendar_url .= '&dates=' . $start_date_machine;
                $calendar_url .= '/' . $end_date_machine;
                $calendar_url .= '&location=' . rawurlencode($location);
                $calendar_url .= '&text=' . rawurlencode($title);
                $calendar_url .= '&details=' . rawurlencode($description);
                $calendar_url .= rawurlencode(' <a href="' . $url . '">View Event</a>');
                break;
        
              case 'Outlook.com':
                $calendar_url = 'https://outlook.live.com/calendar/0/deeplink/compose?rru=addevent';
                $calendar_url .= '&startdt=' . $start_date_iso;
                $calendar_url .= '&enddt=' . $end_date_iso;
                $calendar_url .= '&subject=' . rawurlencode($title);
                $calendar_url .= '&location=' . rawurlencode($location);
                $calendar_url .= '&body=' . rawurlencode($description . ' View Event: ' . $url);
                $calendar_url .= '&allday=false';
                $calendar_url .= '&uid=' . $random;
                $calendar_url .= '&path=/calendar/view/Month';
                break;
        
              case 'Apple or Outlook (.ics)':
                // New route should be like event/43249/download.ics.
                $calendar_url = $GLOBALS['base_url'] . '/event/' . $event->id() . '/download.ics';
                break;

              case 'Yahoo! Calendar':
                $calendar_url = 'https://calendar.yahoo.com/?v=60&view=d&type=20';
                $calendar_url .= '&title=' . rawurlencode($title);
                $calendar_url .= '&st=' . $start_date_machine;
                $calendar_url .= '&dur=' . $duration;
                $calendar_url .= '&desc=' . rawurlencode($description);
                $calendar_url .= '&in_loc=' . rawurlencode($location);
                break;
        
            }
            $calendar_urls[$key] = '<a href="' . $calendar_url . '">' . $type . '</a>';
          }
          $replacements[$original] = new FormattableMarkup('Add to my calendar:<br />@calendar_urls', ['@calendar_urls' => implode(', ', $calendar_urls)]);
          break;
      }
    }
  }

  if ($type == 'event_attendance' && !empty($data['event_attendance'])) {
    /** @var \Drupal\intercept_event\Entity\EventAttendanceInterface $event_attendance */
    $event_attendance = $data['event_attendance'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'first-name':
          $user = $event_attendance->getAttendee();
          $replacements[$original] = $user && $user->first_name ? $user->first_name : 'User';
          break;

        case 'full-name':
          $user = $event_attendance->getAttendee();
          $replacements[$original] = $user && $user->full_name ? $user->full_name : 'User';
          break;

        case 'user-login-link':
          $user = $event_attendance->getAttendee();
          $replacements[$original] = \Drupal::request()->getSchemeAndHttpHost()
            . '/event/evaluate/' . $user->id() . '/' . time() . '/' . user_pass_rehash($user, time())
            . '/login?destination=/account/events&field_date_time_value=1&attended=1&registered=1&saved=1';
          break;
      }
    }
  }

  // Saved Events.
  if ($type == 'flagging' && !empty($data['flagging'])) {
    /** @var \Drupal\flag\Entity\Flagging $flag */
    $flagging = $entity = $data['flagging'];
    $saved_event_id = $entity->get('entity_id')->getString();
    $event = Node::load($saved_event_id);
    $user = $flagging->getOwner();

    // Fill in first-name, full-name, title, and field_date_time with
    // info from the flagging (saved event).
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'event-saved-title':
          $replacements[$original] = $event && $event->getTitle() ? $event->getTitle() : 'Library Event';
          break;

        case 'event-saved-field-date-time':
          $dateTime = new DrupalDateTime($event->get('field_date_time')->value, 'UTC');
          $saved_event_date = date('m/d/y \a\t g:i a', $dateTime->getTimestamp());
          $replacements[$original] = $saved_event_date;
          break;

        case 'event-saved-first-name':
          $replacements[$original] = $user && $user->first_name ? $user->first_name : 'User';
          break;

        case 'event-saved-full-name':
          $replacements[$original] = $user && $user->full_name ? $user->full_name : 'User';
          break;

        case 'event-saved-url':
          $url = Url::fromRoute('entity.node.canonical', ['node' => $saved_event_id], ['absolute' => TRUE])->toString();
          $replacements[$original] = $url;
          break;
      }
    }
  }

  if ($type == 'intercept_event') {
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'full-name':
          $user = $event_attendance->getAttendee();
          $replacements[$original] = $user && $user->full_name ? $user->full_name : 'User';
          break;
      }
    }

  }

  return $replacements;
}
