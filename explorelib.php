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
 * Library for catalog exploration
 *
 * @package    local_courseindex
 * @author     Valery Fremaux <valery.fremaux@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 1999 onwards Martin Dougiamas  http://dougiamas.com
 */

namespace local_courseindex;

use StdClass;

/**
 * Categories explorer.
 */
class explorer {

    /**
     * performs the search in the courseware
     */
    public static function explore($data) {
        global $CFG, $DB;

        $config = get_config('local_courseindex');

        if (!empty($data->specialsearch)) {
            // Special search using topic and targets.
            $results1 = array();

            if (!empty($data->targets)) {
                $allvalueslist = implode("','", array_values($data->targets));
                $sql = "
                    SELECT DISTINCT
                        c.id,
                        c.shortname,
                        c.fullname,
                        c.visible
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
                        c.visible
                    FROM
                        {course} c,
                        {{$CFG->course_metadata_table}} cc
                    WHERE
                        cc.course = c.id AND
                        cc.{$CFG->course_metadata_value_key} IN ('$topicslist')
                    ORDER BY
                        c.sortorder
                ";
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

        } else if (!empty($data->freesearch)) {

            // Free search implementation.
            if ($tokens = preg_split("/\\s+/", $data->searchtext)) {
                $i = 0; // We need to keep orderfrom the search string.
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
                            c.visible
                        FROM
                            {course} c
                        WHERE
                            1 = 1
                            $searchclauses
                        ORDER BY
                            sortorder
                    ";

                    if (!$results1 = $DB->get_records_sql($sql)) {
                        $results1 = array();
                    }
                    $results2 = array();
                    if (!empty($data->information)) {
                        $sql = "
                            SELECT DISTINCT
                                c.id,
                                c.shortname,
                                c.fullname,
                                c.visible
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

                    // Implements a manual array intersection as array_instersect fails with associative arrays.
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
            // List driven implementation.

            $customlabel = new \StdClass();
            $customlabel->labelclass = 'courseclassifier';
            $customlabel->title = 'void';
            $customlabel->safecontent = '';
            $classifier = customlabel_load_class($customlabel);

            $masterresults = null;

            foreach ($classifier->fields as $field) {
                $classifierkey = $field->name;
                if (!isset($data->$classifierkey)) {
                    continue;
                }

                $input = $data->$classifierkey;
                if (!empty($input)) {
                    if (is_array($input)) {
                        $inputarr = $input;
                    } else {
                        $inputarr = array($input);
                    }

                    list($insql, $inparams) = $DB->get_in_or_equal($inputarr);

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
                            cc.{$config->course_metadata_value_key} $insql
                        ORDER BY
                            c.sortorder
                    ";

                    $results = $DB->get_records_sql($sql, $inparams);

                    // Intersect.
                    if (is_null($masterresults)) {
                        $masterresults = $results;
                    } else {
                        foreach (array_keys($masterresults) as $rid) {
                            if (!array_key_exists($rid, $results)) {
                                // Substract all entries that are NOT in further results. (reduce).
                                unset($masterresults[$rid]);
                            }
                        }
                    }
                }
            }

            if (is_null($masterresults)) {
                return array();
            }
            return $masterresults;
        }
    }

    /**
     * Get linked values to a value
     * @param int $valueid
     */
    public static function get_all_linked_values($valueid) {
        global $CFG, $DB;

        $config = get_config('local_courseindex');

        if (!$value = $DB->get_record($config->classification_value_table, array('id' => $valueid))) {
            return array($valueid);
        }
        $valuetypekey = $config->classification_value_type_key;
        $valuetype = $DB->get_record($config->classification_type_table, array('id' => $value->$valuetypekey));
        $select = " sortorder > ? AND type LIKE '%category' ";
        $subtypes = $DB->get_records_select($config->classification_type_table, $select, array($valuetype->sortorder), 'sortorder');
        $values = array();
        $values[] = $valueid;

        foreach ($subtypes as $subtype) {
            $typevalues = $DB->get_records($config->classification_value_table, array($valuetypekey => $subtype->id));
            $typevaluelist = implode("','", array_keys($typevalues));
            $sourcelist = implode("','", $values); // exploration is cumulative
            // Get impossibilities.
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
                    } else if (!in_array($ac->value2, $values) && in_array($ac->value1, $values)) {
                        $values[] = $ac->value2; // Add remaining non invalidated ids.
                    }
                }
            }
        }
        return $values;
    }

    /**
     *
     *
     */
    public static function has_special_fields(&$fields) {
        global $CFG, $DB;

        $config = get_config('local_courseindex');

        $peoplefieldid = $DB->get_field($config->classification_type_table, 'id', array('code' => 'PEOPLE'));
        $topicfieldid = $DB->get_field($config->classification_type_table, 'id', array('code' => 'TOPIC'));
        $fields = array($peoplefieldid, $topicfieldid);
        return $peoplefieldid || $topicfieldid;
    }

    /**
     * Prepare explore form.
     * @param string $formname
     */
    public static function prepare_form($formname) {
        global $OUTPUT;

        $form = new StdClass();

        if ($formname == 'indexsearch') {
            $customlabel = new \StdClass();
            $customlabel->labelclass = 'courseclassifier';
            $customlabel->title = 'void';
            $customlabel->safecontent = '';
            $classifier = customlabel_load_class($customlabel);

            $template = new \StdClass;
            foreach ($classifier->fields as $field) {

                if ($field->type == '_error_') {
                    continue;
                }

                // All other field types are not relevant for search engine.
                if (!preg_match("/(datasource|_error_)$/", $field->type)) {
                    continue;
                }

                $form->fields[] = $field;

                $classifiertpl = new \Stdclass;

                $classifiertpl->fieldname = str_replace('[]', '', $field->name); // must take care it is a multiple field
                if (!empty($field->isfilter)) {
                    $classifiertpl->fieldlabel = $field->label;
                } else {
                    $classifiertpl->fieldlabel = get_string($field->name, 'customlabeltype_courseclassifier');
                }

                if (!empty($field->mandatory)) {
                    $classifiertpl->mandatory = true;
                    $classifiertpl->pixurl = $OUTPUT->pix_url('req');
                }
                if (!empty($field->help)) {
                    $classifiertpl->fieldhelp = $field->help;
                }

                // Very similar to lists, except options come from an external datasource.
                $multiple = (isset($field->multiple)) ? $field->multiple : '';
                $options = $classifier->get_datasource_options($field);
                $params = array();
                if (!empty($field->constraintson)) {
                    $params['class'] = 'constrained';
                }
                if (empty($multiple)) {
                    $classifiertpl->searchselect = \html_writer::select($options, $field->name, $value, $params);
                } else {
                    $params['multiple'] = 1;
                    $classifiertpl->searchselect = \html_writer::select($options, "{$field->name}[]", @$form->$fieldname, array(), $params);
                }

                $template->classifiers[] = $classifiertpl;
            }

            $form->html = $OUTPUT->render_from_template('local_courseindex/indexsearchform', $template);
        }

        return $form;
    }
}
