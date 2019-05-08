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

$url = new moodle_url('/local/courseindex/ajax/courseactions.php', ['id' => $id]);
$context = context_course::instance($course->id);
$PAGE->set_context($context);

$renderer = $PAGE->get_renderer('local_courseindex');

echo $renderer->course_actions($id);