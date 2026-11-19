<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Unit tests for the lifecyclestep_info implementation of the privacy API.
 *
 * @package    lifecyclestep_info
 * @copyright  2026 University of Münster
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace lifecyclestep_info\privacy;

use context_course;
use context_system;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use core_privacy\tests\provider_testcase;
use dml_exception;
use stdClass;
use tool_lifecycle\local\entity\step_subplugin;
use tool_lifecycle\local\manager\step_manager;
use tool_lifecycle\local\manager\workflow_manager;

/**
 * Unit tests for the lifecyclestep_info implementation of the privacy API.
 *
 * @package    lifecyclestep_info
 * @copyright  2026 University of Münster
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class provider_test extends provider_testcase {

    /** @var stdClass test user1 */
    private $user1;
    /** @var stdClass test user2 */
    private $user2;
    /** @var stdClass test user3 */
    private $user3;

    /** @var stdClass test course1 */
    private $course1;
    /** @var stdClass test course2 */
    private $course2;
    /** @var stdClass test course3 */
    private $course3;

    /**
     * Basic setup for the provider tests.
     *
     * @throws dml_exception
     */
    public function setUp(): void {
        global $DB;

        parent::setUp();
        $this->resetAfterTest();
        $this->user1 = $this->getDataGenerator()->create_user();
        $this->user2 = $this->getDataGenerator()->create_user();
        $this->user3 = $this->getDataGenerator()->create_user();

        $this->course1 = $this->getDataGenerator()->create_course();
        $this->course2 = $this->getDataGenerator()->create_course();
        $this->course3 = $this->getDataGenerator()->create_course();

        $testworkflow = workflow_manager::create_workflow('testworkflow');
        $step = new step_subplugin('test info', 'info', $testworkflow->id);
        step_manager::insert_or_update($step);

        $record = new stdClass();
        $record->touser = $this->user1->id;
        $record->courseid = $this->course1->id;
        $record->instanceid = $step->id;
        $DB->insert_record('lifecyclestep_info', $record);

        $record = new stdClass();
        $record->touser = $this->user1->id;
        $record->courseid = $this->course2->id;
        $record->instanceid = $step->id;
        $DB->insert_record('lifecyclestep_info', $record);

        $record = new stdClass();
        $record->touser = $this->user2->id;
        $record->courseid = $this->course1->id;
        $record->instanceid = $step->id;
        $DB->insert_record('lifecyclestep_info', $record);

        $record = new stdClass();
        $record->touser = $this->user2->id;
        $record->courseid = $this->course2->id;
        $record->instanceid = $step->id;
        $DB->insert_record('lifecyclestep_info', $record);

        $record = new stdClass();
        $record->touser = $this->user2->id;
        $record->courseid = $this->course3->id;
        $record->instanceid = $step->id;
        $DB->insert_record('lifecyclestep_info', $record);

        $record = new stdClass();
        $record->touser = $this->user3->id;
        $record->courseid = $this->course3->id;
        $record->instanceid = $step->id;
        $DB->insert_record('lifecyclestep_info', $record);
    }

    /**
     * @covers \lifecyclestep_info\privacy\provider::get_contexts_for_userid
     */
    public function test_get_contexts_for_userid(): void {
        $contextlist = provider::get_contexts_for_userid($this->user1->id);
        $this->assertEquals(3, $contextlist->count());
        $this->assertTrue(in_array(context_system::instance(), $contextlist->get_contexts()));
        $this->assertTrue(in_array(context_course::instance($this->course1->id), $contextlist->get_contexts()));
        $this->assertTrue(in_array(context_course::instance($this->course2->id), $contextlist->get_contexts()));
    }

    /**
     * @covers \lifecyclestep_info\privacy\provider::export_user_data
     * @throws dml_exception
     */
    public function test_export_user_data(): void {
        global $DB;
        $course1context = context_course::instance($this->course1->id);
        $course2context = context_course::instance($this->course2->id);
        $approvedcontextlist = new approved_contextlist($this->user1, 'lifecyclestep_info',
            [context_system::instance()->id, $course1context->id, $course2context->id]);
        provider::export_user_data($approvedcontextlist);
        $writer = writer::with_context(context_system::instance());
        $this->assertTrue($writer->has_any_data());
        $recordids = $DB->get_records('lifecyclestep_info', ['touser' => $this->user1->id], '', 'id');
        foreach ($recordids as $id) {
            $exportedrecord = $writer->get_data(['lifecyclestep_info-' . $id->id]);
            $this->assertEquals($this->user1->id, $exportedrecord->touser);
            $this->assertTrue(in_array($exportedrecord->courseid, [$this->course1->id, $this->course2->id]));
        }
    }

    /**
     * @covers \lifecyclestep_info\privacy\provider::get_users_in_context
     * @throws dml_exception
     */
    public function test_get_users_in_context(): void {
        $userlist = new userlist(context_system::instance(), 'lifecyclestep_info');
        provider::get_users_in_context($userlist);
        $this->assertCount(3, $userlist->get_userids());

        $userlist = new userlist(context_course::instance($this->course1->id), 'lifecyclestep_info');
        provider::get_users_in_context($userlist);
        $this->assertEquals(2, count($userlist->get_userids()));
        $this->assertTrue(in_array($this->user1->id, $userlist->get_userids()));
        $this->assertTrue(in_array($this->user2->id, $userlist->get_userids()));
    }

    /**
     * @covers \lifecyclestep_info\privacy\provider::delete_data_for_users
     * @throws dml_exception
     */
    public function test_delete_data_for_users(): void {
        global $DB;
        $approveduserlist = new approved_userlist(context_system::instance(), 'lifecyclestep_info',
            [$this->user1->id, $this->user2->id]);
        $this->assertEquals(6, count($DB->get_records('lifecyclestep_info')));
        provider::delete_data_for_users($approveduserlist);
        $this->assertEquals(1, count($DB->get_records('lifecyclestep_info')));
    }

    /**
     * @covers \lifecyclestep_info\privacy\provider::delete_data_for_users
     * @throws dml_exception
     */
    public function test_delete_data_for_users_coursecontext(): void {
        global $DB;
        $approveduserlist = new approved_userlist(context_course::instance($this->course1->id), 'lifecyclestep_info',
            [$this->user1->id, $this->user2->id]);
        $this->assertEquals(2, count($DB->get_records('lifecyclestep_info', ['courseid' => $this->course1->id])));
        provider::delete_data_for_users($approveduserlist);
        $this->assertEquals(0, count($DB->get_records('lifecyclestep_info', ['courseid' => $this->course1->id])));
        $this->assertEquals(4, count($DB->get_records('lifecyclestep_info')));
    }

    /**
     * @covers \lifecyclestep_info\privacy\provider::delete_data_for_all_users_in_context
     * @throws dml_exception
     */
    public function test_delete_data_for_all_users_in_context(): void {
        global $DB;
        $this->assertEquals(6, count($DB->get_records('lifecyclestep_info')));
        provider::delete_data_for_all_users_in_context(context_system::instance());
        $this->assertEmpty($DB->get_records('lifecyclestep_info'));
    }

    /**
     * @covers \lifecyclestep_info\privacy\provider::delete_data_for_all_users_in_context
     * @throws dml_exception
     */
    public function test_delete_data_for_all_users_in_context_coursecontext(): void {
        global $DB;
        $this->assertEquals(2, count($DB->get_records('lifecyclestep_info', ['courseid' => $this->course1->id])));
        provider::delete_data_for_all_users_in_context(context_course::instance($this->course1->id));
        $this->assertEmpty($DB->get_records('lifecyclestep_info', ['courseid' => $this->course1->id]));
    }

    /**
     * @covers \lifecyclestep_info\privacy\provider::delete_data_for_user
     * @throws dml_exception
     */
    public function test_delete_data_for_user(): void {
        global $DB;
        $approvedcontextlist = new approved_contextlist($this->user2, 'lifecyclestep_info',
            [context_system::instance()->id]);
        $this->assertEquals(6, count($DB->get_records('lifecyclestep_info')));
        provider::delete_data_for_user($approvedcontextlist);
        $this->assertEquals(3, count($DB->get_records('lifecyclestep_info')));
    }

    /**
     * @covers \lifecyclestep_info\privacy\provider::delete_data_for_user
     * @throws dml_exception
     */
    public function test_delete_data_for_user_coursecontext(): void {
        global $DB;
        $approvedcontextlist = new approved_contextlist($this->user1, 'lifecyclestep_info',
            [context_course::instance($this->course1->id)->id, context_course::instance($this->course2->id)->id]);
        $this->assertEquals(6, count($DB->get_records('lifecyclestep_info')));
        provider::delete_data_for_user($approvedcontextlist);
        $this->assertEquals(0, count($DB->get_records('lifecyclestep_info',
            ['courseid' => $this->course1->id, 'touser' => $this->user1->id])));
        $this->assertEquals(0, count($DB->get_records('lifecyclestep_info',
            ['courseid' => $this->course2->id, 'touser' => $this->user1->id])));
    }
}
