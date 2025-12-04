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
 * Catalog geomapping
 *
 * @package    local_courseindex
 * @author     Valery Fremaux <valery.fremaux@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 1999 onwards Martin Dougiamas  http://dougiamas.com
 *
 */

require('../../config.php');
require_once($CFG->dirroot.'/local/courseindex/lib.php');
require_once($CFG->dirroot.'/local/courseindex/classes/navigator.class.php');
require_once($CFG->dirroot.'/local/courseindex/classes/helper.class.php');

$SESSION->courseindex = new StdClass;
$SESSION->courseindex->noheaders = optional_param('noheaders', $SESSION->courseindex->noheaders ?? 0, PARAM_BOOL);

$config = get_config('local_courseindex');
if (!local_courseindex_supports_feature('metadata/tunable')) {
    local_courseindex_load_defaults($config);
}

// Hidden key to open the catalog to the unlogged area.
if (empty($config->indexisopen)) {
    require_login();
    $sitecontext = context_course::instance(SITEID);
    require_capability('local/courseindex:browse', $sitecontext);
}

$strheading = get_string('coursemap', 'local_courseindex');

$url = new moodle_url('/local/courseindex/map.php');
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');
$PAGE->set_context(context_system::instance());
$PAGE->navbar->add(get_string('courseindex', 'local_courseindex'));
$PAGE->navbar->add(get_string('map', 'local_courseindex'));
$PAGE->requires->jquery();
$markers = local_courseindex\helper::get_all_geomarkers();
$data = ['markers' => array_values($markers), 'defaultcenterloc' => $config->defaultcenterloc, 'defaultzoom' => $config->defaultzoom];
$PAGE->requires->js_call_amd('local_courseindex/geolocate', 'init', [$data]);
$PAGE->requires->css('/local/courseindex/geomap.css');

$PAGE->set_heading($strheading);
$PAGE->set_title($strheading);

if (local_courseindex_supports_feature('layout/map')) {
    $renderer = $PAGE->get_renderer('local_courseindex', 'extended');
} else {
    $renderer = $PAGE->get_renderer('local_courseindex');
}

// Getting all filters.

$classificationfilters = \local_courseindex\navigator::get_category_filters();
$filters = \local_courseindex\navigator::get_filters_option_values($classificationfilters);

if (empty($SESSION->courseindex->noheaders)) {
    $PAGE->add_body_classes([$config->layoutmodel]);
    echo $OUTPUT->header();
}

if (empty($config->enabled)) {
    throw new moodle_exception('disabled', 'local_courseindex');
}

$classificationfilters = \local_courseindex\navigator::get_category_filters();
$filters = \local_courseindex\navigator::get_filters_option_values($classificationfilters);

$template = new StdClass;
$maprenderer = $PAGE->get_renderer('local_courseindex', 'map');
$template->mapbody = $maprenderer->render_map_body();
if ($config->layoutmodel == 'magistere' && local_courseindex_supports_feature('layout/magistere')) {
    $template->filters = $renderer->filters(0, '/', $filters, "magistere");
} else {
    $template->filters = $renderer->filters(0, '/', $filters, "standard");
}

echo $OUTPUT->render_from_template('local_courseindex/map', $template);

if (empty($SESSION->courseindex->noheaders)) {
    echo $OUTPUT->footer();
}
