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

defined('MOODLE_INTERNAL') || die();

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
// constants for display control

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
 * make a full navigation category tree. Recursive. @see tao_generate_navigation_rec_tree()
 * elements are shown in category if they have no other qualifiers setup
 * @param int $startcat if startcat is null, makes full navigation tree from the start. If not null, displays content of a node
 * @param string $catpath contains tree elements reprsnting the upper branch over the start point.
 * @param int $levelix the categorization level the startcat is located at
 * @param array $filters are there filters to apply ? Here they are described.
 * @param $return
 *
 */
function local_courseindex_generate_navigation($startcat = null, $catpath = '', $levelix = 0, &$filters, $restrictions = array()) {
    global $CFG, $DB;

    $config = get_config('local_courseindex');
    $publishconfig = get_config('block_publishflow');

    $levels = local_courseindex_get_category_levels();
    $currentlevel = @$levels[$levelix];

    // Compute some filtering sql from filter selects in the browser.

    $filterclauseelm = array();
    if (!empty($filters)) {
        foreach ($filters as $key => $filter) {
            // apply only classification filters
            if (preg_match("/^f/", $key) && $filter->value) {
                $filterclauseelm[] = " AND cc.{$config->course_metadata_value_key} = $filter->value";
            }
        }
    }
    $filterclause = '';
    if (!empty($filterclauseelm)) {
        $filterclause = implode(' ', $filterclauseelm);
    }

    // Compute some restrictions upon metadata values, that is, some metadata values we do not want to use.

    if (!empty($restrictions)) {
        foreach ($restrictions as $restid => $filter) {
            // apply only classification filters
            $restrictionclauseelm[] = " cc.{$config->course_metadata_value_key} = $filter->value";
        }
    }
    $restrictionclause = '';
    if (!empty($restrictionclauseelm)) {
        $restrictionclause = ' AND ('. implode(' OR ', $restrictionclauseelm). ')';
    }

    // We shall check for some formats to display.

    if (@$publishconfig->moodlenodetype == 'learningarea') {
        // If we use the publishflow, the publishing status may impact what we can see.
        $filterclause = " AND c.category != {$publishconfig->closedcategory} AND c.category != {$publishconfig->freeuseclosedcategory} AND c.category != {$publishconfig->deploycategory} ";
        $formatclause = '';
        if ($formats = local_courseindex_localbrowse_accept_formats()) {
            foreach ($formats as $format) {
                $formatclauseitems[] = " c.format = '$format' ";
            }
            $formatclause = ' AND ('.implode('OR', $formatclauseitems). ') ';
        }
    } else {
        // This is for standard moodle platforms. 
        $formatclause = '';
        if ($formats = local_courseindex_browser_accept_formats()) {
            foreach ($formats as $format) {
                $formatclauseitems[] = " c.format = '$format' ";
            }
            $formatclause = ' AND ('.implode('OR', $formatclauseitems). ') '; 
        }
    }

    if (empty($startcat)) {
        // this is the main root of the cat tree. Start cat is null.

        // Prepare query for getting subs.
        $sql = "
            SELECT DISTINCT
                c.id,
                c.category,
                c.shortname,
                c.fullname,
                c.visible,
                c.timecreated,
                c.summary
            FROM
                {course} c,
                {{$config->course_metadata_table}} cc,
                {{$config->classification_value_table}} ccv
            WHERE
                c.id = cc.{$config->course_metadata_course_key} AND
                cc.{$config->course_metadata_value_key} = ccv.id AND
                ccv.typeid = '.$currentlevel->id.'
                $formatclause
                $filterclause
                $restrictionclause
            GROUP BY
                c.id
        ";

        // Make the parent root node.
        $rootcat = new StdClass();
        $rootcat->id = 0;
        $rootcat->parent = new StdClass();
        $rootcat->parent->id = 0;
        $rootcat->parent->catpath = '';
        $rootcat->name = get_string('root', 'local_courseindex');
    } else {
        // get all possible entries in the required catpath

        // Compute the constraints over the category path subpath
        if (!empty($catpath)) {
            $catpathelms = explode(',', $catpath);
        }
        $catpathelms[] = $startcat;
        $i = 1;
        foreach ($catpathelms as $elm) {
            $tablespecs[] = " {{$config->course_metadata_table}} cc$i, ";
            $constraintspecs[] = "  AND cc$i.{$config->course_metadata_course_key} = c.id AND\n cc$i.{$config->course_metadata_value_key} = {$elm} ";
            $i++;
        }
        $tablespecsclause = implode("\n", $tablespecs);
        $constraintspecsclause = implode("\n", $constraintspecs);

        // Prepare SQL for subnodes.

        $sql = "
            SELECT DISTINCT
                c.id,
                c.category,
                c.shortname,
                c.fullname,
                c.visible,
                c.timecreated,
                c.summary
            FROM
                $tablespecsclause
                {course} c
            WHERE
                1 = 1
                $constraintspecsclause
                $formatclause
                $filterclause
                $restrictionclause
            GROUP BY
                c.id
        ";

        // Make a root node with the start vcat as parent.

        $rootcat = $DB->get_record($config->classification_value_table, array('id' => $startcat));
        $rootcat->name = $rootcat->value;
        // we can compute parent from catpath, as the last id in the path
        $pathparts = explode(',', $catpath);
        $parentpath = array_pop($pathparts);
        $parentcatpath = implode(',', $pathparts);
        $rootcat->parent = new Stdclass;
        $rootcat->parent->id = $parentpath;
        $rootcat->parent->catpath = $parentcatpath;
    }

    // echo $sql;
    if (!$entries = $DB->get_records_sql($sql)) {
        $entries = array();
    }

    // echo "Root : $rootcat->id<br/>";
    // echo "Root parent : {$rootcat->parent->id}<br/>";
    // echo "Subs [";
    foreach (array_keys($entries) as $entryid) {
        // echo "$entryid,";
        if (is_dir($CFG->dirroot.'/blocks/course_status')) {
            // Are we using also course_status block for publishing control ?
            $laststatetime = $DB->get_field('block_course_status_history', 'MAX(timestamp)', array('courseid' => $entryid));
            $laststate = $DB->get_field('block_course_status_history', 'approval_status_id', array('courseid' => $entryid, 'timestamp' => $laststatetime));
            if ($laststate > COURSE_STATUS_PUBLISHED) {
                unset($entries[$entryid]);
            }
        }
    }
    // echo "]<br/>";

    $branchentries = $entries;
    $rootcat->entries = $entries;
    $rootcat->cats = array();
    if (!isset($config->maxnavigationdepth)) {
        set_config('maxnavigationdepth', 3, 'local_courseindex');
    }
    // Get candidate subcategories.
    if ($levelix < count($levels) && $levelix < $config->maxnavigationdepth) {
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
                cv.{$config->classification_value_type_key} = {$levels[$levelix]->id}
            ORDER BY
                cv.sortorder
        ";
        $levelcats = $DB->get_records_sql($sql);
        if ($levelcats) {
            foreach ($levelcats as $acat) {
                if ($rootcat->id && !local_courseindex_navigation_match_constraints($acat, $rootcat)) {
                    continue;
                }
                $catobj = new StdClass();
                $catobj->id = $acat->id;
                $catobj->parent = $rootcat;
                $catobj->name = format_string($acat->value);
                $catobj->typesortorder = $acat->typesortorder;
                // call tree_rec to get entries and subcats
                $levelix++;
                $entriesfound = local_courseindex_generate_navigation_rec_tree($catobj, $branchentries, $levels, $levelix, $filterclause);
                $levelix--;
                if ($entriesfound || !empty($levels[$levelix]->displayempty)) {
                    $rootcat->cats[$acat->id] = $catobj;
                }
            }
        }
    }
    return $rootcat;
}

