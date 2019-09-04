<?php

namespace Drupal\intercept_event;

use Drupal\date_recur\DateRecurDefaultRRule;
use Drupal\date_recur\DateRecurDefaultRSet;
use Drupal\date_recur\DateRecurRRule as DateRecurRRuleContrib;
use RRule\RfcParser;

class DateRecurRRule extends DateRecurRRuleContrib {

  /**
   * {@inheritdoc}
   */
  public function __construct($rrule, $startDate, $startDateEnd = NULL, $timezone = NULL) {
    $this->originalRuleString = $rrule;
    $this->startDate = $startDate;
    $this->recurTime = $this->startDate->format('H:i');
    if (empty($startDateEnd)) {
      $startDateEnd = clone $startDate;
    }
    $this->startDateEnd = $startDateEnd;
    $this->recurDiff = $this->startDate->diff($startDateEnd);

    $this::parseRrule($rrule, $startDate);
    // TODO: Make this optional in the field configuration.
    if (!empty($this->parts['UNTIL'])) {
      $this->parts['UNTIL']->add(new \DateInterval('P1D'));
    }

    $this->rrule = new DateRecurDefaultRSet();
    $this->rrule->addRRule(new DateRecurDefaultRRule($this->parts));
    if (!empty($this->setParts)) {
      foreach ($this->setParts as $type => $type_parts) {
        foreach ($type_parts as $part) {
          list(, $part) = explode(':', $part);
          switch ($type) {
          case 'RDATE':
            $this->rrule->addDate($part);
            break;
          case 'EXDATE':
            $this->rrule->addExDate($part);
            break;
          case 'EXRULE':
            $this->rrule->addExRule($part);
          }
        }
      }
    }

    if ($timezone) {
      $this->timezone = $timezone;
      $start = clone $this->startDate;
      $start->setTimezone(new \DateTimeZone($this->timezone));
      $this->timezoneOffset = $start->getOffset();
      $this->rrule->setTimezoneOffset($this->timezoneOffset);
    }
  }

    /**
     * {@inheritdoc}
     */
    public function parseRrule($rrule, $startDate, $check_only = FALSE) {
        // Correct formatting.
        if (strpos($rrule, "\n") === FALSE && strpos($rrule, 'RRULE:') !== 0) {
            $rrule = "RRULE:$rrule";
        }

        //$dtstart = 'DTSTART:' . $startDate->format(SELF::RFC_DATE_FORMAT);
        $dtstart = '';

        // Check for unsupported parts.
        $set_keys = ['RDATE', 'EXRULE', 'EXDATE'];
        $rules = $set_parts = [];
        foreach (explode("\n", $rrule) as $key => $part) {
            $els = explode(':', $part);
            if (in_array($els[0], $set_keys)) {
                $set_parts[$els[0]][] = $part;
            }
            else if ($els[0] == 'RRULE') {
                $rules[] = $part;
            }
            else if ($els[0] == 'DTSTART') {
                $dtstart = $part;
            }
            else {
                throw new \InvalidArgumentException("Unsupported line: " . $part);
            }
        }

        if (!count($rules)) {
            throw new \InvalidArgumentException("Missing RRULE line: " . $rrule);
        }
        if (count($rules) > 1) {
            throw new \InvalidArgumentException("More than one RRULE line is not supported.");
        }
        $rrule = $dtstart . "\n" . $rules[0];

        if (empty($parts['WKST'])) {
            $parts['WKST'] = 'MO';
        }
        $this->parts = RfcParser::parseRRule($rrule, $startDate);
        $this->setParts = $set_parts;
    }
}
