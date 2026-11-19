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
 * Step subplugin for sending out emails to the teacher.
 * @package lifecyclestep_email
 * @copyright  2017 Tobias Reischmann WWU
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace tool_lifecycle\step;

use tool_lifecycle\local\manager\process_manager;
use tool_lifecycle\local\manager\settings_manager;
use tool_lifecycle\local\response\step_response;
use tool_lifecycle\settings_type;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../emailbaselib.php');

define('EMAIL_PROCDATA_KEY_KEEP', 'keep');

/**
 * Step subplugin for sending out emails to the teacher with opt-out.
 *
 * @package lifecyclestep_email
 * @copyright  2017 Tobias Reischmann WWU
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class email extends email_mailer_base {

    /**
     * @inheritdoc
     */
    protected function get_queue_table(): string {
        return 'lifecyclestep_email';
    }

    /**
     * @inheritdoc
     */
    protected function get_notified_table(): string {
        return 'lifecyclestep_email_notified';
    }

    /**
     * @inheritdoc
     */
    protected function get_receive_capability(): string {
        return 'lifecyclestep/email:preventdeletion';
    }

    /**
     * @inheritdoc
     */
    protected function get_lang_component(): string {
        return 'lifecyclestep_email';
    }

    /**
     * @inheritdoc
     */
    protected function get_course_process_response(): step_response {
        return step_response::waiting();
    }

    /**
     * @inheritdoc
     */
    protected function includes_response_timeout(): bool {
        return true;
    }

    /**
     * @inheritdoc
     */
    protected function include_interaction_link_in_placeholders(): bool {
        return true;
    }

    /**
     * Processes the course in status waiting and returns a response.
     *
     * @param int $processid of the respective process.
     * @param int $instanceid of the step instance.
     * @param object $course to be processed.
     * @return step_response
     */
    public function process_waiting_course($processid, $instanceid, $course) {
        $process = process_manager::get_process_by_id($processid);
        if (null !== $process && $process->timestepchanged < time() - settings_manager::get_settings(
            $instanceid,
            settings_type::STEP
        )['responsetimeout']) {
            return step_response::proceed();
        }
        return step_response::waiting();
    }

    /**
     * @inheritdoc
     */
    public function get_subpluginname() {
        return 'email';
    }

    /**
     * @inheritdoc
     */
    public function is_stoppable() {
        return true;
    }
}
