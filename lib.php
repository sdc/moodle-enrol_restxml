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
 * RESTian XML enrolment plugin.
 *
 * This plugin uses RESTful methods to get a user's enrolments from Leap.
 *
 * @package    enrol
 * @subpackage restxml
 * @copyright  2011-2014 Paul Vaughan, South Devon College
 * @author     Paul Vaughan - based on code by Petr Skoda and others
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Mark Lin
 * Curl a shib protected site
 *
 * Found on the web here:
 * http://www.codesphp.com/php-category/curl-a-shib-protected-site.html
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
class ShibCurl {
    private static $ch, $username, $password;
    private static $shib_cookie = "shib-cookie";
    private static $debug = false;

    // Initialize curl handler with SSO username and pass
    function __construct($username,$password) {
        self::$ch = curl_init();
        // Sets up options for the curl handler
        curl_setopt(self::$ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt(self::$ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt(self::$ch, CURLOPT_COOKIEFILE, self::$shib_cookie);
        curl_setopt(self::$ch, CURLOPT_COOKIEJAR, self::$shib_cookie);
        curl_setopt(self::$ch, CURLOPT_COOKIESESSION, 1);
        curl_setopt(self::$ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
        curl_setopt(self::$ch, CURLOPT_FOLLOWLOCATION, 1);
        if ( empty($username) || empty($password)  ) {
            throw new Exception("Empty username or password");
        } else {
            self::$username = $username;
            self::$password = $password;
        }
    }

    // At destruct time, close the curl handler and remove cookie
    function __destruct() {
        curl_close(self::$ch);
            if ( file_exists("./".self::$shib_cookie) ) {
            unlink("./".self::$shib_cookie);
        }
    }

    function setDebug($v) {
        self::$debug = $v;
    }

    // To curl a given url
    public function curl($url, $formvars = array()) {

        curl_setopt(self::$ch, CURLOPT_URL, $url);
        // Post the form
        curl_setopt(self::$ch, CURLOPT_POSTFIELDS, $formvars);

        $out = curl_exec(self::$ch);
        $info = curl_getinfo(self::$ch);

        // Check if we're forward to idp, if not we just need to submit the form to continue
        if ( preg_match('@/idp/Authn@',$info['url']) ) {
            if ( self::$debug ) {
                echo "we're in IDPn";
            }
            $url_auth = $info['url'];

            // Set curl to hit idp next
            curl_setopt(self::$ch, CURLOPT_URL, $url_auth);

            // Set this request to be post
            curl_setopt(self::$ch, CURLOPT_POST, 1);

            // Our lovely headless account
            $forms = "j_username=".urlencode(self::$username)."&j_password=".urlencode(self::$password);

            // Add it to request
            curl_setopt(self::$ch, CURLOPT_POSTFIELDS, $forms);

            // Get the output
            $out = curl_exec(self::$ch);

            if ( self::$debug ) {
                print $out;
            }

            // If we returned to auth form, that means unable to authenticate
            if ( preg_match('@Authentication Failed@', $out) ) {
                throw new Exception('Unable to authenticate');
            }
        }

        // Now we should be properly authenticated.  IDP send us back to SP's shib site.
        // Normally, javascript will kick in and submit the page automatically, but this is curl
        // so we have to do the submit ourself.
        // Get the SP's SAML url from the output
        if ( preg_match('@form action="([^"]+)"@', $out, $matches) ) {

            if ( self::$debug ) {
                echo "we're in sp SAMLn";
            }

            $url_sp = $matches[1];

            // Get the fields, match all input type=hidden
            preg_match_all('@input type="hidden" name="([^"]+)" value="([^"]+)"@i', $out, $matches);
            // Get rid of first element which just contains the whole matched string.
            unset($matches[0]);

            // $matches now contains fields needed SP to validate session, we'll build a POST form around it.
            $forms = array();
            for ( $i = 0; $i < count($matches[1]); $i++ ) {
                $forms[] = $matches[1][$i] .'='. urlencode($matches[2][$i]);
            }

            // Again, setup the path and get the form in
            curl_setopt(self::$ch, CURLOPT_URL, $url_sp);
            curl_setopt(self::$ch, CURLOPT_POSTFIELDS, join('&',$forms));

            // wheeee, finally we get the page.

            $out = curl_exec(self::$ch);
            if ( self::$debug ) {
                print $out;
            }

            // Now we are properly authenticated and got a session with this SP, do last curl to send the form

            // This next line stops everything working properly, so we're not going to use it.
            //curl_setopt(self::$ch, CURLOPT_POSTFIELDS, $formvars);

            $out = curl_exec(self::$ch);
        }

        // return the output
        return $out;
    }
} // End ShibCurl function.


/**
 * RESTian XML enrolment plugin implementation.
 * @author  Paul Vaughan - based on code by Petr Skoda, Martin Dougiamas, Martin Langhoff and others
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrol_restxml_plugin extends enrol_plugin {

    protected $errorlogtag = '[ENROL_RESTXML2] ';
    /**
     * Debugging options
     * 1. Set $fulllogging = true to write useful debug information to /var/log/apache/error.log.
     * 2. Set $epiclogging = true to write EVEN MORE USEFUL debug information (and lots of it).
     */
    protected $fulllogging = true;
    protected $epiclogging = false;

    /**
     * creating an instance of the plugin
     */
    public function get_instance_name($instance) {
        global $DB;

        if (empty($instance->name)) {
            if (!empty($instance->roleid)) {
                $role = ' (' . role_get_name($role, context_course::instance($instance->courseid)) . ')';
            } else {
                $role = '';
            }
            $enrol = $this->get_name();
            return get_string('pluginname', 'enrol_'.$enrol) . $role;
        } else {
            return format_string($instance->name);
        }
    }

    // Users may tweak the roles later.
    public function roles_protected() {
        return false;
    }
    // Users with enrol cap may unenrol other users manually.
    public function allow_enrol(stdClass $instance) {
        return true;
    }
    // Users with unenrol cap may unenrol other users manually.
    public function allow_unenrol(stdClass $instance) {
        return true;
    }
    // Users with manage cap may tweak period and status.
    public function allow_manage(stdClass $instance) {
        return true;
    }

    /**
     * Add new instance of enrol plugin with default settings.
     * @param object $course
     * @return int id of new instance, null if can not be created
     */
    public function add_default_instance($course) {
        $fields = array(
            'status'        => $this->get_config('status'),
            'enrolperiod'   => $this->get_config('enrolperiod', 0),
            'roleid'        => $this->get_config('roleid', 0)
        );
        return $this->add_instance($course, $fields);
    }

    /**
     * Add new instance of enrol plugin.
     * @param object $course
     * @param array instance fields
     * @return int id of new instance, null if can not be created
     */
    public function add_instance($course, array $fields = null) {
        global $DB;

        if ($DB->record_exists('enrol', array('courseid' => $course->id, 'enrol' => 'restxml'))) {
            return null;
        }

        return parent::add_instance($course, $fields);
    }

    // Ideas, code and help from http://docs.moodle.org/dev/Enrolment_plugins#Automated_enrolment.
    public function sync_user_enrolments($user) {
        global $CFG, $DB;

        // Create the current academic year in the same format it appears in the XML; store for later use.
        $now    = time();
        $year   = date('y', $now);
        $month  = date('m', $now);
        if ($month >= 8 && $month <= 12) {
            $acadyear = $year.'/'.($year+1);
        } else {
            $acadyear = ($year-1).'/'.$year;
        }

        if ($this->fulllogging) {
            error_log($this->errorlogtag . '- Starting plugin instance');
        }

        // Quick checks to ensure we have the bits we need to continue.
        if (!is_object($user) or !property_exists($user, 'id')) {
            throw new coding_exception('Invalid $user parameter in sync_user_enrolments()');
            if ($this->fulllogging) {
                error_log($this->errorlogtag . '  Invalid $user parameter: serious error about here.');
            }
        }

        // Checking for the username, password and enrolment URL.
        if (empty($CFG->enrol_restxml_url) || empty($CFG->enrol_restxml_shiblogin) || empty($CFG->enrol_restxml_shibpassword)) {
            if ($this->fulllogging) {
                error_log($this->errorlogtag . '  Missing important $CFG parameters: serious error about here.');
                return false;
            }
        }

        // Get the user's id number.
        if (!property_exists($user, 'idnumber')) {
            if ($this->fulllogging) {
                error_log($this->errorlogtag . '  Missing "idnumber" for '.$user->id);
            }
            $user = $DB->get_record('user', array('id' => $user->id));
        } else {
            if ($this->epiclogging) {
                error_log($this->errorlogtag . ' <"idnumber" found for '.$user->id);
            }
        }

        // Take Shibboleth's "@southdevon.ac.uk" off the username.
        $unameparts = explode('@', $user->username);
        $uname = $unameparts[0];
        // A list of users for which we bail out of doing anything else regarding enrolment.
        // Note: Admin users get excluded from this process automaticaly by Moodle itself.
        if ($uname == 'leapuser') {
            if ($this->fulllogging) {
                error_log($this->errorlogtag . 'x Bailing out! Not processing enrolment for "'.$uname.'"');
            }
            return false;
        } else {
            if ($this->fulllogging) {
                error_log($this->errorlogtag . '  Enrolment/s for "'.$uname.'"');
            }
        }

        // Staff usernames and 8-digit student usernames stay unchanged.
        // E, N and S-prefix 6-digit usernames are changed.
        if (preg_match('/^[s][0-9]{6}/', $uname)) {
            $uname = '10'.substr($uname, 1, 6);
        } else if (preg_match('/^[n][0-9]{6}/', $uname)) {
            $uname = '20'.substr($uname, 1, 6);
        } else if (preg_match('/^[e][0-9]{6}/', $uname)) {
            $uname = '30'.substr($uname, 1, 6);
        }
        if ($this->fulllogging) {
            error_log($this->errorlogtag . '  EBS username is "'.$uname.'"');
        }

        $url = preg_replace('/USERNAME/', $uname, $CFG->enrol_restxml_url);

        // Some epic logging, prob not needed unless extreme debugging is taking place.
        if ($this->epiclogging) {
            error_log($this->errorlogtag . ' <'.$url);
        }

        // Loading XML from a file: testing purposes.
        // $dom = new DOMDocument();
        // $dom->load('file.xml');

        // Loading XML from Leap using ShibCurl.
        $shibcurl = new ShibCurl($CFG->enrol_restxml_shiblogin, $CFG->enrol_restxml_shibpassword);
        $dom = new DOMDocument();
        $dom->loadXML($shibcurl->curl($url));

        // Add this to the received XML so that courses labeled appropriately should enrol everyone.
        $allstudentsxml = "  <!-- BEGIN adding ALLSTUDENTS xml -->
  <event>
    <eventable>
      <status>current</status>
      <course>
        <code>ALLSTUDENTS</code>
        <year>".$acadyear."</year>
      </course>
    </eventable>
  </event>
  <!-- END adding ALLSTUDENTS xml -->
";

        $fragment = $dom->createDocumentFragment();
        $fragment->appendXML($allstudentsxml);
        $dom->documentElement->appendChild($fragment);

        if ($this->epiclogging) {
            error_log($this->errorlogtag . ' <'.$dom->saveXML());
        }

        // Walk through the XML, checking for conditions and pulling out what we need.
        foreach ($dom->getElementsByTagName('event') as $event) {

            // If the enrolment status is 'current'.
            if ($event->getElementsByTagName('status')->item(0)->nodeValue == 'current') {
                $enrolment = $event->getElementsByTagName('code')->item(0)->nodeValue;

                if ($this->fulllogging) {
                    error_log($this->errorlogtag . '  Found current course enrolment "'.$enrolment.'"');
                }

                // If the current academic year (generated above) matches that of the XML
                $acadyearxml = $event->getElementsByTagName('year')->item(0)->nodeValue;
                if ($acadyearxml == $acadyear) {

                    if ($this->fulllogging) {
                        error_log($this->errorlogtag . '  Academic year \''.$acadyear.'\' matches that found in the XML');
                    }

                    // Get the course code from the part of the 'idnumber' field.
                    $courseobjects = $DB->get_records_select('course', 'idnumber LIKE "%'.$enrolment.'%"', array(), '', 'id,idnumber');

                    // If the course the user is enrolled on exists in Moodle.
                    if (!empty($courseobjects)) {

                        if ($this->fulllogging) {
                            error_log($this->errorlogtag . '  Course '.$enrolment.' exists');
                        }

                        // Loop through all courses found.
                        foreach ($courseobjects as $courseobj) {

                            // Get the course context for this course.
                            $context = context_course::instance($courseobj->id);

                            // Get the enrolment plugin instance.
                            $enrolid = $DB->get_record('enrol', array(
                                'enrol' => 'manual',            // Add the enrolments in as manual, to be better managed by teachers/managers.
                                'courseid' => $courseobj->id,   // This course.
                                'roleid' => 5                   // Student role.
                            ), 'id');

                            if (!$enrolid) {
                                // Couldn't find an instance of the manual enrolment plugin. D'oh.
                                if ($this->fulllogging) {
                                    error_log($this->errorlogtag . ' >No manual-student instance for course '.$enrolment);
                                }
                            } else {
                                // A user's course enrolment is utterly seperate to their role on that course.
                                // We check for course enrolment, then seperately we check for role assignment.

                                // Part 1 of 2: Enrol the user onto the course.
                                if ($DB->record_exists('user_enrolments', array('enrolid' => $enrolid->id, 'userid' => $user->id))) {
                                    // User already enrolled.
                                    if ($this->fulllogging) {
                                        error_log($this->errorlogtag . '   User '.$user->id.' already enrolled on course '.$enrolment.'!');
                                    }

                                } else {
                                    if ($this->fulllogging) {
                                        error_log($this->errorlogtag . '   Performing enrolment for '.$uname.'/'.$user->id.' onto course '.$enrolment);
                                    }

                                    // Enrol the user.
                                    $timenow = time();
                                    $newenrolment = new stdClass();
                                    $newenrolment->enrolid      = $enrolid->id;
                                    $newenrolment->userid       = $user->id;
                                    $newenrolment->modifierid   = 406;          // ID of 'leapuser' in live Moodle.
                                    $newenrolment->timestart    = $timenow;
                                    $newenrolment->timeend      = 0;
                                    $newenrolment->timecreated  = $timenow;
                                    $newenrolment->timemodified = $timenow;
                                    if (!$DB->insert_record('user_enrolments', $newenrolment)) {
                                        if ($this->fulllogging) {
                                            error_log($this->errorlogtag . '  >Enrolment failed for '.$uname.'/'.$user->id.' onto course '.$enrolment);
                                        }
                                    } else {
                                        if ($this->fulllogging) {
                                            error_log($this->errorlogtag . '   Enrolment succeeded');
                                        }
                                    }
                                } // End enrolment.

                                // Part 2 of 2: Assign the user's role on the course.
                                $roletoassign = 5; // Student.
                                if ($DB->record_exists('role_assignments', array('roleid' => $roletoassign, 'userid' => $user->id, 'contextid' => $context->id))) {
                                    // User already enrolled.
                                    if ($this->fulllogging) {
                                        error_log($this->errorlogtag . '   User '.$user->id.' already assigned role '.$roletoassign.' on course '.$enrolment.'!');
                                    }

                                } else {
                                    // Assign the user's role on the course.
                                    if (!role_assign($roletoassign, $user->id, $context->id, '', 0, '')) {
                                        if ($this->fulllogging) {
                                            error_log($this->errorlogtag . '  >Role assignment '.$roletoassign.' failed for '.$uname.'/'.$user->id.' onto course '.$enrolment);
                                        }
                                    } else {
                                        if ($this->fulllogging) {
                                            error_log($this->errorlogtag . '   Role assignment '.$roletoassign.' succeeded');
                                        }
                                    }

                                } // End Assignment.

                            } // End enrolment plugin instance.

                        } // End 'this course' looping.

                    // If the course the user is enrolled on does not exist in Moodle.
                    } else {
                        if ($this->fulllogging) {
                            error_log($this->errorlogtag . '  Course '.$enrolment.' doesn\'t exist');
                        }
                    } // End courses loop.

                // Academic year doesn't match.
                } else {
                    // A quick note to say that the reason it failed was because the academic year didn't match.
                    if ($this->fulllogging) {
                        error_log($this->errorlogtag . '  >Academic year \''.$acadyear.'\' did NOT match that found in the XML (\''.$acadyearxml.'\')');
                    }

                } // End academic year not matching.

            // If the status is anything other then 'current'.
            } else {
                if ($this->epiclogging) {
                    error_log($this->errorlogtag . ' <Ignoring "'.$event->getElementsByTagName('status')->item(0)->nodeValue.
                        '" course "'.$event->getElementsByTagName('code')->item(0)->nodeValue.'"');
                }
            } // End 'status' loop.

        } // End XML 'event' loop.

        // Bye bye now.
        if ($this->fulllogging) {
            error_log($this->errorlogtag . '  Finished setting up enrolments for "'.$uname.'"');
        }

        return true;

    } // End public function.

} // End class.