/**
 * make a full navigation tree.
 * elements are shown in category if they have no other qualifiers setup
 * @param object $cat the current category to be populated
 * @param array $entries the set of entries that are falling down the tree branches
 * @param array $levels an array of categorization descriptors
 * @param int $levelix the current leve we are examinating
 * @param string $filterclause the filtering SQL clause we are applying
 * @param $return
 */
function local_courseindex_generate_navigation_rec_tree(&$cat, &$branchentries, &$levels, $levelix, $filterclause) {
    global $CFG, $USER, $DB;

    $config = get_config('local_courseindex');

    // Recursion Security.
    static $reclevel = 0;
    $reclevel++;
    if ($reclevel > 10) {
        die("Too many recursions");
    }

    $topentryids = implode("','", array_keys($branchentries));

    $entriesfound = 0;
    // initalize arrays
    $cat->entries = array();
    $cat->cats = array();
    $formats = local_courseindex_browser_accept_formats();
    foreach($formats as $format) {
        $formatclauseitems[] = " c.format = '$format' ";
    }
    $formatclause = implode('OR', $formatclauseitems);

    // Get possible entries at this level : one who has the levelcat tag and wich is in topentries.
    $sql = "
        SELECT DISTINCT
            c.id,
            c.category,
            c.shortname,
            c.fullname,
            c.visible,
            c.timecreated,
            c.summary
        FROM
            {course} c,
            {{$config->course_metadata_table}} cc
        WHERE
            cc.{$config->course_metadata_course_key} = c.id AND
            ($formatclause) AND
            cc.{$config->course_metadata_value_key} = '{$cat->id}' AND
            c.id IN ('$topentryids')
            $filterclause
        ORDER BY
            c.sortorder
    ";

    if (!$levelentries = $DB->get_records_sql($sql)) {
        if (empty($levels[$levelix]->displayempty)) {
            // return 0;
        }
        $levelentries = array();
    } else {
        // Discard captured entries from all parents or not having status if course status block is used.
        foreach (array_keys($levelentries) as $entrykey) {

            if (is_dir($CFG->dirroot.'/blocks/course_status')) {
                $laststatetime = $DB->get_field('block_course_status_history', 'MAX(timestamp)', array('courseid' => $entrykey));
                $laststate = $DB->get_field('block_course_status_history', 'approval_status_id', array('courseid' => $entrykey, 'timestamp' => $laststatetime));
                if ($laststate > COURSE_STATUS_PUBLISHED) {
                    unset($catptr->entries[$entrykey]);
                }
            }

            $catptr = $cat->parent;
            while ($catptr) {
                unset($catptr->entries[$entrykey]);
                $catptr = @$catptr->parent;
            }
        }
        //TODO : check visibility and availability against user capabilities
        /*
        // LET DO IT DISPLAY TIME
        foreach(array_keys($levelentries) as $entryid){
            $coursecontext = context_course::instance($entryid);
            if (!isset($rpcoptions->wwwroot)){ // getting course list from local host
                if (!$levelentries[$entryid]->visible && !has_capability('moodle/course:viewhiddencourses', $coursecontext, $USER->id)){
                    unset($levelentries[$entryid]);
                }
            } else {
                if (!$levelentries[$entryid]->visible && !has_capability('moodle/course:viewhiddencourses', $coursecontext, $rpcoptions->localuser->id)){
                    unset($levelentries[$entryid]);
                }
            }
        }
        */
        $cat->entries = $levelentries;
        foreach($levelentries as $cid => $c) {
            $branchentries[$cid] = $c;
        }
    }
    if ($levelix < count($levels) && $levelix < $config->maxnavigationdepth) {

        // Get all subcategories
        $currentlevel = $levels[$levelix];

        $sql = "
            SELECT DISTINCT
               cv.id,
               cv.value,
               ct.sortorder AS typesortorder
            FROM
               {{$config->classification_value_table}} cv,
               {{$config->classification_type_table}} ct,
               {{$config->classification_constraint_table}} cc
            WHERE
                ct.id = cv.{$config->classification_value_type_key} AND
                ct.type LIKE '%category' AND
                ct.sortorder > ? AND
                ((cc.value1 = $cat->id AND cc.value2 = cv.id) OR (cc.value2 = $cat->id AND cc.value1 = cv.id)) AND
                ct.id = ? AND
                cc.const = 1
            ORDER BY
                cv.sortorder
        ";
        // echo $sql.'<br/>';
        if ($levelcats = $DB->get_records_sql($sql, array($cat->typesortorder, $currentlevel->id))) {
            $cat->cats = array();
            foreach($levelcats as $acat) {
                // echo "taking subcat $acat->value ";
                $catobj = new StdClass();
                $catobj->id = $acat->id;
                $catobj->name = $acat->value;
                $catobj->parent = $cat;
                $catobj->typesortorder = $acat->typesortorder;
                // call tree_rec to get entries and subcats
                $levelix++;
                $entriesfound = local_courseindex_generate_navigation_rec_tree($catobj, $branchentries, $levels, $levelix, $filterclause);
                $levelix--;
                if ($entriesfound || !empty($levels[$levelix]->displayempty)) {
                    $cat->cats[$acat->id] = $catobj;
                }
            }
        }
    }
    $reclevel--;
    return (count($levelentries) + $entriesfound);
}


