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
 * Unit tests for the lifecyclestep_info.
 *
 * @package    lifecyclestep_info
 * @copyright  2026 University of Münster
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace lifecyclestep_info;

use tool_lifecycle\local\manager\interaction_manager;
use tool_lifecycle\local\response\step_response;
use tool_lifecycle\step\info;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/admin/tool/lifecycle/step/info/lib.php');

// phpcs:disable moodle.PHPUnit.TestCaseCovers.Missing

/**
 * Unit tests for the lifecyclestep_info lib.php.
 *
 * @package    lifecyclestep_info
 * @category   test
 * @coversDefaultClass \tool_lifecycle\step\info
 * @copyright  2026 University of Münster
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class lib_test extends \advanced_testcase {

    /**
     * Tests that the info step proceeds immediately and has no interaction UI.
     */
    public function test_process_course_proceeds_without_interaction(): void {
        global $DB;
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $user = $generator->create_user();
        $generator->enrol_user($user->id, $course->id, 'editingteacher');

        $workflowgen = $generator->get_plugin_generator('tool_lifecycle');
        $workflow = $workflowgen->create_workflow([], []);
        $step = $workflowgen->create_step('info instance', 'info', $workflow->id);
        \tool_lifecycle\local\manager\settings_manager::save_settings(
            $step->id,
            \tool_lifecycle\settings_type::STEP,
            'info',
            [
                'subject' => 'Test',
                'content' => 'Content',
                'contenthtml' => 'Content HTML',
            ]
        );

        $lib = new info();
        $response = $lib->process_course(0, $step->id, $course);

        $this->assertEquals(step_response::proceed(), $response);
        $this->assertFalse(interaction_manager::interaction_available('info'));
        $records = $DB->get_records('lifecyclestep_info', ['instanceid' => $step->id, 'courseid' => $course->id]);
        $this->assertCount(1, $records);
    }

    /**
     * Tests \tool_lifecycle\step\info::replace_placeholders.
     */
    public function test_replace_placeholders(): void {
        $this->resetAfterTest();

        $user1 = $this->getDataGenerator()->create_user(['firstname' => 'Jane', 'lastname' => 'Doe']);
        $course1 = $this->getDataGenerator()->create_course(['fullname' => 'Course 1', 'shortname' => 'C1']);
        $lib = new info();
        $response = $lib->replace_placeholders(
            ["##firstname##\n##courses##"],
            $user1,
            [(object) ['courseid' => $course1->id]]
        );
        $this->assertEquals("Jane\nCourse 1", $response[0]);
    }
}
