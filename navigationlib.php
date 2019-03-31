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
namespace local_courseindex;

use \StdClass;
use \context_coursecat;

defined('MOODLE_INTERNAL') || die();

// Constants for display control.

if (!defined('DISPLAY_CATEGORIES')) {
    define('DISPLAY_CATEGORIES', 0);
}

if (!defined('DISPLAY_FILES')) {
    define('DISPLAY_FILES', 1);
}

if (!defined('DISPLAY_FILES_FIRST_LEVEL')) {
    define('DISPLAY_FILES_FIRST_LEVEL', 2);
}

if (is_dir($CFG->dirroot.'/blocks/course_status')) {
    require_once($CFG->dirroot.'/blocks/course_status/locallib.php');
}

class navigator {

    /**
     * make a full navigation category tree. Recursive.
     * elements are shown in category if they have no other qualifiers setup
     * @param int $startcat if startcat is null, makes full navigation tree from the start. If not null, displays content of a node
     * @param string $catpath contains tree elements representing the upper branch over the start point.
     * @param int $levelix the categorization level the startcat is located at
     * @param array $filters are there filters to apply ? Here they are described.
     * @param $return
     */
    public static function generate_navigation($startcat = null, $catpath = '', $catlevels, &$filters, $restrictions = array()) {
        global $CFG, $DB;

        $config = get_config('local_courseindex');
        $publishconfig = get_config('block_publishflow');

        if (empty($catpath)) {
            $levelix = 0;
            $catpatharr = array();
        } else {
            $catpatharr = explode(',', $catpath);
            $levelix = count($catpatharr);
        }

        if (empty($catlevels)) {
            // The course index seems being empty or having no levels.
            $rootcat = new StdClass();
            $rootcat->id = 0;
            $rootcat->parent = new StdClass();
            $rootcat->parent->id = 0;
            $rootcat->parent->catpath = '';
            $rootcat->name = get_string('root', 'local_courseindex');
            $rootcat->cats = array();
            $rootcat->entries = array();
            return $rootcat;
        } else if (empty($startcat)) {
            $rootcat = new StdClass();
            $rootcat->id = 0;
            $rootcat->parent = new StdClass();
            $rootcat->parent->id = 0;
            $rootcat->parent->catpath = '';
            $rootcat->name = get_string('root', 'local_courseindex');
            $rootcat->cats = array();
            $rootcat->entries = array();
        } else {
            $rootcat = $DB->get_record($config->classification_value_table, array('id' => $startcat));
            $rootcat->name = $rootcat->value;

            /*
             * We can compute parent from catpath, as the last id in the path.
             * Path has the full path until the actual cat.
             */
            $pathparts = $catpatharr; // Clone the array.
            array_pop($pathparts); // Remove ourself.
            $parentcat = array_pop($pathparts); // Get parent.
            $parentcatpath = implode(',', $pathparts); // 
            $rootcat->parent = new Stdclass;
            $rootcat->parent->id = 0 + $parentcat;
            $rootcat->parent->catpath = $parentcatpath;
        }

        // Compute some filtering sql from filter selects in the browser.
        // These are user choosen on catalog interface.
        $filtered = array();
        if (!empty($filters)) {
            foreach ($filters as $key => $filter) {
                // Apply only classification filters.
                if (preg_match("/^f/", $key) && $filter->value) {
                    $filtered[] = $filter->value;
                }
            }
        }

        // We shall check for some formats to display.

        if (@$publishconfig->moodlenodetype == 'learningarea') {
            // If we use the publishflow, the publishing status may impact what we can see.
            $filterclause = "
                AND c.category != {$publishconfig->closedcategory} AND
                c.category != {$publishconfig->freeuseclosedcategory} AND
                c.category != {$publishconfig->deploycategory}
            ";
            $formatclause = '';
            if ($formats = self::browser_accept_formats()) {
                foreach ($formats as $format) {
                    $formatclauseitems[] = " c.format = '$format' ";
                }
                $formatclause = ' AND ('.implode('OR', $formatclauseitems). ') ';
            }
        } else {
            // This is for standard moodle platforms.
            $formatclause = '';
            if ($formats = @$config->formats) {
                foreach ($formats as $format) {
                    $formatclauseitems[] = " c.format = '$format' ";
                }
                $formatclause = ' AND ('.implode('OR', $formatclauseitems). ') '; 
            }
        }

        // Get all course info
        $sql = "
            SELECT DISTINCT
                c.id,
                c.category,
                c.shortname,
                c.fullname,
                c.visible,
                c.timecreated,
                c.summary,
                GROUP_CONCAT(ccv.value) as tags,
                GROUP_CONCAT(ccv.id) as tagids
            FROM
                {course} c,
                {{$config->course_metadata_table}} cc,
                {{$config->classification_value_table}} ccv
            WHERE
                c.id = cc.{$config->course_metadata_course_key} AND
                cc.{$config->course_metadata_value_key} = ccv.id
                $formatclause
            GROUP BY
                c.id
            ORDER BY
                c.sortorder
        ";

        $allcourses = $DB->get_records_sql($sql);
        $allcoursesbytag = array();

        $debug = optional_param('debug', false, PARAM_BOOL);

        // We have all courses. Now scan them and arrange them.
        if ($allcourses) {
            foreach ($allcourses as $cid => &$course) {
                $course->cats = explode(',', $course->tags);
                $course->catids = explode(',', $course->tagids);

                foreach ($course->catids as $catid) {
                    $allcoursesbytag[$catid][$cid] = $course;
                }

                // Keep only courses that are matching start point, that is, having all parent cats in metadata.
                // All the remaining courses have at least all parents bound, but may be in subcategories.
                // If startcat is null, parents is empty and all courses are kept.
                foreach ($catpatharr as $parentcatid) {

                    // Matching parent tree condition ?
                    if (!in_array($parentcatid, $course->catids)) {
                        unset($allcourses[$cid]);
                        if ($debug) {
                            echo "Discard $cid by parent tree<br>";
                        }
                        continue 2;
                    }
                }

                // Matching filters ?
                foreach ($filtered as $keepcat) {
                    if (!in_array($keepcat, $course->catids)) {
                        unset($allcourses[$cid]);
                        if ($debug) {
                            echo "Discard $cid by filter<br>";
                        }
                        continue 2;
                    }
                }

                // Matching restrictions ?
                foreach ($restrictions as $reject) {
                    if (in_array($keepcat, $course->catids)) {
                        unset($allcourses[$cid]);
                        if ($debug) {
                            echo "Discard $cid by restriction<br>";
                        }
                        continue 2;
                    }
                }

                // If course status block is used to control course publishing workflow, then filter also.
                if (is_dir($CFG->dirroot.'/blocks/course_status')) {
                    // Are we using also course_status block for publishing control ?
                    $laststatetime = $DB->get_field('block_course_status_history', 'MAX(timestamp)', array('courseid' => $cid));
                    $laststate = $DB->get_field('block_course_status_history', 'approval_status_id', array('courseid' => $cid, 'timestamp' => $laststatetime));
                    if ($laststate > COURSE_STATUS_PUBLISHED) {
                        unset($allcourses[$cid]);
                        if ($debug) {
                            echo "Discard $cid by status<br>";
                        }
                        continue;
                    }
                }
            }
        }

        $rootcat->cats = array();

        // Get candidate subcategories.
        if ($levelix < count($catlevels)) {
            $sql = "
                SELECT DISTINCT
                   cv.id,
                   cv.value,
                   ct.sortorder AS typesortorder
                FROM
                   {{$config->classification_value_table}} cv,
                   {{$config->classification_type_table}} ct
                WHERE
                    ct.id = cv.{$config->classification_value_type_key} AND
                    cv.{$config->classification_value_type_key} = {$catlevels[$levelix]->id}
                ORDER BY
                    cv.sortorder
            ";
            $levelcats = $DB->get_records_sql($sql);

            if ($levelcats) {
                $coursescatchedbysubcat = array();
                foreach ($levelcats as $acat) {

                    if ($rootcat->id && !self::navigation_match_constraints($acat, $rootcat)) {
                        // Exclude cats that are not matched by constraints.
                        continue;
                    }

                    $catobj = new StdClass();
                    $catobj->id = $acat->id;
                    $catobj->parent = $rootcat;
                    $catobj->name = format_string($acat->value);
                    $catobj->typesortorder = $acat->typesortorder;

                    $entriesfound = false;
                    foreach ($allcourses as $cid => $c) {
                        if (!empty($allcoursesbytag[$acat->id]) && in_array($c->id, array_keys($allcoursesbytag[$acat->id]))) {
                            $catobj->entries[$cid] = $c;
                            $coursescatchedbysubcat[$cid] = true;
                            $entriesfound = true;
                        }
                    }

                    if ($entriesfound || !empty($catlevels[$levelix]->displayempty)) {
                        $rootcat->cats[$acat->id] = $catobj;
                    }
                }
            }

            // finally cleanup rootcategory entries from course that are actually in subats.
            foreach ($allcourses as $cid => $c) {
                if (in_array($c->id, $coursescatchedbysubcat)) {
                    unset($allcourses[$cid]);
                }
            }
        }

        // Assign to rootcat the remaining courses.
        $rootcat->entries = $allcourses;

        return $rootcat;
    }

