<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Front-end class for the Spinning Wheel availability condition.
 *
 * @package    availability_spinningwheel
 * @copyright  2026 Andrea Juettner, andrea.juettner@eledia.de; AI-assisted by Claude (Anthropic).
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_spinningwheel;

/**
 * Front-end class for editing the Spinning Wheel condition in the restriction UI.
 *
 * @package    availability_spinningwheel
 * @copyright  2026 Andrea Juettner, andrea.juettner@eledia.de; AI-assisted by Claude (Anthropic).
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class frontend extends \core_availability\frontend {
    /**
     * Get the language strings needed by the JavaScript.
     *
     * @return array Array of string identifiers.
     */
    protected function get_javascript_strings() {
        return ['title', 'label_wheel', 'error_selectwheel'];
    }

    /**
     * Get the init parameters for the JavaScript.
     *
     * Returns a list of Spinning Wheel instances in the current course.
     *
     * @param \stdClass $course The course object.
     * @param \cm_info|null $cm The course module being edited (or null for section).
     * @param \section_info|null $section The section being edited (or null).
     * @return array Array of parameters for JS init.
     */
    protected function get_javascript_init_params(
        $course,
        ?\cm_info $cm = null,
        ?\section_info $section = null
    ) {
        global $DB;

        $context = \context_course::instance($course->id);
        $wheels = [];

        // Get all Spinning Wheel instances in this course that use activity entry source.
        $records = $DB->get_records('spinningwheel', ['course' => $course->id, 'entrysource' => 2]);
        foreach ($records as $record) {
            $wheels[] = (object)[
                'id' => $record->id,
                'name' => format_string($record->name, true, ['context' => $context]),
            ];
        }

        return [$wheels];
    }

    /**
     * Determine whether this condition can be added.
     *
     * Only allow if there is at least one Spinning Wheel with activity entry source in the course.
     *
     * @param \stdClass $course The course object.
     * @param \cm_info|null $cm The course module being edited.
     * @param \section_info|null $section The section being edited.
     * @return bool True if condition can be added.
     */
    protected function allow_add(
        $course,
        ?\cm_info $cm = null,
        ?\section_info $section = null
    ) {
        $params = $this->get_javascript_init_params($course, $cm, $section);
        return !empty($params[0]);
    }
}
