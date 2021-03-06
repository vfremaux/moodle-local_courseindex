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
require_once($CFG->dirroot.'/local/courseindex/lib.php');
require_once($CFG->dirroot.'/local/courseindex/classes/navigator.class.php');

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

if ($config->layoutmodel == 'standard') {
    $PAGE->requires->jquery_plugin('animatenumber', 'local_courseindex');
    $PAGE->requires->jquery_plugin('slick', 'local_courseindex');
    $PAGE->requires->css('/local/courseindex/jquery/slick/slick.css');
    $PAGE->requires->js('/local/courseindex/js/slickinit.js');
} else {
    $PAGE->set_pagelayout('simplepage');
    $PAGE->requires->js_call_amd('local_courseindex/magisterecourseindex', 'init');
}

$PAGE->set_heading($strheading);
$PAGE->set_title($strheading);

if (local_courseindex_supports_feature('layout/magistere')) {
    $renderer = $PAGE->get_renderer('local_courseindex', 'extended');
} else {
    $renderer = $PAGE->get_renderer('local_courseindex');
}

// getting all filters

$classificationfilters = \local_courseindex\navigator::get_category_filters();
$filters = \local_courseindex\navigator::get_filters_option_values($classificationfilters);

if (empty($SESSION->courseindex->noheaders)) {
    $PAGE->add_body_classes([$config->layoutmodel]);
    echo $OUTPUT->header();
}

if (empty($config->enabled)) {
    print_error('disabled', 'local_courseindex');
}

$catlevels = \local_courseindex\navigator::get_category_levels();

if ($config->layoutmodel == 'standard') {
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

    $cattree = \local_courseindex\navigator::generate_navigation($catid, $catpath, $catlevels, $filters);

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
} else {
    $cattree = \local_courseindex\navigator::generate_category_tree(0, '', $catlevels, $filters);
    if ($catid) {
        $entries = \local_courseindex\navigator::get_cat_entries($catid, $catpath, $filters);
    } else {
        $entries = [];
        if (!empty($config->topcourselist)) {
            $courseids = explode(',', $config->topcourselist);
            foreach ($courseids as $cid) {
                $entries[$cid] = $DB->get_record('course', ['id' => $cid]);
            }
        }
    }
    echo $renderer->magistere_layout($catid, $catpath, $cattree, $entries, $filters);
}

if (empty($SESSION->courseindex->noheaders)) {
    echo $OUTPUT->footer();
}
