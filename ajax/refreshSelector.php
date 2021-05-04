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
 * @author     Valery Fremaux <valery.fremaux@club-internet.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 1999 onwards Martin Dougiamas  http://dougiamas.com
 *
 */

require('../../../config.php');
require_once($CFG->dirroot.'/local/courseindex/explorelib.php');

header("Expires:max-age=0");

// Filter gets the teachinglevels value.

$filteringlevel = optional_param('filter', '', PARAM_TEXT);

if (!empty($filteringlevel)) {
    $catarr = explode('/', $filteringlevel);
    $lastcatid = count($catarr) - 1;
    $filter = $catarr[$lastcatid];
} else {
    $filter = '';
}

$level0sel = optional_param('level0', '', PARAM_INT);
$level1sel = optional_param('level1', '', PARAM_INT);

if (!empty($filter)) {

    $included = course_classification_get_classification($filter);

    // Filters out against exclusions.
    $includedlist = implode("','", array_keys($included));
    if ($disciplins = $DB->get_records_select_menu($CFG->classification_value_table, " id IN ('$includedlist') ", array(), 'sortorder', 'id,value')) {
        $return = '<select name="level1[]" id="id_level1" multiple="multiple" size="10" style="max-width:330px">';
        $alloptionsstr = get_string('alldisciplins', 'local_courseindex');
        $return .= "<option value=\"0\">$alloptionsstr</option>\n";
        foreach ($disciplins as $key => $value) {
            $selected = (in_array($key, $disciplinsel)) ? 'selected="selected"' : '' ;
            $return .= "<option value=\"$key\" $selected >$value</option>\n";
        }
        $return .= '</select>';
    } else {
        $return = '<select name="level1[]" id="id_level1" multiple="multiple" size="10" disabled="disabled" style="max-width:330px">';
        $return .= '</select>';
    }

    echo $return;
} else {
    $disciplinclassifierid = $DB->get_field($CFG->classification_type_table, 'id', array('code' => 'LEVEL2'));
    $disciplins = $DB->get_records_menu($CFG->classification_value_table, 'type', $disciplinclassifierid, 'sortorder', 'id,value');
    if (!empty($disciplins)) {
        $return = '<select name="level2[]" id="id_level2" multiple="multiple" size="10" style="max-width:330px">';
        $alloptionsstr = get_string('alldisciplins', 'local_courseindex');
        $return .= "<option value=\"0\">$alloptionsstr</option>\n";
        foreach ($disciplins as $key => $value) {
            $return .= "<option value=\"$key\">$value</option>\n";
        }
        $return .= '</select>';
    } else {
        $return = '<select name="level2[]" multiple="multiple" size="10" disabled="disabled" style="max-width:330px">';
        $return .= '</select>';
    }
    echo $return;
}