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
 */

/**
* performs the search in the courseware
*/
function local_courseindex_explore($data) {
    global $CFG, $DB;

    $config = get_config('local_courseindex');

    if (!empty($data->specialsearch)) {
        // special search using topic and targets
        $results1 = array();

        if (!empty($data->targets)) {
            $allvalueslist = implode("','", array_values($data->targets));
            $sql = "
                SELECT DISTINCT
                    c.id,
                    c.shortname,
                    c.fullname,
                    c.visible,
                    c.password,
                    c.enrollable,
                    c.guest
                FROM
                    {course} c,
                    {{$CFG->course_metadata_table}} cc
                WHERE
                    c.id = cc.course AND
                    cc.{$CFG->course_metadata_value_key} IN ('$allvalueslist')
                ORDER BY
                    c.sortorder
            ";
            $results1 = $DB->get_records_sql($sql);
        }
        $results2 = array();

        if (!empty($data->topics)) {
            $topicslist = implode("','", array_values($data->topics));
            $sql = "
                SELECT DISTINCT
                    c.id,
                    c.shortname,
                    c.fullname,
                    c.visible,
                    c.password,
                    c.enrollable,
                    c.guest
                FROM
                    {course} c,
                    {{$CFG->course_metadata_table}} cc
                WHERE
                    cc.course = c.id AND
                    cc.{$CFG->course_metadata_value_key} IN ('$topicslist')
                ORDER BY
                    c.sortorder
            ";
            debug_trace("Query 2 search in topics " . $sql);
            $results2 = $DB->get_records_sql($sql);
        }

        if (!empty($results1) && !empty($results2)) {
            $res2keys = array_keys($results2);
            $res1keys = array_keys($results1);
            $resultarr = array();
            foreach ($results1 as $key => $result) {
                if (in_array($key, $res2keys) && in_array($key, $res1keys)) {
                    $resultarr[$key] = $result;
                }
            }
            return $resultarr;
        }

        if (!empty($results2) && !$data->targets) {
            return $results2;
        }

        if (!empty($results1) && !$data->topics) {
            return $results1;
        }
        return array();

    } elseif (!empty($data->freesearch)) {

        // free search implementation
        if ($tokens = preg_split("/\\s+/", $data->searchtext)) {
            $i = 0; // we need to keep orderfrom the search string.
            $resultspertoken = array();
            foreach($tokens as $token) {
                $searchclauses = array();
                if (!empty($data->title)) {
                    $searchclauses[] = " fullname LIKE '%$token%' ";
                }
                if (!empty($data->description)) {
                    $searchclauses[] = " summary LIKE '%$token%' ";
                }
                $searchclauses = implode('OR', $searchclauses);
                $searchclauses = (empty($searchclauses)) ? '' : " AND ($searchclauses) ";
                $sql = "
                    SELECT DISTINCT
                        c.id,
                        c.shortname,
                        c.fullname,
                        c.visible,
                        c.password,
                        c.enrollable,
                        c.guest
                    FROM
                        {course} c
                    WHERE
                        1 = 1
                        $searchclauses
                    ORDER BY
                        sortorder
                ";

                if(!$results1 = $DB->get_records_sql($sql)) {
                    $results1 = array();
                }
                $results2 = array();
                if (!empty($data->information)) {
                    $sql = "
                        SELECT DISTINCT
                            c.id,
                            c.shortname,
                            c.fullname,
                            c.visible,
                            c.password,
                            c.enrollable,
                            c.guest
                        FROM
                            {customlabel} cl,
                            {course} c
                        WHERE
                            cl.course = c.id AND
                            cl.labelclass = 'coursedata' AND
                            cl.processedcontent LIKE '%$token%'
                        ORDER BY
                            c.sortorder
                    ";

                    if (!$results2 = $DB->get_records_sql($sql)) {
                        $results2 = array();
                    }
                }

                $resultspertoken[$i] = $results1 + $results2;
                $i++;
            }
            if (!empty($resultspertoken)) {

                // implements a manual array intersecction as array_instersect fails with associative arrays
                $results = $resultspertoken[0];
                $set = array_keys($results);
                for ($i = 1 ; $i < count($resultspertoken) ; $i++) {
                    $resultkeys = array_keys($resultspertoken[$i]);
                    $tmpresults = array();
                    foreach ($set as $elm) {
                        if (in_array($elm, $resultkeys)) {
                            $tmpresults[] = $elm;
                        }
                    }
                    $set = $tmpresults;
                }
                if (!empty($set)) {
                    $tmpresults = array();
                    foreach ($set as $elm) {
                        $tmpresults[$elm] = $results[$elm];
                    }
                    $results = $tmpresults;
                } else {
                    $results = array();
                }
            }
        } else {
            $results = array();
        }

        return $results;

    } else {
        // list driven implementation
        $results1 = array();
        if ($data->level0) {
            $catarr = $data->level0;
            $lastcatid = count($catarr) - 1;
            $allvalues = local_courseindex_get_all_linked_values($catarr[$lastcatid]);
            $level0list = implode("','", $allvalues);
            $sql = "
                SELECT DISTINCT
                    c.id,
                    c.shortname,
                    c.fullname,
                    c.visible
                FROM
                    {course} c,
                    {{$config->course_metadata_table}} cc
                WHERE
                    c.id = cc.courseid AND
                    cc.{$config->course_metadata_value_key} IN ('$level0list')
                ORDER BY
                    c.sortorder
            ";

            $results1 = $DB->get_records_sql($sql);
        }
        $results2 = array();
        if (!empty($data->level1) && !(count($data->level1) == 1 && $data->level1[0] == 0)) {
            $level1list = implode("','", $data->level1);
            $sql = "
                SELECT DISTINCT
                    c.id,
                    c.shortname,
                    c.fullname,
                    c.visible
                FROM
                    {course} c,
                    {{$config->course_metadata_table}} cc
                WHERE
                    cc.courseid = c.id AND
                    cc.{$config->course_metadata_value_key} IN ('$level1list')
                ORDER BY
                    c.sortorder
            ";

            $results2 = $DB->get_records_sql($sql);
        }

        // debug_trace(debug_capture($results1));
        // debug_trace(debug_capture($results2));
        // manual intersect of results1 in results 2
        if (!empty($results1) && !empty($results2)) {
            $res2keys = array_keys($results2);
            $res1keys = array_keys($results1);
            $resultarr = array();
            foreach ($results1 as $key => $result) {
                if (in_array($key, $res2keys) && in_array($key, $res1keys)) {
                    $resultarr[$key] = $result;
                }
            }
            return $resultarr;
        }
        if (!empty($results2) && empty($data->level0)) {
            return $results2;
        }
        if (!empty($results1) && (empty($data->level1) || (count($data->level1) == 1 && $data->level1[0] == 0))) {
            return $results1;
        }
        return array();
    }
}