    /**
     * get the list of category constructors
     * // TODO complete with a "user profile" strategy
     *
     */
    public static function get_category_levels() {
        global $DB;
        static $levels = null;

        if (is_null($levels)) {
            $config = get_config('local_courseindex');

            if (!$levels = $DB->get_records_select($config->classification_type_table, " type LIKE '%category' ", array(), 'sortorder')) {
                return array();
            }
            $levels = array_values($levels);
        }

        return $levels;
    }

    /**
     * get the lost of category constructors
     * // TODO complete with a "user profile" strategy
     *
     */
    public static function get_category_filters() {
        global $CFG, $DB;

        $config = get_config('local_courseindex');

        if (!$filters = array_values($DB->get_records($config->classification_type_table, array('type' => 'coursefilter'), 'sortorder'))) {
            return array();
        }
        return $filters;
    }

    /**
     * checks for include/exclude constraints. Constraints are not "oriented" arrows.
     * @param object $acat the tested category
     * @param object $cat the reference category
     * @return true if a constraint is found that avoids presenting the child acat in this context
     */
    protected static function navigation_match_constraints($acat, $cat) {
        global $CFG, $DB;

        $config = get_config('local_courseindex');

        // check for exclude
        // cat is discarded if IT IS not mapped or in an exclude list
        $count = $DB->count_records_select($config->classification_constraint_table, " `const` = 1 AND ((value1 = ? AND value2 = ? ) OR (value1 = ? AND value2 = ? )) ", array($acat->id, $cat->id, $cat->id, $acat->id));
        if ($count) {
            return true;
        }

        /*
        // check for include
        // Cat is discarded if it IS NOT IN in include list
        // IT IS IN include list. Get it.
        if ($DB->count_records_select($config->classification_constraint_table, " `const` = 1 AND ((value1 = {$acat->id} AND value2 = {$cat->id}) OR (value1 = {$cat->id} AND value2 = {$acat->id})) ")) 
            return false;
        // THERE ARE OTHER includes for upper cat. Get it NOT.
        if ($DB->count_records_select($config->classification_constraint_table, " `const` = 1 AND ((value1 != {$acat->id} AND value2 = {$cat->id}) OR (value1 = {$cat->id} AND value2 != {$acat->id})) ")) 
            return true;
        // No explicit rules
        */
        return false;
    }

