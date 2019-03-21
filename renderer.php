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

defined('MOODLE_INTERNAL') or die();
require_once($CFG->dirroot.'/local/courseindex/lib.php');

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


class local_courseindex_renderer extends plugin_renderer_base {

    /**
     * Navigator category rendering.
     * @param objectref &$cat
     * @param string &$catpath
     * @param int $coursecount
     * @param string $current
     * @param boolean $up
     * @param array $filters
     */
    public function category(&$cat, &$catpath, $coursecount, $current = 'current', $up = false, $filters = array()) {

        $template = new StdClass;
        $template->current = $current;

        $nextpath = (empty($catpath)) ? $cat->id : $catpath.','.$cat->id;

        if (strpos($catpath, ',') === false) {
            $prevpath =  '';
        } else {
            $prevpath = preg_replace('/,'.$cat->id.'$/', '', $catpath);
        }

        $template->hasup = ($up && !is_null($cat->parent));
        if ($template->hasup) {
            $params = array('catid' => $cat->parent->id, 'catpath' => $prevpath);
            if ($filters) {
                foreach ($filters as $key => $afilter) {
                    $params[$key] = $afilter->value;
                }
            }
            $template->upcaturl = new moodle_url('/local/courseindex/browser.php', $params);
            $template->uppixurl = $this->image_url('up', 'local_courseindex');
            $template->catspan = 9;
        } else {
            $template->catspan = 11;
        }

        $template->catname = format_string($cat->name);
        $template->catid = $cat->id;

        $template->issub = ($current == 'sub');
        if ($template->issub) {
            $params = array('catid' => $cat->id, 'catpath' => $nextpath);
            if (!empty($filters)) {
                foreach ($filters as $key => $afilter) {
                    $params[$key] = $afilter->value;
                }
            }
            $template->caturl = new moodle_url('/local/courseindex/browser.php', $params);
        }

        $template->coursecount = 0 + $coursecount;

        return $this->output->render_from_template('local_courseindex/category', $template);
    }

    /**
     *
     */
    public function courses_slider($courseids) {
        global $CFG, $PAGE;

        $template = new StdClass;

        $template->totalfcourse = count($courseids);

        if (!empty($courseids)) {

            $template->heading = $this->output->heading(get_string('courses'));
            $template->courses = array();

            foreach ($courseids as $courseid) {

                $coursetpl = new StdClass;
                $course = get_course($courseid);

                $summary = local_courseindex_strip_html_tags($course->summary, $course->summaryformat);
                $coursetpl->summary = local_courseindex_course_trim_char($summary, 200);
                $coursetpl->trimtitle = local_courseindex_course_trim_char($course->fullname, 45);

                $coursetpl->courseurl = new moodle_url('/course/view.php', array('id' => $courseid ));

                $coursetpl->imgurl = $this->get_course_image_url($course);

                $template->courses[] = $coursetpl;
            }
        }

        $template->hascourses = count($template->courses);
        $template->nocourses = $this->output->notification(get_string('nocourses'));

        return $this->output->render_from_template('local_courseindex/courseslider', $template);
    }

    /**
     * Print all current children of the current category.
     * @param object $cat
     * @param string $catpath
     */
    public function children(&$cat, $catpath, &$filters) {

        $str = '';

        if (!empty($cat->cats)) {

            $str .= $this->output->heading(get_string('subcategories'));

            foreach ($cat->cats as $child) {
                $str .= $this->child($child, $catpath, $filters);
            }
        }

        return $str;
    }

    protected function child(&$cat, $catpath, &$filters) {
        return $this->category($cat, $catpath, \local_courseindex\navigator::count_entries_rec($cat), 'sub', false, $filters);
    }

    public function explorerlink() {

        $template = new Stdclass;
        $template->searchstr = get_string('searchintree', 'local_courseindex');
        $template->exploreurl = new moodle_url('/local/courseindex/explorer.php');

        return $this->output->render_from_template('local_courseindex/explorerlink', $template);
    }

    public function browserlink() {

        $template = new StdClass;

        $template->browserstr = get_string('browsealltree', 'local_courseindex');
        $template->browserurl = new moodle_url('/local/courseindex/browser.php');

        return $this->output->render_from_template('local_courseindex/browserlink', $template);
    }

    /**
     * Renders course filters as select row.
     * @param int $catid the currently starting category
     * @param int $catpath the comma separated parent path from root till current catid
     * @param int $filters the set of filters.
     */
    public function filters($catid, $catpath, &$filters) {

        $template = new StdClass;

        $template->browserurl = new moodle_url('/local/courseindex/browser.php', array('catid' => $catid));
        $template->filters = array();

        $template->catid = $catid;
        $template->catpath = $catpath;
        $template->strreload = get_string('reload', 'local_courseindex');

        if (!empty($filters)) {
            foreach ($filters as $key => $afilter) {
                $ftpl = new Stdclass;
                $ftpl->isnotwwwroot = ($key != 'wwwroot');
                $ftpl->fname = $afilter->name;
                $ftpl->fselect = html_writer::select($afilter->options, $key, $afilter->value);
                $template->filters[] = $ftpl;
            }
        }

        $template->hasfilters = count($template->filters);

        return $this->output->render_from_template('local_courseindex/filters', $template);
    }

