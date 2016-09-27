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
require('../../config.php');
require_once($CFG->dirroot.'/local/courseindex/navigationlib.php');
require_once($CFG->dirroot.'/local/courseindex/explorelib.php');
require_once($CFG->dirroot.'/mod/customlabel/lib.php');
require_once($CFG->dirroot.'/mod/customlabel/locallib.php');

$SESSION->courseindex = new StdClass;
$SESSION->courseindex->headers = optional_param('headers', @$SESSION->courseindex->headers, PARAM_BOOL);

// hidden key to open the catalog to the unlogged area
if (empty($CFG->courseindex_is_open)) {
    require_login();
}

$PAGE->requires->js('/mod/customlabel/js/applyconstraints.js');

$strheading = get_string('explore', 'local_courseindex');

$url = new moodle_url('/local/courseindex/explorer.php');
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());
$PAGE->navbar->add(get_string('courseindex', 'local_courseindex'));
$PAGE->navbar->add(get_string('explore', 'local_courseindex'));

$PAGE->set_heading($strheading);
$PAGE->set_title($strheading);

$renderer = $PAGE->get_renderer('local_courseindex');

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('lpsearch', 'local_courseindex'));

$filters = NULL;

/// getting all filters

$classificationfilters = local_courseindex_get_category_filters();

$i = 0;
foreach ($classificationfilters as $afilter) {
    $options = $DB->get_records_menu($CFG->classification_value_table, array($CFG->classification_value_type_key => $afilter->id), 'value', 'id,value');
    $filters["f$i"] = new StdClass();
    $filters["f$i"]->name = $afilter->name;
    $filters["f$i"]->options = $options;
    $filters["f$i"]->value = optional_param("f$i", '', PARAM_INT);
    $i++;
}

/// including page text
local_print_static_text('courseindex_explore_courses_text', $CFG->wwwroot.'/local/courseindex/explorer.php');

/// print search engine
$search = optional_param('go_search', '', PARAM_RAW);
$freesearch = optional_param('go_freesearch', '', PARAM_RAW);
$specialsearch = optional_param('go_specialsearch', '', PARAM_RAW);

if ($search || $freesearch){
    $form->freesearch = $freesearch;
    $form->lpstatus = optional_param('lpstatus', '', PARAM_INT);
    $form->searchtext = optional_param('searchtext', '', PARAM_RAW);
    $form->title = optional_param('title', '', PARAM_INT);
    $form->description = optional_param('description', '', PARAM_INT);
    $form->information = optional_param('information', '', PARAM_INT);
    $form->targets = '';
    $form->topics = '';
    $form->level0 = optional_param('level0', '', PARAM_INT);
    $form->level1 = optional_param('level1', '', PARAM_INT);
    $form->level2 = optional_param('level2', '', PARAM_INT);
    $searching = true;
    $results = tao_lp_explore($form);
} elseif ($specialsearch) {
    $form->specialsearch = 1;
    $form->lpstatus = optional_param('lpstatus', '', PARAM_INT);
    $form->targets = optional_param('targets', array(), PARAM_INT);
    $form->title = 1;
    $form->description = 1;
    $form->information = 0;
    $form->searchtext = '';
    $searching = true;
    $results = courseindex_explore($form);
} else {
    $searching = false;
    $form = new StdClass();
    $form->lpstatus = 0;
    $form->title = 1;
    $form->description = 1;
    $form->information = 0;
    $form->searchtext = '';
    $form->targets = '';
    $form->topics = '';
}

if (local_has_capability_somewhere('block/course_status:viewcoursestatus')) {
    include($CFG->dirroot.'/local/courseindex/status_filter_form.html');
}

echo $OUTPUT->heading(get_string('bycategory', 'local_courseindex'));
local_print_static_text('courseindex_explore_classifier_text', $CFG->wwwroot.'/local/courseindex/explorer.php');

include($CFG->dirroot.'/local/courseindex/classifier_form.html');

echo $OUTPUT->heading(get_string('bykeyword', 'local_courseindex'));
local_print_static_text('courseindex_explore_freetext_text', $CFG->wwwroot.'/local/courseindex/explorer.php');

include_once $CFG->dirroot.'/local/courseindex/textsearch_form.html';

if (local_courseindex_classification_has_special_fields($specialfields)) {
    echo $OUTPUT->heading(get_string('byspecialcriteria', 'local_courseindex'));
    local_print_static_text('courseindex_explore_targets_text', $CFG->wwwroot.'/local/courseindex/explorer.php');
    include_once $CFG->dirroot.'/local/courseindex/special_form.html';
}

if (!empty($results)) {

    // Calling navigation.
    echo '<a name="results"></a>';

    echo $OUTPUT->box_start('searchresults');
    echo $OUTPUT->heading(get_string('results', 'local_courseindex'));

    print_string('multipleresultsadvice', 'local_courseindex');

    // $restrictions = get_records_list('course_classification', 'course', implode(',', array_keys($results)));
    $rcpoptions = new StdClass();

    // $filters = array();

    $cattree = local_courseindex_generate_navigation(0, '', 0, $filters);
    $catlevels = local_courseindex_get_category_levels();

    $str = '';
    $entrycount = local_courseindex_reduce_tree($cattree, $catlevels, array_keys($results));

    if ($entrycount) {
        echo $renderer->navigation_simple($str, $cattree);
        echo '<br/>';
        echo $str;
    } else {
        if ($searching) {
            echo $OUTPUT->notification(get_string('novisiblecourses', 'local_courseindex'));
        }
    }
    echo $OUTPUT->box_end();
} else {
    if ($searching) {
        echo $OUTPUT->notification(get_string('novisiblecourses', 'local_courseindex'));
    }
}

echo '<br/>';

$browserstr = get_string('browsealltree', 'local_courseindex');
$browserurl = new moodle_url('/local/courseindex/browser.php');
echo '<center><a href="'.$browserurl.'">'.$browserstr.'</a></center>';

echo '<br/>';

echo $OUTPUT->footer();