/**
 * get the list of category constructors
 * // TODO complete with a "user profile" strategy
 *
 */
function local_courseindex_get_category_levels() {
    global $DB;
    static $levels = null;

    if (is_null($levels)) {
        $config = get_config('local_courseindex');

        if (!$levels = $DB->get_records_select($config->classification_type_table, " type LIKE '%category' ", array(), 'sortorder')) {
            return array();
        }
        $levels = array_values($levels);
        $levels[0]->displayempty = (isset($config->classification_display_empty_level_0)) ?  $config->classification_display_empty_level_0 : 1;
        $levels[1]->displayempty = (isset($config->classification_display_empty_level_1)) ?  $config->classification_display_empty_level_1 : 1;
        $levels[2]->displayempty = (isset($config->classification_display_empty_level_2)) ?  $config->classification_display_empty_level_2 : 1;
    }

    return $levels;
}

/**
 * get the lost of category constructors
 * // TODO complete with a "user profile" strategy
 *
 */
function local_courseindex_get_category_filters() {
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
function local_courseindex_navigation_match_constraints($acat, $cat) {
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
 * runs down the cat tree and remove empty branches, 
 * filters out incompatible paths
 */
function local_courseindex_reduce_tree(&$cattree, $catlevels, $courselist = null, $catpath = '', $requiredpath = '') {
    static $level = 0;

    $branchentries = 0;
    if (!empty($cattree->entries)) {
        if (!empty($courselist)) {
            foreach ($cattree->entries as $entryid => $entry) {
                if (!in_array($entryid, $courselist)) {
                    unset($cattree->entries[$entryid]);
                    continue;
                }
                // check entry is compatible with path
                if (!empty($catpath)) {
                    if (!empty($requiredpath)) {
                        if (!preg_match("#^{$requiredpath}#", $catpath)) {
                            unset($cattree->entries[$entryid]);
                        }
                    }
                }
            }
        }
        $branchentries = count($cattree->entries);
    }
    if (!empty($cattree->cats)) {
        foreach ($cattree->cats as $id => $subcat) {
            $nextpath = (empty($catpath)) ? $id : $catpath.'/'.$id ;
            $level++;
            if (!$entries = local_courseindex_reduce_tree($subcat, $catlevels, $courselist, $nextpath, $requiredpath)) {
                if (!@$catlevels[$level - 1]->displayempty) {
                    unset($cattree->cats[$id]);
                }
            } else {
                $branchentries += $entries;
            }
            $level--;
        }
    }
    return $branchentries;
}

/**
 *
 *
 */
function local_courseindex_browser_accept_formats() {
    $formats = array('learning',
                     'page',
                     'topics',
                     'weeks');
    return $formats;
}

/**
*
*
*/
function local_courseindex_navigation_course_is_visible($course) {
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

function local_courseindex_classification_has_special_fields(&$specialfields) {
}