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
 * Compatibility cross-versions
 *
 * @package    local_courseindex
 * @author     Valery Fremaux <valery.fremaux@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 1999 onwards Martin Dougiamas  http://dougiamas.com
 */

/**
 * Adapter of get_deletioninprogress_sql.
 */
function courseindex_get_deletioninprogress_sql() {
    return ' AND (cm.deletioninprogress = 0 OR cm.deletioninprogress IS NULL) ';
}

/**
 * Adapter of image_url.
 */
function courseindex_image_url($image, $component = 'local_courseindex') {
    global $OUTPUT;
    return $OUTPUT->image_url($image, $component);
}

/**
 * Adapter of get_course_link.
 */
function courseindex_get_course_list($course) {
    return new \core_course_list_element($course);
}
