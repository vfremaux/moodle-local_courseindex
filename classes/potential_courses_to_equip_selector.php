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
 * @package    local_courseindex
 * @copyright  1999 onwards Martin Dougiamas (http://dougiamas.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_courseindex\selectors;

require_once($CFG->dirroot.'/local/vflibs/classes/course/selector/course_selector_base.php');

defined('MOODLE_INTERNAL') || die();

/**
 * Course selector subclass for the list of potential courses,
 *
 * This returns only self enrollable courses.
 */
class potential_courses_to_map_selector extends \course_selector_base {

    public function find_courses($search) {
        global $DB;

        if (!array_key_exists('blockid', $this->options)) {
            throw new coding_exception('This course selector needs block id to be provided in options as blockid');
        }

        if (!array_key_exists('qcatid', $this->options)) {
            throw new coding_exception('This course selector needs question category id to be provided in options as qcatid');
        }

        $fields      = 'SELECT ' . $this->required_fields_sql('c');
        $countfields = 'SELECT COUNT(c.id)';
        $params = array($this->options['blockid'], $this->options['qcatid']);

        $sql   = " 
            FROM
                {course} c
            JOIN
                {course_categories} cc
            ON
                c.category = cc.id
            JOIN
                {enrol} e
            ON
                e.courseid = c.id
            LEFT JOIN
                {block_auditquiz_mappings} bam
            ON 
                c.id = bam.courseid AND
                bam.blockid = ? AND
                bam.questioncategoryid = ?
            WHERE
                e.enrol = 'self'
        ";

        $order = "
            ORDER BY
                cc.sortorder, c.sortorder
        ";

        // Check to see if there are too many to show sensibly.
        if (!$this->is_validating()) {
            $potentialcoursescount = $DB->count_records_sql($countfields . $sql, $params);
            if ($potentialcoursescount > $this->maxcoursesperpage) {
                return $this->too_many_results($search, $potentialcoursescount);
            }
        }

        // If not, show them.
        $availablecourses = $DB->get_records_sql($fields . $sql . $order, $params);

        if (empty($availablecourses)) {
            return array();
        }

        if ($search) {
            $groupname = get_string('potcoursesmatching', 'block_auditquiz_results', $search);
        } else {
            $groupname = get_string('potcourses', 'block_auditquiz_results');
        }
        return array($groupname => $availablecourses);
    }
}
