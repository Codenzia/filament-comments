<?php

namespace Codenzia\FilamentComments;

class FilamentComments
{
    /**
     * Whether "Add to Calendar" should be shown for event comments.
     * True only when enable_add_to_calendar is true AND at least one
     * calendar_package_classes entry is loadable (package installed).
     */
    public static function isCalendarAvailable(): bool
    {
        if (! config('codenzia-comments.enable_add_to_calendar', false)) {
            return false;
        }

        $classes = config('codenzia-comments.calendar_package_classes', []);

        if (empty($classes)) {
            return true;
        }

        foreach ($classes as $class) {
            if (is_string($class) && class_exists($class)) {
                return true;
            }
        }

        return false;
    }
}
