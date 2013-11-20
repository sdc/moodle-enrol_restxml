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
 * RESTian XML enrolment plugin language strings, 'en', 'en-GB'.
 *
 * @package    enrol
 * @subpackage restxml
 * @copyright  2011-2013 Paul Vaughan, South Devon College
 * @author     Paul Vaughan - based on code by Petr Skoda and others
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'RESTian XML';
$string['pluginname_desc'] = 'For Moodle 2. Pulls XML from the given location (ILP), parses it, and enrols users onto those courses.';

$string['phpldap_nocurlextension'] = 'Sorry, cannot continue as you don\'t appear to have cURL (php5-curl) installed.';

$string['shiblogin'] = 'Shibboleth username:';
$string['shiblogin_desc'] = 'The username of the Shibboleth user used to access ILP.';

$string['shibpassword'] = 'Shibboleth password:';
$string['shibpassword_desc'] = 'Well, duh. (For Shibboleth.)';

$string['login'] = 'ILP login:';
$string['login_desc'] = 'The username of the ILP user with appropriate permissions.';

$string['password'] = 'ILP password:';
$string['password_desc'] = 'Well, duh.';

$string['url'] = 'Default XML URL:';
$string['url_desc'] = 'The location of the default XML source, probably within ILP. USERNAME will be replaced with the username of the enrolee.<br /><br />Production: https://leap.southdevon.ac.uk/people/USERNAME/views/courses.xml';

$string['staff_url'] = 'Staff XML URL:';
$string['staff_url_desc'] = 'The location of the XML source for staff. USERNAME will be replaced with the username of the enrolee. <strong>Not yet developed.</strong>';

$string['check_element'] = 'XML course status element to check against:';
$string['check_element_desc'] = 'Look for this element, whhich defines the status of a course.';

$string['check_element_value'] = 'XML course status element\'s value must be:';
$string['check_element_value_desc'] = 'The course status element must be this value to attempt enrolment.';

$string['code_element'] = 'Course code element:';
$string['code_element_desc'] = 'The XML element which contains the course\'s code.';

$string['magic'] = 'Magic:';
$string['magic_desc'] = '<a href="http://www.catb.org/jargon/html/magic-story.html">www.catb.org/jargon/html/magic-story.html</a>';