    public function search_results($results) {
        global $DB;

        $config = get_config('local_courseindex');

        foreach ($results as $result) {
            $coursetpl = $result;

            $sql = "
                SELECT
                   cct.code,
                   cct.name,
                   GROUP_CONCAT(ccv.value, ', ') as value
                FROM
                    {{$config->course_metadata_table}} cc,
                    {{$config->classification_value_table}} ccv,
                    {{$config->classification_type_table}} cct
                WHERE
                    cct.id = ccv.typeid AND
                    ccv.id = cc.valueid AND
                    cc.courseid = ?
                GROUP BY
                    cct.code
                ORDER BY
                    cct.sortorder, ccv.sortorder
            ";

            if ($mtds = $DB->get_records_sql($sql, array($result->id))) {
                foreach ($mtds as $mtd) {
                    $tagtpl = new Stdclass;
                    $tagtpl->name = $mtd->name;
                    $tagtpl->value = $mtd->value;
                    $coursetpl->tags[] = $tagtpl;
                }
            }

            $coursetpl->description = format_text($DB->get_field('course', 'summary', array('id' => $result->id)));
            $coursetpl->imgurl = $this->get_course_image_url($result);
            $coursetpl->courseurl = new moodle_url('/course/view.php', array('id' => $result->id));

            $template->courses[] = $coursetpl;
        }

        return $this->output->render_from_template('local_courseindex/searchresults', $template);
    }

    /**
     * Navigator category rendering.
     * @param objectref &$cat
     * @param string &$catpath
     * @param int $coursecount
     * @param string $current
     * @param boolean $up
     * @param array $filters
     *
    public function category_tree(&$cat, &$catpath, $coursecount, $filters = array(), $astemplate = false) {
        static $level = -1;

        $level++;
        $template = new StdClass;

        $template->catname = format_string($cat->name);
        $template->catid = $cat->id;

        $template->issub = ($current == 'sub');
        if ($template->issub) {
            $params = array('catid' => $cat->id, 'catpath' => $nextpath);
            if (!empty($filters)) {
                foreach ($filters as $key => $afilter) {
                    $params[$key] = $afilter->value;
                }
            }
            $template->caturl = new moodle_url('/local/courseindex/browser.php', $params);
        }

        if (!empty($cat->entries)) {
            
        }

        if (!empty($cat->cats)) {
            foreach ($cat->cats as $child) {
                $template->cats[] = $this->category_tree($child, $catpath, $filters, true);
            }
        }

        if ($astemplate) {
            $level--;
            return $template;
        }
        $level--;
        return $this->output->render_from_template('local_courseindex/category', $template);
    }
    */

    protected function get_course_image_url($course) {
        global $CFG;

        if ($course instanceof stdClass) {
            $course = new \core_course_list_element($course);
        }

        $imgurl = false; // Initiate search.
        $context = context_course::instance($course->id);

        foreach ($course->get_course_overviewfiles() as $file) {
            if ($isimage = $file->is_valid_image()) {
                $path = '/'. $file->get_contextid(). '/'. $file->get_component().'/';
                $path .= $file->get_filearea().$file->get_filepath().$file->get_filename();
                $imgurl = file_encode_url("$CFG->wwwroot/pluginfile.php", $path, !$isimage);
                break;
            }
        }
        if (!$imgurl) {
            $imgurl = $this->get_image_url('coursedefaultimage');
        }

        return $imgurl;
    }

    /**
     * Get best suits image url for representing the course.
     */
    protected function get_image_url($imgname) {
        global $PAGE;

        $fs = get_file_storage();

        $context = context_system::instance();

        $haslocalfile = false;
        $frec = new StdClass;
        $frec->contextid = $context->id;
        $frec->component = 'local_courseindex';
        $frec->filearea = 'rendererimages';
        $frec->filename = $imgname.'.svg';
        if (!$fs->file_exists($frec->contextid, $frec->component, $frec->filearea, 0, '/', $frec->filename)) {
            $frec->filename = $imgname.'.png';
            if (!$fs->file_exists($frec->contextid, $frec->component, $frec->filearea, 0, '/', $frec->filename)) {
                $frec->filename = $imgname.'.jpg';
                if (!$fs->file_exists($frec->contextid, $frec->component, $frec->filearea, 0, '/', $frec->filename)) {
                    $frec->filename = $imgname.'.gif';
                    if ($fs->file_exists($frec->contextid, $frec->component, $frec->filearea, 0, '/', $frec->filename)) {
                        $haslocalfile = true;
                    }
                } else {
                    $haslocalfile = true;
                }
            } else {
                $haslocalfile = true;
            }
        } else {
            $haslocalfile = true;
        }

        if ($haslocalfile) {
            $fileurl = moodle_url::make_pluginfile_url($frec->contextid, $frec->component, $frec->filearea, 0, '/',
                                                    $frec->filename, false);
            return $fileurl;
        }

        if ($PAGE->theme->resolve_image_location($imgname, 'theme', true)) {
            $imgurl = $this->output->image_url($imgname, 'theme');
        } else {
            return $this->output->image_url($imgname, 'local_courseindex');
        }

        return $imgurl;
    }
}