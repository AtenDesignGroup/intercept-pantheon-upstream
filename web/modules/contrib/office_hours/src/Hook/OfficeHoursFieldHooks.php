<?php

namespace Drupal\office_hours\Hook;

use Drupal\Core\Field\FieldTypeCategoryManagerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Contains Field and Help hooks.
 *
 * Class is declared as a service in services.yml file.
 *
 * @see https://drupalize.me/blog/drupal-111-adds-hooks-classes-history-how-and-tutorials-weve-updated
 */
class OfficeHoursFieldHooks {

  /**
   * Implements hook_field_type_category_info_alter().
   */
  #[Hook('field_type_category_info_alter')]
  public function field_type_category_info_alter(&$definitions) {
    // The 'office_hours' field type has no separate category defined.
    // It belongs in the 'general' category, so the libraries are attached here.
    $definitions[FieldTypeCategoryManagerInterface::FALLBACK_CATEGORY]['libraries'][] = 'office_hours/office_hours.custom-icon';
  }

  /**
   * Implements hook_help() on 'help.page.office_hours'.
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match) {
    switch ($route_name) {
      case 'help.page.office_hours':
        $output = '<h3>' . t('About') . '</h3>';
        $output .= '<p>' . t('The Office Hours module provides a "weekly office hours" field type. It allows to add a field to any Content type, in order to display when a location is open or closed on a weekly basis.') . '</p>';
        $output .= '<h4>' . t('Functionalities') . '</h4>';
        $output .= '<p>' . t('The module defines 2 widgets:') . '</p>';
        $output .= '<ul><li>' . t('<strong>Week</strong> widget shows all days of the week;') . '</li>';
        $output .= '<li>' . t('<strong>List</strong> widget allows to add a time slot day by day.') . '</li></ul>';
        $output .= '<p>' . t('The widget provides:') . '</p>';
        $output .= '<ul><li>' . t('<i>allowed hours</i> restrictions;') . '</li>';
        $output .= '<li>' . t('input validation;') . '</li>';
        $output .= '<li>' . t('use of either a 24 or 12 hour clock;') . '</li>';
        $output .= '<li>' . t('using 1, 2 or even more <i>time blocks</i> per day;') . '</li>';
        $output .= '<li>' . t('a comment per time slot (E.g., <i>First friday of the month</i>);') . '</li>';
        $output .= '<li>' . t('links to easily copy or delete time slots;') . '</li>';
        $output .= '<li>' . t('adding exception days;') . '</li></ul>';
        $output .= '<p>' . t('The formatter provides:') . '</p>';
        $output .= '<ul><li>' . t("an 'open now'/'closed now' indicator (formatter);") . '</li>';
        $output .= '<li>' . t('options to group days (E.g., <i>Mon-Fri 12:00-22:00</i>);') . '</li>';
        $output .= '<li>' . t('options to maintain <i>Exception days</i>;') . '</li>';
        $output .= '<li>' . t('options to display a <i>Select List</i>, that can be opened/closed;') . '</li>';
        $output .= '<li>' . t('integration for openingHours metadata;') . '</li>';
        $output .= '<li>' . t('integration for openingHoursSpecification metadata;') . '</li>';
        $output .= '<li>' . t('a hook to alter the formatted time (see office_hours.api.php);') . '</li>';
        $output .= '<li>' . t('a hook to alter the <i>current</i> time for timezones (see office_hours.api.php).') . '</li></ul>';
        return $output;

      default:
        return '';
    }
  }

}
