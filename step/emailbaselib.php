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
 * Shared logic for lifecycle email step subplugins.
 *
 * @package    tool_lifecycle
 * @copyright  2026 University of Münster
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_lifecycle\step;

use tool_lifecycle\local\manager\settings_manager;
use tool_lifecycle\local\manager\step_manager;
use tool_lifecycle\local\response\step_response;
use tool_lifecycle\settings_type;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/lib.php');

/**
 * Shared logic for lifecycle email step subplugins.
 *
 * @copyright  2026 University of Münster
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class email_mailer_base extends libbase {

    /**
     * Database table holding queued mails for this step type.
     *
     * @return string
     */
    abstract protected function get_queue_table(): string;

    /**
     * Database table holding sent-mail log entries for this step type.
     *
     * @return string
     */
    abstract protected function get_notified_table(): string;

    /**
     * Capability required in the course context to receive the email.
     *
     * @return string
     */
    abstract protected function get_receive_capability(): string;

    /**
     * Lang pack component for step settings form strings.
     *
     * @return string
     */
    abstract protected function get_lang_component(): string;

    /**
     * Response after queueing mail entries for a course.
     *
     * @return step_response
     */
    abstract protected function get_course_process_response(): step_response;

    /**
     * Whether the step settings form includes a response timeout field.
     *
     * @return bool
     */
    abstract protected function includes_response_timeout(): bool;

    /**
     * Whether mail templates may use interaction page link placeholders.
     *
     * @return bool
     */
    protected function include_interaction_link_in_placeholders(): bool {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function process_course($processid, $instanceid, $course) {
        global $DB;
        $coursecontext = \context_course::instance($course->id);
        $userstobeinformed = get_enrolled_users(
            $coursecontext,
            $this->get_receive_capability(),
            0,
            'u.id',
            null,
            null,
            null,
            true
        );
        foreach ($userstobeinformed as $user) {
            $record = new \stdClass();
            $record->touser = $user->id;
            $record->courseid = $course->id;
            $record->instanceid = $instanceid;
            $DB->insert_record($this->get_queue_table(), $record);
        }
        return $this->get_course_process_response();
    }

    /**
     * Send emails to all recipients, but only one mail per user.
     */
    public function post_processing_bulk_operation() {
        global $DB, $PAGE;
        $queuetable = $this->get_queue_table();
        $notifiedtable = $this->get_notified_table();
        $stepinstances = step_manager::get_step_instances_by_subpluginname($this->get_subpluginname());
        foreach ($stepinstances as $step) {
            $settings = settings_manager::get_settings($step->id, settings_type::STEP);
            $PAGE->set_context(\context_system::instance());
            $settings['contenthtml'] = format_text($settings['contenthtml'], FORMAT_HTML);

            $userstobeinformed = $DB->get_records($queuetable, ['instanceid' => $step->id], '', 'distinct touser');
            foreach ($userstobeinformed as $userrecord) {
                $user = \core_user::get_user($userrecord->touser);
                $transaction = $DB->start_delegated_transaction();
                $mailentries = $DB->get_records(
                    $queuetable,
                    ['instanceid' => $step->id, 'touser' => $user->id]
                );
                $parsedsettings = $this->replace_placeholders($settings, $user, $mailentries);
                $success = email_to_user(
                    $user,
                    \core_user::get_noreply_user(),
                    $parsedsettings['subject'],
                    $parsedsettings['content'],
                    $parsedsettings['contenthtml']
                );
                if (!$success) {
                    mtrace("E-mail to user {$user->id} failed.");
                }
                $dblogging = get_config('tool_lifecycle', 'logreceivedmails');
                if ($dblogging && $success) {
                    $record = new \stdClass();
                    $record->userid = $user->id;
                    $record->timemailsent = time();
                    foreach ($mailentries as $entry) {
                        $record->courseid = $entry->courseid;
                        $DB->insert_record($notifiedtable, $record);
                    }
                }
                $DB->delete_records($queuetable, ['instanceid' => $step->id, 'touser' => $user->id]);
                $transaction->allow_commit();
            }
        }
    }

    /**
     * Replaces certain placeholders within the mail template.
     *
     * @param string[] $strings array of mail templates.
     * @param \stdClass $user User object.
     * @param array[] $mailentries Array consisting of course entries from the database.
     * @return string[] array of mail text.
     */
    public function replace_placeholders($strings, $user, $mailentries) {
        global $CFG;

        $patterns = [];
        $replacements = [];

        $patterns[] = '##firstname##';
        $replacements[] = $user->firstname;

        $patterns[] = '##lastname##';
        $replacements[] = $user->lastname;

        if ($this->include_interaction_link_in_placeholders()) {
            $interactionlink = new \moodle_url('admin/tool/lifecycle/view.php');
            $url = $CFG->wwwroot . '/' . $interactionlink->out();
            $patterns[] = '##link##';
            $replacements[] = $url;
            $patterns[] = '##link-html##';
            $replacements[] = \html_writer::link($url, $url);
        }

        $courses = [];
        foreach ($mailentries as $entry) {
            $courses[] = get_course($entry->courseid);
        }

        $coursesstrings = [];
        foreach ($courses as $course) {
            $coursesstrings[] = $course->fullname;
        }
        $patterns[] = '##courses##';
        $replacements[] = join("\r\n", $coursesstrings);
        $patterns[] = '##courses-html##';
        $replacements[] = join("<br>", $coursesstrings);

        $coursesstrings = [];
        foreach ($courses as $course) {
            $coursesstrings[] = $course->shortname;
        }
        $patterns[] = '##shortcourses##';
        $replacements[] = join("\r\n", $coursesstrings);
        $patterns[] = '##shortcourses-html##';
        $replacements[] = join("<br>", $coursesstrings);

        return str_ireplace($patterns, $replacements, $strings);
    }

    /**
     * @inheritdoc
     */
    public function instance_settings() {
        $settings = [
            new instance_setting('status', PARAM_INT, true),
            new instance_setting('subject', PARAM_TEXT, true),
            new instance_setting('content', PARAM_RAW, true),
            new instance_setting('contenthtml', PARAM_RAW, true),
        ];
        if ($this->includes_response_timeout()) {
            array_splice($settings, 1, 0, [new instance_setting('responsetimeout', PARAM_INT, false)]);
        }
        return $settings;
    }

    /**
     * @inheritdoc
     */
    public function extend_add_instance_form_definition($mform) {
        $lang = $this->get_lang_component();

        $elementname = 'status';
        $options = [
            self::STEPACTIVE => get_string('active', 'tool_lifecycle'),
            self::STEPSTOPPED => get_string('stopped', 'tool_lifecycle'),
        ];
        $mform->addElement('select', $elementname, get_string('status', 'tool_lifecycle'), $options);
        $mform->addHelpButton($elementname, 'stopped', 'tool_lifecycle');
        $mform->setType($elementname, PARAM_INT);
        $mform->setDefault($elementname, self::STEPACTIVE);

        if ($this->includes_response_timeout()) {
            $elementname = 'responsetimeout';
            $mform->addElement('duration', $elementname, get_string('email_responsetimeout', $lang));
            $mform->setType($elementname, PARAM_INT);
        }

        $elementname = 'subject';
        $mform->addElement('text', $elementname, get_string('email_subject', $lang), ['size' => '80']);
        $mform->addHelpButton($elementname, 'email_subject', $lang);
        $mform->setType($elementname, PARAM_TEXT);

        $elementname = 'content';
        $mform->addElement('textarea', $elementname, get_string('email_content', $lang));
        $mform->addHelpButton($elementname, 'email_content', $lang);
        $mform->setType($elementname, PARAM_TEXT);

        $elementname = 'contenthtml';
        $mform->addElement('editor', $elementname, get_string('email_content_html', $lang));
        $mform->addHelpButton($elementname, 'email_content_html', $lang);
        $mform->setType($elementname, PARAM_RAW);
    }

    /**
     * @inheritdoc
     */
    public function extend_add_instance_form_definition_after_data($mform, $settings) {
        $mform->setDefault(
            'contenthtml',
            ['text' => $settings['contenthtml'] ?? '', 'format' => FORMAT_HTML]
        );
    }

    /**
     * @inheritdoc
     */
    public function get_icon() {
        return 'i/email';
    }
}
