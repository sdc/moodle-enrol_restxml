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
 * RESTian XML enrolment plugin settings and presets.
 *
 * @package    enrol
 * @subpackage restxml
 * @copyright  2011-2014 Paul Vaughan, South Devon College
 * @author     Paul Vaughan - based on code by Petr Skoda and others
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {

    // Heading.
    $settings->add(new admin_setting_heading('enrol_restxml_settings', '', get_string('pluginname_desc', 'enrol_restxml')));

    if (!function_exists('curl_init')) {
        $settings->add(new admin_setting_heading('enrol_restxml_noextension', '', get_string('phpldap_nocurlextension', 'enrol_restxml')));
    } else {

        // Default url.
        $settings->add(new admin_setting_configtext('enrol_restxml_url', get_string('url', 'enrol_restxml'),
            get_string('url_desc', 'enrol_restxml'), ''));

        // Magic.
        $options = array('magic', 'more magic');
        $options = array_combine($options, $options);
        $settings->add(new admin_setting_configselect('enrol_restxml_magic', get_string('magic', 'enrol_restxml'), '', '', $options));
    }
}
