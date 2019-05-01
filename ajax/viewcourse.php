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
 *
 * @package local_courseindex
 * @category local
 * @author Valery Fremaux
 * @version $Id: format.php,v 1.10 2012-07-30 15:02:46 vf Exp $
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
require('../../../config.php');

$id = required_param('id', PARAM_INT);

if (!$course = $DB->get_record('course', ['id' => $id])) {
    print_error('coursemisconf');
}

$context = context_course::instance($course->id);
$PAGE->set_context($context);

$template = new StdClass;

$template->fullname = $course->fullname;
$template->description = format_text($course->summary);

// Get some special course info in customlabels

if (is_dir($CFG->dirroot.'/mod/customlabel')) {
    // Is customlabels installed ?
    include_once($CFG->dirroot.'/mod/customlabel/xlib.php');

    $template->authors = '';
    $template->metadata = '';
    $template->classifiers = '';

    if ($authors = customlabel_get_authors($course->id)) {
        $template->authors = $authors->make_content();
        $template->usecustomlabelmtd = true;
    }

    if ($coursemetadata = customlabel_get_coursedata($course->id)) {
        $template->metadata = $coursemetadata->make_content();
        $template->usecustomlabelmtd = true;
    }

    if ($courseclassifiers = customlabel_get_classifiers($course->id)) {
        $template->classifiers = $courseclassifiers->make_content();
        $template->usecustomlabelmtd = true;
    }

}

$url = new moodle_url('/local/courseindex/ajax/viewcourse.php', ['id' => $id]);
$PAGE->set_url($url);
$PAGE->set_context($context);

echo $OUTPUT->render_from_template('local_courseindex/magistereviewcourse', $template);
