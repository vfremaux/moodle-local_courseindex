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
 * @author     Valery Fremaux <valery.fremaux@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 1999 onwards Martin Dougiamas  http://dougiamas.com
 *
 */
require('../../config.php');
require_once($CFG->dirroot.'/local/courseindex/classes/navigator.class.php');
require_once($CFG->dirroot.'/local/courseindex/explorelib.php');
require_once($CFG->dirroot.'/local/courseindex/lib.php');
require_once($CFG->dirroot.'/mod/customlabel/lib.php');
require_once($CFG->dirroot.'/mod/customlabel/locallib.php');

$SESSION->courseindex = new StdClass;
$SESSION->courseindex->headers = optional_param('headers', @$SESSION->courseindex->headers, PARAM_BOOL);

$config = get_config('local_courseindex');

// hidden key to open the catalog to the unlogged area
if (empty($config->indexisopen)) {
    require_login();
}

$PAGE->requires->js_call_amd('mod_customlabel/customlabel', 'init');
$PAGE->requires->js_call_amd('local_courseindex/courseindex', 'init');
if (local_courseindex_supports_feature('layout/magistere')) {
    $PAGE->requires->js_call_amd('local_courseindex/magisterecourseindex', 'init');
}

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

if (empty($config->enabled)) {
    print_error('disabled', 'local_courseindex');
}

$filters = null;

/// getting all filters

$classificationfilters = \local_courseindex\navigator::get_category_filters();
$catlevels = \local_courseindex\navigator::get_category_levels();

$i = 0;
foreach ($classificationfilters as $afilter) {
    $options = $DB->get_records_menu($config->classification_value_table, array($config->classification_value_type_key => $afilter->id), 'value', 'id,value');
    $filters["f$i"] = new StdClass();
    $filters["f$i"]->name = $afilter->name;
    $filters["f$i"]->options = $options;
    $filters["f$i"]->value = optional_param("f$i", '', PARAM_INT);
    $i++;
}

// Including page text.
local_print_static_text('courseindex_explore_courses_text', $url);

// Print search engine.
$search = optional_param('go_search', '', PARAM_RAW);
$freesearch = optional_param('go_freesearch', '', PARAM_RAW);
$specialsearch = optional_param('go_specialsearch', '', PARAM_RAW);

$indexform = \local_courseindex\explorer::prepare_form('indexsearch');

$form = new StdClass;
if ($search) {
    $form->lpstatus = optional_param('lpstatus', '', PARAM_INT);
    foreach($indexform->fields as $f) {
        $key = $f->name;
        if ($f->multiple) {
            $form->$key = optional_param_array($key, '', PARAM_TEXT);
        } else {
            $form->$key = optional_param($key, '', PARAM_TEXT);
        }
    }
    $form->searchtext = '';
    $form->title = 1;
    $form->description = '';
    $form->information = '';
    $searching = true;
    $results = \local_courseindex\explorer::explore($form);
} else if ($freesearch) {
    $form->freesearch = $freesearch;
    $form->lpstatus = optional_param('lpstatus', '', PARAM_INT);
    $form->searchtext = optional_param('searchtext', '', PARAM_TEXT);
    $form->title = optional_param('title', 1, PARAM_INT);
    $form->description = optional_param('description', '', PARAM_INT);
    $form->information = optional_param('information', '', PARAM_INT);
    $searching = true;
    $results = \local_courseindex\explorer::explore($form);
} else if ($specialsearch) {
    $form->specialsearch = 1;
    $form->lpstatus = optional_param('lpstatus', '', PARAM_INT);
    $form->targets = optional_param('targets', array(), PARAM_INT);
    $form->title = 1;
    $form->description = 1;
    $form->information = 0;
    $form->searchtext = '';
    $searching = true;
    $results = \local_courseindex\explorer::explore($form);
} else {
    $searching = false;
    $form = new StdClass();
    $form->lpstatus = 0;
    $form->title = 1;
    $form->description = 0;
    $form->information = 0;
    $form->searchtext = '';
    $form->targets = '';
    $form->topics = '';
}

if (local_has_capability_somewhere('block/course_status:viewcoursestatus')) {
    include($CFG->dirroot.'/local/courseindex/status_filter_form.html');
}

echo $OUTPUT->heading(get_string('bycategory', 'local_courseindex'));
local_print_static_text('courseindex_explore_classifier_text', $url);

echo $indexform->html;

echo $OUTPUT->heading(get_string('bykeyword', 'local_courseindex'));
local_print_static_text('courseindex_explore_freetext_text', $url);

$template = new StdClass;

$template->searchtext = $form->searchtext;
$template->titlechecked = ($form->title) ? 'checked="checked"' : '' ;
$template->descchecked = ($form->description) ? 'checked="checked"' : '' ;
$template->infochecked = ($form->information) ? 'checked="checked"' : '' ;

echo $OUTPUT->render_from_template('local_courseindex/textsearchform', $template);

if (\local_courseindex\explorer::has_special_fields($specialfields)) {
    echo $OUTPUT->heading(get_string('byspecialcriteria', 'local_courseindex'));
    local_print_static_text('courseindex_explore_targets_text', $url);

    $template = new StdClass;

    list($peoplefieldid, $topicfieldid) = $specialfields;
    if ($peoplefieldid || $topicfieldid) {

        if ($peoplefieldid) {
            $template->peoplefieldid = $peoplefieldid;
            $params = array($CFG->classification_value_type_key => $peoplefieldid);
            $targets = $DB->get_records_menu($CFG->classification_value_table, $params, 'sortorder', 'id, value');
            $targets['0'] = get_string('alltargets', 'local_courseindex');
            ksort($targets);
            $attrs = array('id' => '_targets', 'multiple' => 1, 'size' => 8);
            $template->targesselect = html_writer::select($targets, 'targets[]', $form->targets, array(), $attrs);
        }
        if ($topicfieldid) {
            $template->topicfieldid = $topicfieldid;
            $params = array($CFG->classification_value_type_key => $topicfieldid);
            $topics = $DB->get_records_menu($CFG->classification_value_table, $params, 'sortorder', 'id, value');
            $topics['0'] = get_string('alltopics', 'local_courseindex');
            ksort($topics);
            $attrs = array('id' => '_topics', 'multiple' => 1, 'size' => 8);
            $template->topicsselect = html_writer::select($topics, 'topics[]', $form->topics, array(), $attrs);
        }

        echo $OUTPUT->render_from_template('local_courseindex/specialsearchform', $template);
    }
}

if (!empty($results)) {

    // Calling navigation.
    echo '<a name="results"></a>';

    echo $OUTPUT->heading(get_string('results', 'local_courseindex'));

    print_string('multipleresultsadvice', 'local_courseindex');

    // $restrictions = get_records_list('course_classification', 'course', implode(',', array_keys($results)));
    $rcpoptions = new StdClass();

    // $filters = array();

    echo $OUTPUT->box_start('search-results');
    echo $renderer->search_results($results);
    echo $OUTPUT->box_end();
} else {
    if ($searching) {
        echo $OUTPUT->notification(get_string('novisiblecourses', 'local_courseindex'));
    }
}

echo $renderer->browserlink();

echo $OUTPUT->footer();
