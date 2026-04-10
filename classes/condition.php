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
 * Spinning Wheel unlock condition.
 *
 * Restricts access to an activity based on whether the current user has
 * been assigned this activity via a Spinning Wheel spin.
 *
 * @package    availability_spinningwheel
 * @copyright  2026 Andrea Juettner, andrea.juettner@eledia.de; AI-assisted by Claude (Anthropic).
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_spinningwheel;

use core_availability\info;
use stdClass;

/**
 * Condition that checks whether a user has unlocked this activity via a Spinning Wheel.
 *
 * @package    availability_spinningwheel
 * @copyright  2026 Andrea Juettner, andrea.juettner@eledia.de; AI-assisted by Claude (Anthropic).
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class condition extends \core_availability\condition {
    /** @var int ID of the Spinning Wheel instance (wheelid). */
    protected int $wheelid;

    /**
     * Constructor.
     *
     * @param stdClass $structure Data structure from JSON decode.
     * @throws \coding_exception If invalid data.
     */
    public function __construct($structure) {
        if (isset($structure->wheelid) && is_number($structure->wheelid)) {
            $this->wheelid = (int)$structure->wheelid;
        } else {
            throw new \coding_exception('Missing or invalid ->wheelid for spinningwheel condition');
        }
    }

    /**
     * Saves condition data back to a structure object.
     *
     * @return stdClass Structure object for JSON encoding.
     */
    public function save(): stdClass {
        return (object)[
            'type' => 'spinningwheel',
            'wheelid' => $this->wheelid,
        ];
    }

    /**
     * Returns a JSON object for unit testing.
     *
     * @param int $wheelid The Spinning Wheel instance ID.
     * @return stdClass Object representing condition.
     */
    public static function get_json(int $wheelid): stdClass {
        return (object)[
            'type' => 'spinningwheel',
            'wheelid' => (int)$wheelid,
        ];
    }

    /**
     * Check whether this activity is available for the given user.
     *
     * Looks up the spinningwheel_spins table to see if the user has been
     * assigned this specific course module via a wheel spin.
     *
     * @param bool $not True if condition is negated.
     * @param info $info Item being checked.
     * @param bool $grabthelot Performance hint for bulk loading.
     * @param int $userid User ID to check.
     * @return bool True if available.
     */
    public function is_available($not, info $info, $grabthelot, $userid): bool {
        global $DB;

        $cmid = $info->get_course_module()->id;

        $unlocked = $DB->record_exists('spinningwheel_spins', [
            'wheelid' => $this->wheelid,
            'selectedcmid' => $cmid,
            'userid' => $userid,
        ]);

        if ($not) {
            $unlocked = !$unlocked;
        }

        return $unlocked;
    }

    /**
     * Get description text shown to users when the condition is not met.
     *
     * @param bool $full True for full description, false for short.
     * @param bool $not True if condition is negated.
     * @param info $info Item being checked.
     * @return string Human-readable description.
     */
    public function get_description($full, $not, info $info): string {
        global $DB;

        $wheelname = $DB->get_field('spinningwheel', 'name', ['id' => $this->wheelid]);
        if (!$wheelname) {
            $wheelname = get_string('missing', 'availability_spinningwheel');
        } else {
            $wheelname = format_string($wheelname);
        }

        if ($not) {
            return get_string('requires_notunlocked', 'availability_spinningwheel', $wheelname);
        }
        return get_string('requires_unlocked', 'availability_spinningwheel', $wheelname);
    }

    /**
     * Return debug string for developer tools.
     *
     * @return string Debug representation.
     */
    protected function get_debug_string(): string {
        return 'wheelid=' . $this->wheelid;
    }

    /**
     * Update any IDs within this condition after restore.
     *
     * @param string $restoreid Restore identifier.
     * @param int $courseid Target course ID.
     * @param \base_logger $logger Logger for messages.
     * @param string $name Name of condition for logging.
     * @return bool True if successfully updated.
     */
    public function update_after_restore($restoreid, $courseid, \base_logger $logger, $name): bool {
        global $DB;

        $rec = \restore_dbops::get_backup_ids_record($restoreid, 'spinningwheel', $this->wheelid);
        if ($rec) {
            $this->wheelid = (int)$rec->newitemid;
            return true;
        }
        $logger->process("Restored item ($name) has missing spinningwheel reference", \backup::LOG_WARNING);
        return false;
    }

    /**
     * Update dependency ID after course module restore.
     *
     * @param string $table Database table name.
     * @param int $oldid Old ID.
     * @param int $newid New ID.
     * @return bool True if updated.
     */
    public function update_dependency_id($table, $oldid, $newid): bool {
        if ($table === 'spinningwheel' && (int)$oldid === $this->wheelid) {
            $this->wheelid = (int)$newid;
            return true;
        }
        return false;
    }
}