/**
 *
 *
 */
function local_courseindex_get_all_linked_values($valueid) {
    global $CFG, $DB;

    $config = get_config('local_courseindex');

    if (!$value = $DB->get_record($config->classification_value_table, array('id' => $valueid))) {
        return array($valueid);
    }
    $valuetypekey = $config->classification_value_type_key;
    $valuetype = $DB->get_record($config->classification_type_table, array('id' => $value->$valuetypekey));
    $subtypes = $DB->get_records_select($config->classification_type_table, " sortorder > ? AND type LIKE '%category' ", array($valuetype->sortorder), 'sortorder');
    $values = array();
    $values[] = $valueid;
    foreach($subtypes as $subtype) {
        $typevalues = $DB->get_records($config->classification_value_table, array($valuetypekey => $subtype->id));
        $typevaluelist = implode("','", array_keys($typevalues));
        $sourcelist = implode("','", $values); // exploration is cumulative
        // get impossibilities
        $sql = "
            SELECT
                cc.id,
                value1,
                value2
            FROM
                {{$config->classification_constraint_table}} cc,
                {{$config->classification_value_table}} cv1,
                {{$config->classification_value_table}} cv2
            WHERE
                cc.value1 = cv1.id AND
                cc.value2 = cv2.id AND
                ((cv1.id IN('$sourcelist') AND cv2.id IN ('$typevaluelist')) OR (cv2.id IN('$sourcelist') AND cv1.id IN ('$typevaluelist'))) AND
                cc.const = 1
        ";

        if ($acceptedconstraints = $DB->get_records_sql($sql)) {
            foreach ($acceptedconstraints as $ac) {
                if (!in_array($ac->value1, $values) && in_array($ac->value2, $values)) {
                    $values[] = $ac->value1; // add remaining non invalidated ids
                } elseif (!in_array($ac->value2, $values) && in_array($ac->value1, $values)) {
                    $values[] = $ac->value2; // add remaining non invalidated ids
                }
            }
        }
    }
    return $values;
}

/**
 * gets the complete disciplinlist from classification
 * DEPRECATED
 */
