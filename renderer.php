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
 * Main plugin renderer
 *
 * @package    local_courseindex
 * @author     Valery Fremaux <valery.fremaux@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 1999 onwards Martin Dougiamas  http://dougiamas.com
 */

defined('MOODLE_INTERNAL') or die();

require_once($CFG->dirroot.'/local/courseindex/lib.php');
require_once($CFG->dirroot.'/local/courseindex/compatlib.php');

/**
 * Main renderer class.
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
            $template->uppixurl = courseindex_image_url('up');
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
    public function filters($catid, $catpath, &$filters, $layout = 'standard') {

        $template = new StdClass;

        $template->filters = array();

        $template->catid = $catid;
        $template->catpath = $catpath;
        $template->strreload = get_string('reload', 'local_courseindex');

        if (!empty($filters)) {
            foreach ($filters as $key => $afilter) {
                $ftpl = new Stdclass;
                $ftpl->isnotwwwroot = ($key != 'wwwroot');
                $ftpl->fname = $afilter->name;

                if (is_array(@$_REQUEST[$key])) {
                    $filtervalue = optional_param_array($key, '', PARAM_INT);
                } else {
                    $filtervalue = optional_param($key, '', PARAM_INT);
                }

                // For magistere template.
                $ftpl->fcode = $key;
                $flatoptions = [];
                foreach ($afilter->options as $k => $opt) {
                    $optiontpl = new StdClass;
                    $optiontpl->name = $k;
                    $optiontpl->id = $opt->id;
                    $optiontpl->value = $opt->value;
                    $optiontpl->counter = $opt->counter;
                    $optiontpl->checked = '';
                    if (is_array($filtervalue) && in_array($opt->id, $filtervalue)) {
                        $optiontpl->checked = 'checked="checked"';
                    }
                    $ftpl->options[] = $optiontpl;
                    $flatoptions[$k] = "{$opt->value} ({$opt->counter})";
                }

                // For standard template.
                $ftpl->fselect = html_writer::select($flatoptions, $key, $afilter->value);

                $template->filters[] = $ftpl;
            }
        }

        $template->hasfilters = count($template->filters);

        if ($layout == 'standard') {
            return $this->output->render_from_template('local_courseindex/filters', $template);
        } else {
            return $this->output->render_from_template('local_courseindex/magisterefilters', $template);
        }
    }

    public function search_results($results) {
        global $DB;

        $config = get_config('local_courseindex');

        foreach ($results as $result) {

            $coursetpl = $result;

            $coursetpl->accessmode = '';
            $context = context_course::instance($result->id);
            if (local_courseindex_supports_feature('layout/magistere')) {
                $coursetpl->accessmode = 'detailed';
                $coursetpl->ismagistere = true;
                if (has_capability('moodle/course:view', $context)) {
                    $coursetpl->accessmode = 'direct';
                }
            }

            if ($config->trimmode == 'words') {
                if (empty($config->trimlength1)) {
                    $config->trimlength1 = 20;
                }
                $coursetpl->fullname = $this->trim_words($coursetpl->fullname, $config->trimlength1);
            } else if ($config->trimmode == 'chars') {
                if (empty($config->trimlength1)) {
                    $config->trimlength1 = 80;
                }
                $coursetpl->fullname = $this->trim_char($coursetpl->fullname, $config->trimlength1);
            }

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

            $description = format_text($DB->get_field('course', 'summary', array('id' => $result->id)));
            if ($config->trimmode == 'words') {
                if (empty($config->trimlength2)) {
                    $config->trimlength2 = 120;
                }
                $description = $this->trim_words($description, $config->trimlength2);
            } else if ($config->trimmode == 'chars') {
                if (empty($config->trimlength2)) {
                    $config->trimlength2 = 400;
                }
                $description = $this->trim_char($description, $config->trimlength2);
            }
            $description = clean_text($description, FORMAT_HTML);
            $coursetpl->description = $description;
            $coursetpl->imgurl = $this->get_course_image_url($result);
            $coursetpl->courseurl = new moodle_url('/course/view.php', array('id' => $result->id));

            $template->courses[] = $coursetpl;
        }

        return $this->output->render_from_template('local_courseindex/searchresults', $template);
    }

    protected function get_course_image_url($course) {
        global $CFG;

        if ($course instanceof stdClass) {
            $course = courseindex_get_course_list($course);
        }

        $imgurl = false; // Initiate search.
        $context = context_course::instance($course->id);

        foreach ($course->get_course_overviewfiles() as $file) {
            if ($isimage = $file->is_valid_image()) {
                $path = '/'. $file->get_contextid(). '/local_courseindex/';
                $path .= $file->get_filearea().'/0/'.$file->get_filepath().$file->get_filename();
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
            $imgurl = courseindex_image_url($imgname, 'theme');
        } else {
            return courseindex_image_url($imgname);
        }

        return $imgurl;
    }

    /**
     * Overriden in pro version only.
     * @param int $catid starting category id
     * @param string $catpath
     * @param array $cattree
     * @param array $courses
     * @param array $filters
     * @param string $pagingbar
     * @param string $categoryname
     */
    public function magistere_layout($catid, $catpath, $cattree, $courses, $filters, $pagingbar = '', $categoryname = '') {
        throw new moodle_exception("Only implemented in pro version");
    }

    public function course_actions($courseorid) {
        global $CFG, $USER;

        if (is_object($courseorid)) {
            $courseorid = $courseorid->id;
        }

        $template = new Stdclass;

        $context = context_course::instance($courseorid);

        $template->canaccess = false;
        if (isloggedin() && !isguestuser()) {
            if (is_enrolled($context, $USER->id) || has_capability('moodle/course:viewhiddencourses', $context)) {
                $template->canaccess = true;
                $template->courseurl = new moodle_url('/course/view.php', ['id' => $courseorid]);
            }
        }

        if (!$template->canaccess) {

            if (local_courseindex_is_selfenrolable_course($courseorid)) {

                if (is_object($courseorid)) {
                    $courseorid = $courseorid->id;
                }

                $template->canenrol = true;
                $template->courseenrolurl = new moodle_url('/course/view.php', ['id' => $courseorid]);
            }

            $fs = get_file_storage();

            // Is shop installed ?
            if (is_dir($CFG->dirroot.'/local/shop')) {
                include_once($CFG->dirroot.'/local/shop/xlib.php');
                include_once($CFG->dirroot.'/local/shop/classes/Catalog.class.php');
                include_once($CFG->dirroot.'/local/shop/classes/CatalogItem.class.php');
                include_once($CFG->dirroot.'/local/shop/classes/Shop.class.php');
                $relatedproduct = local_shop_related_product($courseorid);
                if ($relatedproduct) {
                    $catalog = new \local_shop\Catalog($relatedproduct->catalogid);
                    $availableshops = \local_shop\Shop::get_instances(array('catalogid' => $relatedproduct->catalogid));
                    if (!empty($availableshops)) {
                        $firstavailable = array_shift($availableshops);
                        $shopid = $firstavailable->id;
                    } else {
                        $shopid = 1;
                    }
                    $params = ['shopid' => $shopid,
                               'view' => 'shop',
                               'what' => 'import',
                               $relatedproduct->code => 1,
                               'origin' => 'courseindex',
                               'autodrive' => true];
                    $template->purchaseproducturl = new moodle_url('/local/shop/front/view.php', $params);
                    $template->hasshop = 1;

                    if ($leafleturl = $relatedproduct->get_leaflet_url()) {
                        $template->hasleaflet = true;
                        $template->leafleturl = $leafleturl;
                    }
                }
            }
        }

        return $this->output->render_from_template('local_courseindex/magisterecourseactions', $template);
    }

    public function coursebox($courseorid) {
        global $CFG, $USER;

        $config = get_config('local_courseindex');

        if (is_object($courseorid)) {
            $courseid = $courseorid->id;
        } else {
            $courseid = $courseorid;
        }

        $coursetpl = new StdClass;
        $course = get_course($courseid);
        $context = context_course::instance($course->id);

        $coursetpl->courseid = $courseid;
        $coursetpl->fullname = format_string($course->fullname);
        if ($config->trimmode == 'words') {
            $coursetpl->trimtitle = $this->trim_words(format_string($course->fullname), $config->trimlength1);
        } else {
            $coursetpl->trimtitle = $this->trim_char(format_string($course->fullname), $config->trimlength1);
        }

        $template->accessmode = 'detailed';
        if (has_capability('moodle/course:view', $context)) {
            $template->accessmode = 'direct';
        }

        $courseurl = new moodle_url('/course/view.php', array('id' => $courseid ));
        $coursetpl->courseurl = ''.$courseurl;
        $coursetpl->format = $course->format;

        $coursetpl->hasattributes = false;
        if (\local_courseindex\navigator::course_is_visible($course)) {
            $coursetpl->hiddenclass = '';
            $coursetpl->hiddenattribute = '';
        } else {
            $coursetpl->hasattributes = true;
            $coursetpl->hiddenattribute = $this->output->pix_icon('hidden', get_string('ishidden', 'local_my'), 'local_my');
            $coursetpl->hiddenclass = 'dimmed';
        }
        if (has_capability('moodle/course:manageactivities', $context, $USER, false)) {
            $coursetpl->hasattributes = true;
            $coursetpl->editingclass = 'can-edit';
            $coursetpl->editingattribute = $this->output->pix_icon('editing', get_string('canedit', 'local_my'), 'local_my');
        } else {
            $coursetpl->editingclass = '';
            $coursetpl->editingattribute = '';
        }
        if (local_courseindex_is_selfenrolable_course($course)) {
            $coursetpl->hasattributes = true;
            $coursetpl->selfenrolclass = 'selfenrol';
            $coursetpl->selfattribute = $this->output->pix_icon('unlocked', get_string('selfenrol', 'local_my'), 'local_my');
        } else {
            $coursetpl->selfenrolclass = '';
            $coursetpl->selfattribute = '';
        }
        if (local_courseindex_is_guestenrolable_course($course)) {
            $coursetpl->hasattributes = true;
            $coursetpl->guestenrolclass = 'guestenrol';
            $coursetpl->guestattribute = $this->output->pix_icon('guest', get_string('guestenrol', 'local_my'), 'local_my');
        } else {
            $coursetpl->guestenrolclass = '';
            $coursetpl->guestattribute = '';
        }
        if ($course->startdate > time()) {
            $coursetpl->hasattributes = true;
            $coursetpl->futureclass = 'future';
            $coursetpl->futureattribute = $this->output->pix_icon('future', get_string('future', 'local_my'), 'local_my');
        } else {
            $coursetpl->futureclass = '';
            $coursetpl->futureattribute = '';
        }

        if (!has_capability('local/courseindex:seecourseattributes', $context)) {
            // Hide all attributes if requested by capability.
            $coursetpl->hasattributes = false;
        }

        if ($course instanceof stdClass) {
            $course = courseindex_get_course_list($course);
        }

        $context = context_course::instance($course->id);

        foreach ($course->get_course_overviewfiles() as $file) {
            if ($isimage = $file->is_valid_image()) {
                $path = '/'. $file->get_contextid(). '/local_courseindex/';
                $path .= $file->get_filearea().'/0/'.$file->get_filepath().$file->get_filename();
                $coursetpl->imgurl = ''.file_encode_url("$CFG->wwwroot/pluginfile.php", $path, !$isimage);
                break;
            }
        }
        if (empty($coursetpl->imgurl)) {
            $coursetpl->imgurl = ''.$this->get_image_url('coursedefaultimage');
        }

        return $coursetpl;
    }

    /**
     * Cut the Course content.
     *
     * @param $str
     * @param $n
     * @param $end_char
     * @return string
     */
    function trim_char($str, $n = 500, $endchar = '...') {
        if (strlen($str) < $n) {
            return $str;
        }

        $str = preg_replace("/\s+/", ' ', str_replace(array("\r\n", "\r", "\n"), ' ', $str));
        if (strlen($str) <= $n) {
            return $str;
        }

        $out = "";
        $small = mb_substr($str, 0, $n);
        $out = $small.$endchar;
        return $out;
    }

    /**
     * Cut the Course content by words.
     *
     * @param $str input string
     * @param $n number of words max
     * @param $endchar unfinished string suffix
     * @return the shortened string
     */
    function trim_words($str, $w = 10, $endchar = '...') {

        // Preformatting.
        $str = str_replace(array("\r\n", "\r", "\n"), ' ', $str); // Remove all endlines
        $str = preg_replace('/\s+/', ' ', $str); // Reduce spaces.

        $words = explode(' ', $str);

        if (count($words) <= $w) {
            return $str;
        }

        $shortened = array_slice($words, 0, $w);
        $out = implode(' ', $shortened).' '.$endchar;
        return $out;
    }
}