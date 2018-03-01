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

$SESSION->courseindex = new StdClass;
$SESSION->courseindex->noheaders = optional_param('noheaders', @$SESSION->courseindex->noheaders, PARAM_BOOL);

$config = get_config('local_courseindex');

// hidden key to open the catalog to the unlogged area.
if (empty($config->indexisopen)) {
    require_login();
    $sitecontext = context_course::instance(SITEID);
    require_capability('local/courseindex:browse', $sitecontext);
}

$catid = optional_param('catid', '', PARAM_INT);
$catpath = optional_param('catpath', '', PARAM_RAW);

$strheading = get_string('courseindex', 'local_courseindex');

$url = new moodle_url('/local/courseindex/browser.php');
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());
$PAGE->navbar->add(get_string('courseindex', 'local_courseindex'));
$PAGE->navbar->add(get_string('browse', 'local_courseindex'));
$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin('animatenumber', 'local_courseindex');
$PAGE->requires->jquery_plugin('slick', 'local_courseindex');
$PAGE->requires->css('/local/courseindex/jquery/slick/slick.css');
$PAGE->requires->js('/local/courseindex/js/slickinit.js');

$PAGE->set_heading($strheading);
$PAGE->set_title($strheading);

$renderer = $PAGE->get_renderer('local_courseindex');

$filters = null;

// getting all filters

$classificationfilters = \local_courseindex\navigator::get_category_filters();

$i = 0;
foreach ($classificationfilters as $afilter) {

    $optionsql = "
        SELECT
            cv.id,
            CONCAT(cv.value, ' (',COUNT(cm.id),')') as value
        FROM
            {{$config->classification_value_table}} cv
        LEFT JOIN
            {{$config->course_metadata_table}} cm
        ON
            {$config->course_metadata_value_key} = cv.id
        LEFT JOIN
            {course} c
        ON
            cm.courseid = c.id
        WHERE
            {$config->classification_value_type_key} = ? AND
            c.visible = 1
        GROUP BY
            cv.id
        ORDER BY
            cv.sortorder
    ";

    $options = $DB->get_records_sql_menu($optionsql, array($afilter->id));

    $filters["f$i"] = new StdClass;
    $filters["f$i"]->name = $afilter->name;
    $filters["f$i"]->options = $options;
    $filters["f$i"]->value = optional_param("f$i", '', PARAM_INT);
    $i++;
}

if (empty($SESSION->courseindex->noheaders)) {
    echo $OUTPUT->header();
}

echo $OUTPUT->heading(get_string('courseindex', 'local_courseindex'), 2);

if (is_dir($CFG->dirroot.'/local/staticguitexts')) {
    // If static gui texts are installed, add a static text to be edited by administrator.
    echo '<div class="static">';
    local_print_static_text('coursecatalog_browser_header', $CFG->wwwroot.'/local/courseindex/browser.php');
    echo '</div>';
}

// making filters.

echo $renderer->filters($catid, $catpath, $filters);

// Calling navigation.

$catlevels = \local_courseindex\navigator::get_category_levels();
$cattree = \local_courseindex\navigator::generate_navigation($catid, $catpath, $catlevels, $filters);

// local_courseindex_reduce_tree($cattree, $catlevels);

echo $renderer->category($cattree, $catpath, \local_courseindex\navigator::count_entries_rec($cattree), 'current', true, $filters);

if ($catid) {
    // Root of the catalog cannot have courses.
    if (!empty($cattree->entries)) {
        echo $renderer->courses_slider(array_keys($cattree->entries));
    }
}

echo $renderer->children($cattree, $catpath, $filters);

if (!empty($config->enableexplorer)) {
    echo $renderer->explorerlink();
}

if (empty($SESSION->courseindex->noheaders)) {
    echo $OUTPUT->footer();
}