function ____coursecatalog_get_classification($filter, $treemap=false) {
    global $CFG, $DB;

    $config = get_config('local_courseindex');

    static $classificationbackmap; // caches calculated once classification tree
    if ($treemap && isset($classificationbackmap)) return $classificationbackmap;
    if (!$degreeclassifierid = $DB->get_field_select($config->classification_type_table, 'id', " name LIKE 'Degr%' ", array())) {
        error ("No degre classifier ID");
    }
    if (!$levelclassifierid = $DB->get_field_select($config->classification_type_table, 'id', " name LIKE 'Niveau%' ",  array())) {
        error ("No level classifier ID");
    }
    if (!$cycleclassifierid = $DB->get_field_select($config->classification_type_table, 'id', " name LIKE 'Cycle%' ", array())) {
        error ("No cycle classifier ID");
    }
    if (!$disciplinclassifierid = $DB->get_field_select($config->classification_type_table, 'id', " name LIKE 'Discipl%' ", array())) {
        error ("No disciplin classifier ID");
    }
    $included = array();
    $degreeitems = array();
    if (!empty($filter)) {
        $clvalue = $DB->get_record($config->classification_value_table, array('id' => $filter));
        if($clvalue->type == $degreeclassifierid) {
            $degreeitems[] = $clvalue->id;
        }
    } else {
        // if nothing given, we get all degrees
        if ($degrees = $DB->get_records($config->classification_value_table, array('type' => $degreeclassifierid))) {
            $degreeitems = array_keys($degrees);
        }
    }
    // collect all subdegrees if any
    foreach ($degreeitems as $degreeitem) {
        // fetch all non invalidated levels 
        $sql = "
            SELECT 
                cc.*
            FROM
                {{$config->classification_value_table}} cv
            LEFT JOIN
                {{$config->classification_constraint_table}} cc
            ON
                (cv.id = cc.value1 OR cv.id = cc.value2)
            WHERE
                (cv.id = $degreeitem) AND
                cc.const = 1
            ORDER BY
                cv.sortorder
        ";
        if ($constraintpeerrecs = $DB->get_records_sql($sql)) {
            foreach ($constraintpeerrecs as $apeer) {
                if ($apeer->value1 == $degreeitem) {
                    $peervalue = $apeer->value2;
                    $sourcevalue = $apeer->value1;
                } else {
                    $peervalue = $apeer->value1;
                    $sourcevalue = $apeer->value2;
                }
                $apeer->type = $DB->get_field($config->classification_value_table, 'type', array('id' => $peervalue));
                if ($apeer->type == $levelclassifierid) {
                    $levelitems[] = $peervalue;
                    $classificationbackmap["$peervalue"] = $sourcevalue;
                }
            }
        }
    }
    if(isset($clvalue) && $clvalue->type == $levelclassifierid) {
        $levelitems[] = $clvalue->id;
    }
    if (!empty($levelitems)) {
        // seek in levels
        foreach ($levelitems as $levelitem) {
            $sql = "
                SELECT 
                    cc.*
                FROM
                    {{$config->classification_value_table}} cv
                LEFT JOIN
                    {{$config->classification_constraint_table}} cc
                ON
                    (cv.id = cc.value1 OR cv.id = cc.value2)
                WHERE
                    (cv.id = $levelitem) AND const = 1
                ORDER BY
                    cv.sortorder
            ";
            if ($constraintpeerrecs = $DB->get_records_sql($sql)) {
                foreach ($constraintpeerrecs as $apeer) {
                    if ($apeer->value1 == $levelitem) {
                        $peervalue = $apeer->value2;
                        $sourcevalue = $apeer->value1;
                    } else {
                        $peervalue = $apeer->value1;
                        $sourcevalue = $apeer->value2;
                    }
                    $apeer->type = $DB->get_field($config->classification_value_table, $config->classification_value_type_key, array('id' => $peervalue));
                    if ($apeer->type == $cycleclassifierid) {
                        // echo "indirect $peervalue ";
                        // we are indirectly connected to disciplins, so defer the check to cycle checks
                        $cycleitems[] = $peervalue;
                    } else if($apeer->type == $disciplinclassifierid) {
                        // we are directly connected to disciplins, so mark them now
                        $included[$peervalue] = 1;
                        $classificationbackmap["$peervalue"] = $sourcevalue;
                    }
                }
            }
        }
    }
    $typekey = $config->classification_value_type_key;
    if (isset($clvalue) && $clvalue->$typekey == $cycleclassifierid) {
        $cycleitems[] = $clvalue->id;
    }
    if (!empty($cycleitems)) {
        // seek in disciplins
        foreach ($cycleitems as $cycleitem) {
            $sql = "
                SELECT 
                    cc.*,
                    cv.id ".$DB->sql_as()." cvid
                FROM
                    {{$config->classification_value_table} cv
                LEFT JOIN
                    {{$config->classification_constraint_table}} cc
                ON
                    (cv.id = cc.value1 OR cv.id = cc.value2)
                WHERE
                    (cv.id = $cycleitem) AND const = 1
                ORDER BY
                    cv.sortorder
            ";
            if ($constraintpeerrecs = $DB->get_records_sql($sql)) {
                foreach ($constraintpeerrecs as $apeer) {
                    if ($apeer->value1 == $cycleitem) {
                        $peervalue = $apeer->value2;
                        $sourcevalue = $apeer->value1;
                    } else {
                        $peervalue = $apeer->value1;
                        $sourcevalue = $apeer->value2;
                    }
                    // we are directly connected to disciplins, so mark them now
                    $included[$peervalue] = 1;
                    $classificationbackmap["$peervalue"] = $sourcevalue;
                }
            }
        }
    }
    if ($treemap) {
        return $classificationbackmap;
    }
    return $included;
}

/**
*
*
*/
function coursecatalog_has_special_fields(&$fields) {
    global $CFG, $DB;

    $peoplefieldid = $DB->get_field($config->classification_type_table, 'id', array('code' => 'PEOPLE'));
    $topicfieldid = $DB->get_field($config->classification_type_table, 'id', array('code' => 'TOPIC'));
    $fields = array($peoplefieldid, $topicfieldid);
    return $peoplefieldid || $topicfieldid;
}
