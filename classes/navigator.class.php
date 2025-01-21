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
 * Resolves all navigation calculation
 *
 * @package    local_courseindex
 * @author     Valery Fremaux <valery.fremaux@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 1999 onwards Martin Dougiamas  http://dougiamas.com
 */
namespace local_courseindex;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/local/courseindex/compatlib.php');

use StdClass;
use context_coursecat;
use moodle_url;

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

/**
 * The course insdex navigation manager.
 */
class navigator {

    /**
     * make a full navigation category tree. Recursive.
     * elements are shown in category if they have no other qualifiers setup
     * @param int $startcat if startcat is null, makes full navigation tree from the start. If not null, displays content of a node
     * @param string $catpath contains tree elements representing the upper branch over the start point.
     * @param int $catlevels the categorization level the startcat is located at
     * @param array $filters are there filters to apply ? Here they are described.
     * @param $return
     */
    public static function generate_navigation($startcat = null, $catpath = '', $catlevels = 1, $filters = [], $restrictions = []) {
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

        $deletioninprogressclause = courseindex_get_deletioninprogress_sql();

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
                {{$config->classification_value_table}} ccv,
                {{$config->course_metadata_table}} cc
            LEFT JOIN
                {course_modules} cm
            ON
                cc.{$config->course_metadata_cmid_key} = cm.id
            WHERE
                c.id = cc.{$config->course_metadata_course_key} AND
                cc.{$config->course_metadata_value_key} = ccv.id
                {$deletioninprogressclause}
                {$formatclause}
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
                            if ($debug) {
                                echo "Catching course $cid in cat $acat->id<br/>";
                            }
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

            // Finally cleanup rootcategory entries from course that are actually in subcats.
            foreach ($allcourses as $cid => $c) {
                if (array_key_exists($c->id, $coursescatchedbysubcat)) {
                    if ($debug) {
                        echo "Discard $cid as catched by subcat ";
                    }
                    unset($allcourses[$cid]);
                }
            }
        }

        // Assign to rootcat the remaining courses.
        $rootcat->entries = $allcourses;

