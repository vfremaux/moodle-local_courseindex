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
 * @package    local_courseindex
 * @category   local
 * @author     Moodle 2.x Valery Fremaux <valery.fremaux@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * This is part of the dual release distribution system.
 * Tells wether a feature is supported or not. Gives back the
 * implementation path where to fetch resources.
 * @param string $feature a feature key to be tested.
 */
function local_courseindex_supports_feature($feature = null, $getsupported = false) {
    global $CFG;
    static $supports;

    if (!during_initial_install()) {
        $config = get_config('local_courseindex');
    }

    if (!isset($supports)) {
        $supports = array(
            'pro' => array(
                'layout' => array('magistere'),
                'bindings' => array('shop'),
            ),
            'community' => array(
            ),
        );
    }

    if ($getsupported) {
        return $supports;
    }

    // Check existance of the 'pro' dir in plugin.
    if (is_dir(__DIR__.'/pro')) {
        if ($feature == 'emulate/community') {
            return 'pro';
        }
        if (empty($config->emulatecommunity)) {
            $versionkey = 'pro';
        } else {
            $versionkey = 'community';
        }
    } else {
        $versionkey = 'community';
    }

    if (empty($feature)) {
        // Just return version.
        return $versionkey;
    }

    list($feat, $subfeat) = explode('/', $feature);

    if (!array_key_exists($feat, $supports[$versionkey])) {
        return false;
    }

    if (!in_array($subfeat, $supports[$versionkey][$feat])) {
        return false;
    }

    return $versionkey;
}

/**
 * Cut the String content content.
 *
 * @param $str
 * @param $n
 * @param $end_char
 * @return string
 */
function local_courseindex_course_trim_char($str, $n = 500, $endchar = '&#8230;') {
    if (strlen($str) < $n) {
        return $str;
    }

    $str = preg_replace("/\s+/", ' ', str_replace(array("\r\n", "\r", "\n"), ' ', $str));
    if (strlen($str) <= $n) {
        return $str;
    }

    $out = "";
    $small = substr($str, 0, $n);
    $out = $small.$endchar;
    return $out;
}

function local_courseindex_strip_html_tags($text, $format) {
    return strip_tags($text);
}

/**
 * Local clone of local/my for modularity.
 */
function local_courseindex_is_selfenrolable_course($courseorid) {
    global $DB;

    if (is_object($courseorid)) {
        $courseorid = $courseorid->id;
    }

    $params = array('courseid' => $courseorid, 'enrol' => 'self', 'status' => 0);
    if ($enrol = $DB->get_records('enrol', $params)) {
        return $enrol;
    }
    return false;
}

/**
 * Local clone of local/my for modularity.
 */
function local_courseindex_is_guestenrolable_course($courseorid) {
    global $DB;

    if (is_object($courseorid)) {
        $courseorid = $courseorid->id;
    }

    $params = array('courseid' => $courseorid, 'enrol' => 'guest', 'status' => 0);
    if ($DB->count_records('enrol', $params)) {
        return true;
    }
    return false;
}

/**
 * Willby pass internal course protections for course description attached files or thumbnails.
 */
function local_courseindex_pluginfile($course, $cmid, $context, $filearea, $args, $forcedownload, array $options = array()) {

    $systemcontext = context_system::instance();

    // Filearea must contain a real area.
    if (!in_array($filearea, ['overviewfiles', 'rendererimages'])) {
        return false;
    }

    $itemid = (int)array_shift($args);

    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    if ($filearea == 'overviewfiles') {
        if ($context->contextlevel != CONTEXT_COURSE) {
            return false;
        }
        $fullpath = "/$context->id/course/$filearea/$itemid/$relativepath";
    } else {
        if ($context->contextlevel != CONTEXT_SYSTEM) {
            return false;
        }
        $fullpath = "/{$systemcontext->id}/local_courseindex/$filearea/$itemid/$relativepath";
    }

    if (!$file = $fs->get_file_by_hash(sha1($fullpath))) {
        return false;
    }

    // Finally send the file.
    send_stored_file($file, 0, 0, true, $options); // Download MUST be forced - security!
}

/**
 * Checks if at least one filter value is used.
 * @param array $filters the active filters.
 * @return bool
 */
function courseindex_is_filtering($filters) {

    foreach ($filters as $f) {

        if (empty($f->value)) {
            continue;
        }

        if ((count(array_keys($f->value)) == 1) && ($f->value[0] == 0)) {
            // Empty exprimed filter as single element array with 0 in it.
            continue;
        }
        // At least one filter value has been proposed.
        return true;
    }
    return false;
}

/**
 * Get all active filter values from filters.
 * @param array $filters
 * @return array of value ids.
 */
function courseindex_get_all_filter_values($filters) {

    $allvalues = [];

    foreach ($filters as $filter) {
        if (empty($filter->value)) {
            continue;
        }

        if ((count(array_keys($filter->value)) == 1) && ($filter->value[0] == 0)) {
            // Empty exprimed filter as single element array with 0 in it.
            continue;
        }

        $noneofthem = true;
        foreach ($filter->value as $singlevalue) {
            $allvalues[] = $singlevalue;
        }

    }
    return $allvalues;
}