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

// hidden key to open the catalog to the unlogged area
if (empty($config->indexisopen)) {
    require_login();
    $sitecontext = context_course::instance(SITEID);
    require_capability('local/courseindex:browse', $sitecontext);
}

$cat = optional_param('cat', '', PARAM_INT);
$level = optional_param('level', 0, PARAM_INT);
$catpath = optional_param('catpath', '', PARAM_RAW);

$strheading = get_string('courseindex', 'local_courseindex');

$url = new moodle_url('/local/courseindex/browser.php');
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());
$PAGE->navbar->add(get_string('courseindex', 'local_courseindex'));
$PAGE->navbar->add(get_string('browse', 'local_courseindex'));
$PAGE->requires->jquery_plugin('animatenumber', 'local_courseindex');

$PAGE->set_heading($strheading);
$PAGE->set_title($strheading);

$renderer = $PAGE->get_renderer('local_courseindex');

$filters = null;

// getting all filters

$classificationfilters = local_courseindex_get_category_filters();

$i = 0;
foreach ($classificationfilters as $afilter) {
    $options = $DB->get_records_menu($config->classification_value_table, array($config->classification_value_type_key => $afilter->id), 'value', 'id,value');
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
echo '<div class="static">';
local_print_static_text('coursecatalog_browser_header', $CFG->wwwroot.'/local/courseindex/browser.php');
echo '</div>';

// making filters.

$browserurl = '/local/courseindex/browser.php';

echo '<form name="filtering" action="'.$browserurl.'" method="post">';
echo '<input type="hidden" name="cat" value="'.$cat.'"/>';
echo '<input type="hidden" name="level" value="'.$level.'"/>';
echo '<input type="hidden" name="catpath" value="'.$catpath.'"/>';

if (!empty($filters)) {
    echo '<fieldset>';
    echo '<table cellspacing="10"><tr><td>';
    foreach($filters as $key => $afilter) {
        if ($key != 'wwwroot') {
            echo '<b><span style="font-size:80%">'.$afilter->name.' :</span></b><br/>';
            echo html_writer::select($afilter->options, $key, $afilter->value);
            echo '</td><td>';
        }
    }
    $strreload = get_string('reload', 'local_courseindex');
    
    echo '<td><input type="submit" name="go_btn" value="'.$strreload.'" /></td>';
    echo '</td></tr></table>';
    echo '</fieldset>';
}

echo '</form>';

// calling navigation.

$simmplenav = false;
if ($simmplenav) {
    $cattree = local_courseindex_generate_navigation($cat, $catpath, $level, $filters);
    $str = '';
    $catlevels = local_courseindex_get_category_levels();
    local_courseindex_reduce_tree($cattree, $catlevels);
    $renderer->navigation_simple($str, $cattree);
    echo '<br/>';
    echo $str;
    echo '<br/>';
    echo '<br/>';
} else {
    $str = '';
    // TODO Actually not completely fixed. Category browsing
    // fails in applying constraints at level > 1
    $cattree = local_courseindex_generate_navigation($cat, $catpath, $level, $filters);
    $catlevels = local_courseindex_get_category_levels();

    // echo $renderer->cat_struct_debug($cattree);

    local_courseindex_reduce_tree($cattree, $catlevels);

    // echo $renderer->cat_struct_debug($cattree);

    $rcpoptions = null;
    $allcoursecount = $renderer->navigation($str, $cattree, $catpath, 2, DISPLAY_FILES_FIRST_LEVEL, 0, false, $rcpoptions);

    echo $OUTPUT->box_start('lpbrowsingarea');
    echo $str;
    echo '<br/>';
    echo '<br/>';
    $parentcat = $cattree->parent->id;
    $parentcatpath = $cattree->parent->catpath;
    $browseparent = '';

    if ($level > 0) {
        $parentlevel = $level - 1;
        $browseupstr = get_string('browseup', 'local_courseindex');
        $browserurl = new moodle_url('/local/courseindex/browser.php', array('cat' => $parentcat, 'catpath' => $parentcatpath, 'level' => $parentlevel));
        $browseparent = ' - <a href="'.$browserurl.'">'.$browseupstr.'</a>';
    }

    if ($cat) {
        // print back to root
        $strbacktoroot = get_string('backtoroot', 'local_courseindex');
        $topcatbrowserurl = new moodle_url('/local/courseindex/browser.php', array('cat' => 0));
        echo '<div id="backtorootlink" style="text-align:center"><a href="'.$topcatbrowserurl.'">'.$strbacktoroot.'</a>'.$browseparent.'</div>';
    }
    echo '<br/>';
    if (!$allcoursecount) {
        echo $OUTPUT->notification(get_string('novisiblecoursesinsubtree', 'local_courseindex'));
    }
    echo $OUTPUT->box_end();

}
echo '<br/>';
if (!empty($config->enableexplorer)) {
    $searchstr = get_string('searchintree', 'local_courseindex');
    $exploreurl = new moodle_url('/local/courseindex/explorer.php');
    echo '<center><a href="'.$exploreurl.'">'.$searchstr.'</a></center>';
    echo '<br/>';
}

if (empty($SESSION->courseindex->noheaders)) {
    echo $OUTPUT->footer();
}