        return $rootcat;
    }

    /**
     * make a full navigation category tree without courses. Recursive.
     * elements are shown in category if they have no other qualifiers setup
     * @param int $startcat if startcat is null, makes full navigation tree from the start. If not null, displays content of a node
     * @param string $catpath contains tree elements representing the upper branch over the start point.
     * @param int $levelix the categorization level the startcat is located at
     * @param $return
     */
    public static function generate_category_tree($startcat = null, $catpath = '', $catlevels = [], $filterstring = '') {
        global $CFG, $DB;

        $config = get_config('local_courseindex');
        $current = optional_param('catpath', '', PARAM_TEXT);

        if (empty($catpath)) {
            $levelix = 0;
            $catpatharr = array();
        } else {
            $catpatharr = explode(',', $catpath);
            $levelix = count($catpatharr) - 1;
        }

        if (empty($catlevels)) {
            // The course index seems being empty or having no levels.
            $rootcat = new StdClass();
            $rootcat->id = 0;
            $rootcat->parentid = '';
            $rootcat->parentpath = '';
            $rootcat->name = get_string('root', 'local_courseindex');
            $rootcat->cats = array();
            $rootcat->hascats = 0;
            $rootcat->catidpath = 0;
            $rootcat->display = '';
            $params = array('catid' => 0, 'catpath' => '');
            $rootcat->caturl = new moodle_url('/local/courseindex/browser.php', $params).$filterstring;
            return $rootcat;
        } else if (empty($startcat)) {
            // This is the top root cat.
            $rootcat = new StdClass();
            $rootcat->id = 0;
            $rootcat->parentid = '';
            $rootcat->parentpath = '';
            $rootcat->name = get_string('root', 'local_courseindex');
            $rootcat->cats = array();
            $rootcat->display = '';
            $rootcat->isroot = 1;
            $startcatid = 0;
        } else {
            if (is_object($startcat)) {
                $startcatid = $startcat->id;
            } else {
                $startcatid = $startcat;
            }
            $rootcat = $DB->get_record($config->classification_value_table, array('id' => $startcatid));
            unset($rootcat->parent); // Do not confuse.
            $rootcat->name = $rootcat->value;
            if ($levelix) {
                $rootcat->display = 'display:none';
            } else {
                $rootcat->display = '';
            }

            /*
             * We can compute parent from catpath, as the last id in the path.
             * Path has the full path until the actual cat.
             */
            $pathparts = $catpatharr; // Clone the array.
            array_pop($pathparts); // Remove ourself.
            $parentcat = array_pop($pathparts); // Get parent.
            $parentcatpath = implode(',', $pathparts); //
            $rootcat->parentid = 0 + (int)$parentcat;
            $rootcat->parentpath = $parentcatpath;
            $rootcat->isroot = 0;
        }

        $rootcat->cats = array();
        $rootcat->hascats = 0;
        $rootcat->catidpath = str_replace(',', '-', $catpath);
        $rootcat->level = $levelix;
        $params = array('catid' => $startcatid, 'catpath' => $catpath);
        $rootcat->caturl = new moodle_url('/local/courseindex/browser.php', $params).$filterstring;
        $rootcat->currentclass = '';
        if ($current == $catpath) {
            $rootcat->currentclass = 'is-current';
        }

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

                    // Recurse to get all tree.
                    $catobj = self::generate_category_tree($acat, $catpath.','.$acat->id, $catlevels, $filterstring);
                    $catobj->id = $acat->id;
                    $catobj->parentid = $startcatid;
                    $catobj->parentpath = $catpath;
                    $catobj->name = format_string($acat->value);
                    $catobj->typesortorder = $acat->typesortorder;
                    $rootcat->cats[] = $catobj;
                    $rootcat->hascats = 1;
                }
            }
        }

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
     * Entries of a cat are entries attached to exactly all cats in the cat path.
     * @param int $catid
     * @param string $catpath
     * @param array $filters
     */
    public static function get_cat_entries($catid, $catpath, $filters) {
        global $DB;

        $config = get_config('local_courseindex');

        if (empty($config->course_metadata_cmid_key)) {
            set_config('course_metadata_cmid_key', 'cmid', 'local_courseindex');
            $config->course_metadata_cmid_key = 'cmid';
        }

        // Get courses entries in the category.
        $catids = explode(',', $catpath);
        array_shift($catids); // Drop the root.

        $catcourses = [];
        $deletioninprogressclause = courseindex_get_deletioninprogress_sql();
        $start = true;

        foreach ($catids as $catid) {

            // Get all course info.
            $sql = "
                SELECT DISTINCT
                    c.id,
                    c.format,
                    c.category,
                    c.shortname,
                    c.fullname,
                    c.visible,
                    c.timecreated,
                    c.summary
                FROM
                    {course} c,
                    {{$config->classification_value_table}} ccv,
                    {{$config->course_metadata_table}} cc
                LEFT JOIN
                    {course_modules} cm
                ON
                    cc.{$config->course_metadata_cmid_key} = cm.id
                WHERE
                    c.id = cc.{$config->course_metadata_course_key} AND
                    cc.{$config->course_metadata_value_key} = ccv.id AND
                    cc.valueid = ?
                    {$deletioninprogressclause}
                GROUP BY
                    c.id
                ORDER BY
                    c.sortorder
            ";

            $taggedcourses = $DB->get_records_sql($sql, [$catid]);

            if (empty($catcourses) && ($start == true)) {
                // Load with first tag.
                $catcourses = $taggedcourses;
            } else {
                // Calculate an INTERSECT.
                foreach (array_keys($catcourses) as $cid) {
                    if (!array_key_exists($cid, $taggedcourses)) {
                        unset($catcourses[$cid]);
                    }
                }
            }
            $start = false;
        }

        // Finally apply filters.
        foreach (array_keys($catcourses) as $cid) {
            foreach($filters as $filter) {
                if (empty($filter->value)) {
                    continue;
                }

                if ((count(array_keys($filter->value)) == 1) && ($filter->value[0] == 0)) {
                    // Empty exprimed filter as single element array with 0 in it.
                    continue;
                }

                $noneofthem = true;
                foreach ($filter->value as $singlevalue) {
                    $params = ['courseid' => $cid, 'valueid' => $singlevalue];
                    if ($DB->record_exists($config->course_metadata_table, $params)) {
                        $noneofthem = false;
                    }
                }

                if ($noneofthem) {
                    unset($catcourses[$cid]);
                }
            }
        }

        return $catcourses;
    }

    /**
     * Get all courses after filtering
     * @param array $filters
     * @param int $page
     * @param int $pagesize
     * @param array $totalcourses
     */
    public static function get_all_filtered_courses($filters, $page = 0, $pagesize = 30, & $totalcourses =  null) {
        global $DB;

        $config = get_config('local_courseindex');

        $allvalues = courseindex_get_all_filter_values($filters);

        list($insql, $inparams) = $DB->get_in_or_equal($allvalues);

        $deletioninprogressclause = courseindex_get_deletioninprogress_sql();

        $sql = "
            SELECT DISTINCT
                c.id,
                c.format,
                c.category,
                c.shortname,
                c.fullname,
                c.visible,
                c.timecreated,
                c.summary
            FROM
                {course} c,
                {{$config->classification_value_table}} ccv,
                {{$config->course_metadata_table}} cc
            LEFT JOIN
                {course_modules} cm
            ON
                cc.{$config->course_metadata_cmid_key} = cm.id
            WHERE
                c.id = cc.{$config->course_metadata_course_key} AND
                cc.{$config->course_metadata_value_key} = ccv.id AND
                cc.valueid $insql
                {$deletioninprogressclause}
            GROUP BY
                c.id
            ORDER BY
                c.sortorder
        ";

        $countsql = "
            SELECT
                DISTINCT COUNT(*)
            FROM
                {course} c,
                {{$config->classification_value_table}} ccv,
                {{$config->course_metadata_table}} cc
            LEFT JOIN
                {course_modules} cm
            ON
                cc.{$config->course_metadata_cmid_key} = cm.id
            WHERE
                c.id = cc.{$config->course_metadata_course_key} AND
                cc.{$config->course_metadata_value_key} = ccv.id AND
                cc.valueid $insql
                {$deletioninprogressclause}
        ";

        $totalcourses = $DB->count_records_sql($countsql, $inparams);
        $offset = $page * $pagesize;
        $filteredcourses = $DB->get_records_sql($sql, $inparams, $offset, $pagesize);

        return $filteredcourses;
    }

    /**
     * get the lost of category constructors
     * // TODO complete with a "user profile" strategy
     */
    public static function get_category_filters() {
        global $CFG, $DB;

        $config = get_config('local_courseindex');

        $params = ['type' => 'coursefilter'];
        if (!$filters = array_values($DB->get_records($config->classification_type_table, $params, 'sortorder'))) {
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

        // Check for exclude.
        // Cat is discarded if IT IS not mapped or in an exclude list.
        $select = " `const` = 1 AND ((value1 = ? AND value2 = ? ) OR (value1 = ? AND value2 = ? )) ";
        $params = [$acat->id, $cat->id, $cat->id, $acat->id];
        $count = $DB->count_records_select($config->classification_constraint_table, $select, $params);
        if ($count) {
            return true;
        }

        return false;
    }

    /**
     * Is course visible ?
     * @param object $course
     */
    public static function course_is_visible($course) {
        global $DB;

        if (!$course->visible) {
            return false;
        }

        $coursecat = $DB->get_record('course_categories', ['id' => $course->category]);
        $cat = $coursecat;
        $catcontext = context_coursecat::instance($cat->id);

        if (!$cat->visible && !has_capability('moodle/category:viewhiddencategories', $catcontext)) {
            return false;
        }

        while ($cat->parent) {
            $catcontext = context_coursecat::instance($cat->id);
            if (!$cat->visible && !has_capability('moodle/category:viewhiddencategories', $catcontext)) {
                return false;
            }
            $cat = $DB->get_record('course_categories', ['id' => $cat->parent]);
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

    public static function get_filters_option_values($classificationfilters) {
        global $DB;

        $filters = [];
        $config = get_config('local_courseindex');

        $i = 0;
        foreach ($classificationfilters as $afilter) {

            $sql = "
                SELECT
                    cv.id,
                    cv.value as value,
                    COUNT(c.id) as counter
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
                    /* c.visible = 1 */
                    1 = 1
                GROUP BY
                    cv.id
                ORDER BY
                    cv.sortorder
            ";

            $params = array($afilter->id);

            $options = $DB->get_records_sql($sql, $params);

            $filters["f$i"] = new StdClass;
            $filters["f$i"]->name = $afilter->name;
            $filters["f$i"]->options = $options;
            if (is_array(@$_REQUEST["f$i"])) {
                $filters["f$i"]->value = optional_param_array("f$i", '', PARAM_INT);
            } else {
                $filters["f$i"]->value = optional_param("f$i", '', PARAM_INT);
            }
            $i++;
        }

        return $filters;
    }

}