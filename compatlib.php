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
 * this file should be used for all tao-specific methods
 * and will be included automatically in local/lib.php along
 * with other core libraries.
 */


function courseindex_get_deletioninprogress_sql() {
    return ' AND (cm.deletioninprogress = 0 OR cm.deletioninprogress IS NULL) ';
}

function courseindex_image_url($image, $component = 'local_courseindex') {
    global $OUTPUT;
    return $OUTPUT->image_url($image, $component);
}