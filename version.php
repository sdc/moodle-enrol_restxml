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
 * RESTian XML enrolment plugin version specification.
 *
 * @package    enrol
 * @subpackage restxml
 * @copyright  2011-2013 Paul Vaughan, South Devon College
 * @author     Paul Vaughan - based on code by Petr Skoda and others
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version        = 2013112000;       // The current plugin version (Date: YYYYMMDDXX).
$plugin->requires       = 2010112400;       // Moodle 2.0+.
$plugin->component      = 'enrol_restxml';  // Full name of the plugin (used for diagnostics).
$plugin->cron           = 0;                // Period for cron to check this plugin (secs).
$plugin->maturity       = MATURITY_STABLE;
$plugin->release        = '2.1';
