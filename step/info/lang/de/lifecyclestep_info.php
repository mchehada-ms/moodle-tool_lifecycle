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
 * Lang strings for information-only email step.
 *
 * @package    lifecyclestep_info
 * @copyright  2026 University of Münster
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['email_content'] = 'Vorlage für Emails in Klartext';
$string['email_content_help'] = 'Stellen Sie die Vorlage für Emails ein. (in Klartext, alternativ können Sie auch die HTML-Vorlage unten einstellen.)' . '<p>' . 'Sie können die folgenden Platzhalter benutzen:'
        . '<br>' . 'Vorname des Empfängers: ##firstname##'
        . '<br>' . 'Nachname des Empfängers: ##lastname##'
        . '<br>' . 'Betroffene Kurse: ##courses##'
        . '<br>' . 'Kurznamen betroffener Kurse: ##shortcourses##'
        . '</p>';
$string['email_content_html'] = 'HTML-Vorlage für Emails';
$string['email_content_html_help'] = 'Stellen sie die HTML-Vorlage für Emails ein. (in HTML-Format; falls gesetzt, wird es an Stelle der Klartext-Vorlage benutzt!)' . '<p>' . 'Sie können die folgenden Platzhalter benutzen:'
        . '<br>' . 'Vorname des Empfängers: ##firstname##'
        . '<br>' . 'Nachname des Empfängers: ##lastname##'
        . '<br>' . 'Betroffene Kurse: ##courses-html##'
        . '<br>' . 'Kurznamen betroffener Kurse: ##shortcourses-html##'
        . '</p>';
$string['email_subject'] = 'Betreffvorlage';
$string['email_subject_help'] = 'Stellen Sie die Vorlage für den Emailbetreff ein.' . '<p>' . 'Sie können die folgenden Platzhalter benutzen:'
        . '<br>' . 'Vorname des Empfängers: ##firstname##'
        . '<br>' . 'Nachname des Empfängers: ##lastname##'
        . '<br>' . 'Betroffene Kurse: ##courses##'
        . '</p>';
$string['info:receiveemail'] = 'Informations-E-Mail zum Kurslebenszyklus erhalten';
$string['plugindescription'] = 'Informiert Kursverantwortliche per E-Mail ohne Opt-out und ohne Wartezeit im Workflow.';
$string['pluginname'] = 'E-Mail-Schritt (nur Information)';
$string['privacy:metadata:lifecyclestep_info:courseid'] = 'Die ID des Kurses, zu dem E-Mail-Benachrichtigungen versandt werden';
$string['privacy:metadata:lifecyclestep_info:instanceid'] = 'Die ID der Schritt-Instanz, der E-Mails verschickt';
$string['privacy:metadata:lifecyclestep_info:summary'] = 'Informationen, welche Benutzer per E-Mail benachrichtigt werden';
$string['privacy:metadata:lifecyclestep_info:touser'] = 'Die ID des Benutzers, an den eine E-Mail verschickt wird';
$string['privacy:metadata:lifecyclestep_info_notified:courseid'] = 'ID des Kurses, für den die Benachrichtigungs-E-Mail versendet wurde';
$string['privacy:metadata:lifecyclestep_info_notified:summary'] = 'Informationen über Benutzer, denen Benachrichtigungs-E-Mails gesendet wurden';
$string['privacy:metadata:lifecyclestep_info_notified:timemailsent'] = 'Zeitpunkt, zu dem die Benachrichtigungs-E-Mail gesendet wurde';
$string['privacy:metadata:lifecyclestep_info_notified:userid'] = 'ID des Benutzers, dem eine Benachrichtigungs-E-Mail gesendet wurde';
