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

namespace availability_spinningwheel;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/availability/tests/fixtures/mock_info.php');
require_once($CFG->dirroot . '/availability/tests/fixtures/mock_info_module.php');

/**
 * Unit tests for the Spinning Wheel availability condition.
 *
 * @package    availability_spinningwheel
 * @copyright  2026 Andrea Juettner, andrea.juettner@eledia.de; AI-assisted by Claude (Anthropic).
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \availability_spinningwheel\condition
 */
final class condition_test extends \advanced_testcase {
    /**
     * Test constructor with valid data.
     */
    public function test_constructor_valid(): void {
        $condition = new condition((object)['wheelid' => 42]);
        $saved = $condition->save();
        $this->assertEquals('spinningwheel', $saved->type);
        $this->assertEquals(42, $saved->wheelid);
    }

    /**
     * Test constructor with missing wheelid.
     */
    public function test_constructor_missing_wheelid(): void {
        $this->expectException(\coding_exception::class);
        new condition((object)[]);
    }

    /**
     * Test constructor with invalid wheelid.
     */
    public function test_constructor_invalid_wheelid(): void {
        $this->expectException(\coding_exception::class);
        new condition((object)['wheelid' => 'abc']);
    }

    /**
     * Test save returns correct structure.
     */
    public function test_save(): void {
        $condition = new condition((object)['wheelid' => 7]);
        $result = $condition->save();
        $this->assertIsObject($result);
        $this->assertEquals('spinningwheel', $result->type);
        $this->assertEquals(7, $result->wheelid);
    }

    /**
     * Test get_json helper.
     */
    public function test_get_json(): void {
        $json = condition::get_json(99);
        $this->assertEquals('spinningwheel', $json->type);
        $this->assertEquals(99, $json->wheelid);
    }

    /**
     * Test is_available when user has NOT unlocked the activity.
     */
    public function test_is_available_not_unlocked(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student');

        $spinningwheel = $this->getDataGenerator()->create_module('spinningwheel', [
            'course' => $course->id,
            'entrysource' => 2,
        ]);
        $target = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);

        $modinfo = get_fast_modinfo($course, $user->id);
        $targetcm = $modinfo->get_cm(get_coursemodule_from_instance('page', $target->id)->id);
        $info = new \core_availability\mock_info_module($user->id, $targetcm);

        $condition = new condition((object)['wheelid' => $spinningwheel->id]);

        // No spin recorded — activity should NOT be available.
        $this->assertFalse($condition->is_available(false, $info, false, $user->id));
    }

    /**
     * Test is_available when user HAS unlocked the activity.
     */
    public function test_is_available_unlocked(): void {
        $this->resetAfterTest();
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student');

        $spinningwheel = $this->getDataGenerator()->create_module('spinningwheel', [
            'course' => $course->id,
            'entrysource' => 2,
        ]);
        $target = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        $targetcmid = get_coursemodule_from_instance('page', $target->id)->id;

        // Record a spin that selected this activity.
        $spin = new \stdClass();
        $spin->wheelid = $spinningwheel->id;
        $spin->userid = $user->id;
        $spin->selectedcmid = $targetcmid;
        $spin->selectedtext = 'Test Page';
        $spin->timecreated = time();
        $DB->insert_record('spinningwheel_spins', $spin);

        $modinfo = get_fast_modinfo($course, $user->id);
        $targetcm = $modinfo->get_cm($targetcmid);
        $info = new \core_availability\mock_info_module($user->id, $targetcm);
        $condition = new condition((object)['wheelid' => $spinningwheel->id]);

        $this->assertTrue($condition->is_available(false, $info, false, $user->id));
    }

    /**
     * Test is_available with NOT operator.
     */
    public function test_is_available_negated(): void {
        $this->resetAfterTest();
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student');

        $spinningwheel = $this->getDataGenerator()->create_module('spinningwheel', [
            'course' => $course->id,
            'entrysource' => 2,
        ]);
        $target = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        $targetcmid = get_coursemodule_from_instance('page', $target->id)->id;

        $modinfo = get_fast_modinfo($course, $user->id);
        $targetcm = $modinfo->get_cm($targetcmid);
        $info = new \core_availability\mock_info_module($user->id, $targetcm);
        $condition = new condition((object)['wheelid' => $spinningwheel->id]);

        // NOT unlocked — with negation should return true.
        $this->assertTrue($condition->is_available(true, $info, false, $user->id));

        // Now unlock it.
        $spin = new \stdClass();
        $spin->wheelid = $spinningwheel->id;
        $spin->userid = $user->id;
        $spin->selectedcmid = $targetcmid;
        $spin->selectedtext = 'Test Page';
        $spin->timecreated = time();
        $DB->insert_record('spinningwheel_spins', $spin);

        // Unlocked — with negation should return false.
        $this->assertFalse($condition->is_available(true, $info, false, $user->id));
    }

    /**
     * Test is_available is per-user.
     */
    public function test_is_available_per_user(): void {
        $this->resetAfterTest();
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user1->id, $course->id, 'student');
        $this->getDataGenerator()->enrol_user($user2->id, $course->id, 'student');

        $spinningwheel = $this->getDataGenerator()->create_module('spinningwheel', [
            'course' => $course->id,
            'entrysource' => 2,
        ]);
        $target = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        $targetcmid = get_coursemodule_from_instance('page', $target->id)->id;

        // Only user1 unlocks.
        $spin = new \stdClass();
        $spin->wheelid = $spinningwheel->id;
        $spin->userid = $user1->id;
        $spin->selectedcmid = $targetcmid;
        $spin->selectedtext = 'Test Page';
        $spin->timecreated = time();
        $DB->insert_record('spinningwheel_spins', $spin);

        $modinfo = get_fast_modinfo($course, $user1->id);
        $targetcm = $modinfo->get_cm($targetcmid);
        $info = new \core_availability\mock_info_module($user1->id, $targetcm);
        $condition = new condition((object)['wheelid' => $spinningwheel->id]);

        $this->assertTrue($condition->is_available(false, $info, false, $user1->id));
        $this->assertFalse($condition->is_available(false, $info, false, $user2->id));
    }

    /**
     * Test get_description with existing wheel.
     */
    public function test_get_description(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $spinningwheel = $this->getDataGenerator()->create_module('spinningwheel', [
            'course' => $course->id,
            'name' => 'My Test Wheel',
        ]);

        $info = new \core_availability\mock_info($course);
        $condition = new condition((object)['wheelid' => $spinningwheel->id]);

        $desc = $condition->get_description(true, false, $info);
        $this->assertStringContainsString('My Test Wheel', $desc);

        $descnot = $condition->get_description(true, true, $info);
        $this->assertStringContainsString('My Test Wheel', $descnot);
        $this->assertStringContainsString('not', $descnot);
    }

    /**
     * Test get_description with missing wheel.
     */
    public function test_get_description_missing_wheel(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $info = new \core_availability\mock_info($course);

        $condition = new condition((object)['wheelid' => 999999]);
        $desc = $condition->get_description(true, false, $info);
        $this->assertStringContainsString(
            get_string('missing', 'availability_spinningwheel'),
            $desc
        );
    }

    /**
     * Test get_debug_string.
     */
    public function test_get_debug_string(): void {
        $condition = new condition((object)['wheelid' => 42]);
        $rc = new \ReflectionClass($condition);
        $method = $rc->getMethod('get_debug_string');
        $this->assertEquals('wheelid=42', $method->invoke($condition));
    }
}
