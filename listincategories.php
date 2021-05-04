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
 * learning path list - meant to duplicate http://aoc.ssatrust.org.uk/index?s=13
 */
require('../../../config.php');
require_once($CFG->dirroot.'/local/courseindex/classes/navigator.class.php');

$url = new moodle_url('/local/courseindex/listincategories.php');
$PAGE->set_url($url);

$type = optional_param('type', 0, PARAM_INT);

require_login();
require_capability('local/courseindex:browse', context_system::instance());

$strheading = get_string('learningpaths', 'local_courseindex');
$PAGE->set_title($strheading);
$PAGE->set_heading($strheading);

$renderer = $PAGE->get_renderer('local_courseindex');

echo $OUTPUT->header();

// Make filters.

$filtervalues = local_courseindex_get_classifications();
$filters = array();
foreach ($filtervalues->allvalues as $f) {
    switch ($f->type) {
        case 'filter':
            if (!array_key_exists($f->typeid, $filters)) {
                $filters[$f->typeid] = array(0 => $f->name . ':');
            }
            $filters[$f->typeid][$f->id] = $f->value;
        break;
        case 'topcategory':
            if (empty($topcat)) {
                $topcat[0] = $f->name;
            }
            $topcat[$f->id] = $f->value;
        break;
        case 'secondcategory':
            if (empty($secondcat)) {
                $secondcat[0] = $f->name;
            }
            $secondcat[$f->id] = $f->value;
        break;
    }
}

// Print filters.

foreach ($filters as $id => $filter) {
    popup_form($url . '?type=', $filter, $id, $type, null);
}

// Print the filtered tree.
echo $OUTPUT->box_start();
$all = $renderer->category_list($str, 0);
if ($all == 0) {
    echo '<br/>';
    echo $OUTPUT->notification(get_string('novisiblecourses', 'local_courseindex'));
} else {
    echo $str;
}
echo $OUTPUT->box_end();
echo '<br/>';
