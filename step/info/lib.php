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
 * Information-only email step (no opt-out).
 *
 * @package    lifecyclestep_info
 * @copyright  2026 University of Münster
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_lifecycle\step;

use tool_lifecycle\local\response\step_response;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../emailbaselib.php');

/**
 * Information-only email step (no opt-out).
 *
 * @package    lifecyclestep_info
 * @copyright  2026 University of Münster
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class info extends email_mailer_base {

    /**
     * @inheritdoc
     */
    protected function get_queue_table(): string {
        return 'lifecyclestep_info';
    }

    /**
     * @inheritdoc
     */
    protected function get_notified_table(): string {
        return 'lifecyclestep_info_notified';
    }

    /**
     * @inheritdoc
     */
    protected function get_receive_capability(): string {
        return 'lifecyclestep/info:receiveemail';
    }

    /**
     * @inheritdoc
     */
    protected function get_lang_component(): string {
        return 'lifecyclestep_info';
    }

    /**
     * @inheritdoc
     */
    protected function get_course_process_response(): step_response {
        return step_response::proceed();
    }

    /**
     * @inheritdoc
     */
    protected function includes_response_timeout(): bool {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function get_subpluginname() {
        return 'info';
    }

    /**
     * @inheritdoc
     */
    public function is_stoppable() {
        return true;
    }
}