    /**
     *
     *
     */
    public static function navigation_course_is_visible($course) {
        global $DB;

        if (!$course->visible) {
            return false;
        }

        $coursecat = $DB->get_record('course_categories', array('id' => $course->category));
        $cat = $coursecat;
        $catcontext = context_coursecat::instance($cat->id);

        if (!$cat->visible && !has_capability('moodle/category:viewhiddencategories', $catcontext)) {
            return false;
        }

        while ($cat->parent) {
            $catcontext = context_coursecat::instance($cat->id);
            if (!$cat->visible && !has_capability('moodle/category:viewhiddencategories')) {
                return false;
            }
            $cat = $DB->get_record('course_categories', array('id' => $cat->parent));
        };
        return true;
    }

    /**
     *
     */
    public static function classification_has_special_fields(&$specialfields) {
    }

    /**
     * Counts recursively all courses in the categories
     */
    public static function count_entries_rec(&$cattree) {
        $count = 0 + count($cattree->entries);

        if (!empty($cattree->cats)) {
            foreach ($cattree->cats as $cat) {
                $count += self::count_entries_rec($cat);
            }
        }

        return $count;
    }

    /**
     *
     *
     */
    public static function browser_accept_formats() {
        $formats = array('learning',
                         'page',
                         'topics',
                         'weeks');
        return $formats;
    }

}