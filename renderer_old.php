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
     * print a full navigation tree. Self Recursive.
     * @param string $str the string where to generate HTML flow
     * @param object $cattree the complete cat tree structure
     * @param int $depth logical starting depth (style control)
     * @param boolean $files tells if we need display entries here
     * @param int $levelix the category item depth
     * @param array $rpcoptions an array containing incomming XML-RPC remote call stuff
     * @return the count of real entries we had in category.
     */
    function navigation(&$str, &$cattree, $catpath, $depth, $files, $levelix = 0, $rpcoptions = null) {
        global $OUTPUT;

        $allcoursecount = 1;

        if (is_null($str)) {
            $str = '';
        }

        if (isset($cattree->error)) {
            $str .= "Catalog Error : " . $cattree->error;
        }

        switch ($files) {
            case DISPLAY_FILES_FIRST_LEVEL:
                $subfiles = DISPLAY_CATEGORIES;
                break;
            case DISPLAY_FILES:
                $subfiles = DISPLAY_FILES;
                break;
            default:
                $subfiles = DISPLAY_CATEGORIES;
        }

        // Sub categories
        $substr = '';
        if ($levelix == 0) {
            $substr .= $OUTPUT->heading(get_string('subcatsincat', 'local_courseindex'), 3);
        }

        $countcats = count($cattree->cats);
        $count = 0;
        $first = true;
        $last = false;
        if ($countcats) {
            if ($levelix == 0) {
                $substr .= '<div class="browser-category'.$levelix.'cont container-fluid">';
                $substr .= '<div class="browser-category row-fluid">';
            }
            foreach ($cattree->cats as $cat) {
                $substr .= '<div class="browser-category cell span4">';
                $levelix++;
                $nextpath = (empty($catpath)) ? $cattree->id : $catpath.','.$cattree->id ;
                $allcoursecount += $this->subnavigation($substr, $cat, $nextpath, $depth - 1, $subfiles, $levelix, $rpcoptions);
                $levelix--;
                $substr .= '</div>';
                $count++;
                if ($count && ($count % 3 == 0) && $levelix == 1) {
                    // New row start each 3 cells
                    $substr .= '</div>';
                    $substr .= '<div class="browser-category row-fluid">';
                }
            }
            if ($levelix == 0) {
                $substr .= '</div>';
                $substr .= '</div>';
            }
        } else {
                if ($levelix == 0) {
                    $substr .= $OUTPUT->notification(get_string('nosubcats', 'local_courseindex'), 'browseremptysignal');
                }
        }

        // Top category
        $str .= $this->current_category($cattree, $catpath, $depth, $levelix, $allcoursecount, $rpcoptions);

        $str .= $substr;

        // If current category level, print available files
        if ($files && $levelix == 0) {
            $str .= '<div class="browser-category-list">';
            $str .= '<div class="browser-category-list row-fluid">';
            $str .= '<div class="browser-category-list cell span12">';
            $str .= $OUTPUT->heading(get_string('lpsincategory', 'local_courseindex'), 3);
            $str .= '</div>';
            $str .= '</div>';
            $entries = $this->navigation_entries($str, $cattree, $rpcoptions);
            $str .= '</div>';
        }
        return $allcoursecount;
    }

    /**
     * print a full navigation tree. Self Recursive.
     * @param string $str the string where to generate HTML flow
     * @param object $cattree the complete cat tree structure
     * @param int $depth logical starting depth (style control)
     * @param boolean $files tells if we need display entries here
     * @param int $levelix the category item depth
     * @param boolean $jump do we have to jump column ?
     * @param array $rpcoptions an array containing incomming XML-RPC remote call stuff
     * @return the count of real entries we had in category.
     */
    function subnavigation(&$str, &$cattree, $catpath, $depth, $files, $levelix = 0, $rpcoptions = null) {
        global $OUTPUT;

        if (is_null($str)) {
            $str = '';
        }

        switch ($files) {
            case DISPLAY_FILES_FIRST_LEVEL:
                $subfiles = DISPLAY_CATEGORIES;
                break;
            case DISPLAY_FILES:
                $subfiles = DISPLAY_FILES;
                break;
            default:
                $subfiles = DISPLAY_CATEGORIES;
        }

        $countcats = count($cattree->cats);
        $count = 0;
        $first = true;
        $last = false;
        $substr = '';
        $allcoursecount = 0;
        if ($countcats) {
            foreach ($cattree->cats as $cat) {
                $levelix++;
                $nextpath = (empty($catpath)) ? $cattree->id : $catpath.','.$cattree->id ;
                $allcoursecount += $this->subnavigation($substr, $cat, $nextpath, $depth - 1, $subfiles, $levelix, $rpcoptions);
                $levelix--;
            }
        }

        $str .= '<div id="subnav'.$levelix.'" class="courseindex-browser">';
        $this->navigation_category_info($str, $cattree, $catpath, $depth, $levelix, $rpcoptions);
        $str .= $substr;
        $str .= '</div>';

        return $allcoursecount;
    }

    /**
     * Prints the category info in indented fashion
     * This function is only used by print_navigation() above
     * @param int $str @see tao_print_navigation()
     * @param int $cat the current category
     * @param array $rpcoptions @see tao_print_navigation()
     * @return the number of printed entries
     */
    function navigation_entries(&$str, &$cat, $rpcoptions = null) {
        global $CFG, $USER, $OUTPUT;
        static $strallowguests, $strrequireskey, $strsummary;
    
        if (empty($strsummary)) {
            $strallowguests = get_string('allowguests', 'local_courseindex');
            $strrequireskey = get_string('requireskey', 'local_courseindex');
            $strsummary = get_string('summary');
        }
        if (!empty($cat->entries)) {
            $str .= '<div class="courselist row-fluid">';
            $first = 'first';
            foreach ($cat->entries as $course) {
                // if hidden and we have no capability to see hiddens
                $context = context_course::instance($course->id);
                $str .= $this->course_block($course, $rpcoptions);
                $first = '';
            }
            $str .= '</div>';
        } else {
            $str .= $OUTPUT->notification(get_string('nocourses', 'local_courseindex'), 'browseremptysignal');
        }
        return count($cat->entries);
    }

    /**
     * Prints the category info in indented fashion
     * This function is only used by print_navigation() above
     * @param string $str @see local_courseindex_print_navigation()
     * @param object $cat the current category
     * @param object $depth style depth
     * @param object $levelix category level (needed for making link URLs)
     */
    function navigation_category_info(&$str, &$cat, $thispath, $depth, $levelix, $rpcoptions = null) {
        global $CFG, $OUTPUT, $DB;

        $config = get_config('local_courseindex');

        static $filterdepth;

        $str .= '<div class="browser-category-info">';

        $catpix ='';
        // prints subcategory bullets
        $startlinkstyle = '';
        $endlinkstyle = '';
        $visiblecount = 0;
        $hiddencount = 0;
        $courses = $cat->entries;
        $coursecount = count($courses);
        $coursecountstr = '';
        // check if category has courses for bolding
        if (!empty($courses)) {
            foreach ($courses as $course) {
                if (local_courseindex_navigation_course_is_visible($course)) {
                    $visiblecount++;
                } else {
                    $coursecontext = context_course::instance($course->id);
                    if (has_capability('moodle/course:viewhiddencourses', $coursecontext)) {
                        $hiddencount++;
                    }
                }
            } 
            $hiddencount = $coursecount - $visiblecount;
            if ($visiblecount) {
                $coursecountstr = '<span id="browser-cat'.$cat->id.'">0</span>';
;
                $startlinkstyle = '<b>';
                $endlinkstyle = '</b>';
            }
            if ($hiddencount) {
                $coursecountstr .= ' / '.get_string('hiddencourses', 'local_courseindex', $hiddencount);
            }
        }

        // compute path
        $catptr = &$cat;
        while (!empty($catptr->parent->id)) {
            $catpathelms[] = $catptr->parent->id;
            $catptr = &$catptr->parent;
        }
        $catpath = '';

        if (!empty($catpathelms)) {
            // $catpathelms = array_reverse($catpathelms);
            $catpath = implode(',', $catpathelms);
        }

        // if at top of the visible tree, but not starting at plain root, make the breadcrumb cat list from root
        $pathacc = '';
        if ($depth > 0 && $levelix > 0) {
            $catpathelms = explode(',', $thispath);
            for ($i = 0 ; $i < count($catpathelms) ; $i++) {
                $catpatharray = array();
                for ($j = 0 ; $j < $i ; $j++) {
                    $catpatharray[] = $catpathelms[$j];
                }
                $catpathtmp = implode(',', $catpatharray);
                $catnametmp = $DB->get_field($config->course_metadata_table, $config->course_metadata_value_key, array('id' => $catpathelms[$j]));
                $leveltmp = $i + 1;
                if (!isset($rpcoptions->wwwroot)) {
                    // Local category
                    $str .= '<div class="catgrouprow row-fluid">';
                    $str .= '<div class="catgroup span12">';
                    $browserurl = new moodle_url('/local/courseindex/browser.php', array('cat' => $catpathelms[$j], 'catpath' => $catpathtmp, 'level' => $leveltmp - 1));
                    $str .= '<a href="'.$browserurl.'" class="breadcrumbcat" >'.format_string($catnametmp).'</a>';
                    $str .= '</div>';
                    $str .= '</div>';
                } else {
                    // Remote category in a catalog Moodle
                    $str .= '<div class="catgrouprow row-fluid">';
                    $str .= '<div class="catgroup span12">';
                    $str .= '<a href="'.$rpcoptions->wwwroot.'/index.php?remcat='.$catpathelms[$j]."&amp;catpath={$catpathtmp}&amp;level={$leveltmp}\" class=\"breadcrumbcat\">". format_string($catnametmp).'</a>';
                    $str .= '</div>';
                    $str .= '</div>';
                }
            }
        }

        // resolve target level for category link
        // we need get some absolute reference in the whole category deepness
        $startpath = optional_param('catpath', '', PARAM_TEXT);
        $startlevel = optional_param('level', '', PARAM_TEXT);
        if ($startpath == 0) {
            $realdepth = 0;
        } else {
            $realdepth = substr_count($startpath, ',');
        }
        $catlevel = $startlevel + $realdepth + $levelix;
        // print category link
        if (!isset($rpcoptions->wwwroot)) {
            $str .= '<div class="catname row-fluid">';
            $str .= '<div class="category span10">';
            $browserurl = new moodle_url('/local/courseindex/browser.php', array('cat' => $cat->id, 'catpath' => $thispath, 'level' => $catlevel));
            $str .= '<a href="'.$browserurl.'">'.$startlinkstyle. format_string($cat->name).$endlinkstyle.'</a>';
            $str .= '</div>';
            $str .= '<div class="coursecount span2">';
            $str .= $coursecountstr;
            $str .= '<script type="text/javascript">$(\'#browser-cat'.$cat->id.'\').animateNumber({ number: '.$visiblecount.'});</script>';
            $str .= '</div>';
            $str .= '</div>';
       } else {
            $str .= '<div class="catname row-fluid">';
            $str .= '<div class="category span10">';
            $str .= '<a href="'.$rpcoptions->wwwroot.'/index.php?remcat='.$cat->id.'&amp;catpath='.$thispath.'&amp;level='.$catlevel.'">'.$startlinkstyle. format_string($cat->name).$endlinkstyle.'</a>';
            $str .= '</div>';
            $str .= '<div class="coursecount span2">';
            $str .= $coursecountstr;
            $str .= '<script type="text/javascript">$(\'#browser-cat'.$cat->id.'\').animateNumber({ number: '.$visiblecount.'});</script>';
            // $str .= $coursecountstr;
            $str .= '</div>';
            $str .= '</div>';
        }

        $str .= '</div>';
    }

    /**
     * print a full navigation tree with items in categories. Self Recursive.
     * @param string $str the string where to generate HTML flow
     * @param object $cattree the complete cat tree structure
     * @param int $depth logical starting depth (style control)
     * @param boolean $files tells if we need display entries here
     * @param int $levelix the category item depth
     * @param boolean $jump do we have to jump column ?
     * @param array $rpcoptions an array containing incomming XML-RPC remote call stuff
     * @return the count of real entries we had in category.
     */
    function navigation_simple(&$str, &$cattree, $catpath = '') {
        global $CFG, $DB;
    
        static $levelix = 1;
        $coursestr = get_string('course');
        $startdatestr = get_string('startdate', 'local_courseindex');
        $lpstatusstr = get_string('lpstatus', 'local_courseindex');
        if (is_null($str)) {
            $str = '';
        }
        if (isset($cattree->error)) {
            $str .= "Catalog Error : " . $cattree->error;
        }
        if ($levelix == 1) {
            $str .= '<div class="browser-category">';
        }
        if ($levelix > 1) {
            if (!empty($cattree->entries)) {
                foreach ($cattree->entries as $entry) {
                    $visible = true;
                    if (!$entry->visible) {
                        if (!has_capability('moodle/course:viewhiddencourses', context_course::instance($entry->id))) {
                            $visible = false;
                            continue;
                        }
                    }
                    // Check if we can see against our capabilities on categories.
                    $category = $DB->get_record('course_categories', array('id' => $entry->category));
                    if (!$category->visible) {
                        if (!has_capability('moodle/category:viewhiddencategories', context_coursecat::instance($category->id))) {
                            continue;
                        }
                        $visible = false;
                    }
                    while ($category->parent != 0) {
                        $category = $DB->get_record('course_categories', array('id' => $category->parent));
                        if (!$category->visible) {
                            if (!has_capability('moodle/category:viewhiddencategories', context_coursecat::instance($category->id))) {
                                continue;
                            }
                            $visible = false;
                        }
                    }
                    $class = '';
                    if (!$visible) {
                        $class = " class=\"dimmed\" ";
                    }
                    $keystr = get_string('needspassword', 'local_courseindex');
                    $gueststr = get_string('guestallowed', 'local_courseindex');
                    $enrolstr = get_string('openenrol', 'local_courseindex');
                    $keyed = ($entry->password) ? '<img src="'.$OUTPUT->pix_url('i/key').'" title="'.$keystr.'" />' : '';
                    $guest = ($entry->guest) ? ' <img src="'.$OUTPUT->pix_url('i/guest').'" title="'.$gueststr.'" />' : '';
                    $enrol = ($entry->enrollable) ? ' <img src="'.$OUTPUT->pix_url('t/manual_item').'" title="'.$enrolstr.'" />' : '';
                    $courseurl = new moodle_url('/course/view.php', array('id' => $entry->id));
                    $str .= '<div class="browser category row-fluid">';
                    $str .= '<div class="courseline lpcourse'.$levelix.' cell">';
                    $str .= '<a href="'.$courseurl.'" '.$class.' >'.format_string($entry->fullname).'</a></div>';
                    $str .= '<div class="courseattrs cell">'.$keyed.' '.$enrol.' '.$guest.'</div>';
                    $str .= '</div>'; // row
                }
            }
        }
        if (!empty($cattree->cats)) {
            foreach ($cattree->cats as $cat) {
                $str .= '<div class=" row">';
                $str .= '<div class="lpcat'.$levelix.' cell">'.format_string($cat->name).'</td>';
                $str .= '</div>';
                $levelix++;
                $nextpath = (empty($catpath)) ? $cattree->id : $catpath.','.$cattree->id;
                $this->navigation_simple($str, $cat, $nextpath);
                $levelix--;
            }
        }
        if ($levelix == 1) {
            if (!empty($cattree->entries)) {
                foreach ($cattree->entries as $entry) {
                    $visible = true;
                    if (!$entry->visible) {
                        if (!has_capability('moodle/course:viewhiddencourses', context_course::instance($entry->id))) {
                            continue;
                        }
                        $visible = false;
                    }
                    // check if we can see against our capabilities on categories
                    $category = $DB->get_record('course_categories', array('id' => $entry->category));
                    if (!$category->visible) {
                        if (!has_capability('moodle/category:viewhiddencategories', context_coursecat::instance($category->id))) {
                            continue;
                        }
                        $visible = false;
                    }
                    while ($category->parent != 0) {
                        $category = $DB->get_record('course_categories', array('id' => $category->parent));
                        if (!$category->visible) {
                            if(!has_capability('moodle/category:viewhiddencategories', context_coursecat::instance($category->id))) {
                                continue;
                            }
                            $visible = false;
                        }
                    }
                    $class = '';
                    if (!$visible) {
                        $class = " class=\"dimmed\" ";
                    }
                    $courseurl = new moodle_url('/course/view.php', array('id' => $entry->id));
                    $str .= '<div class="row-fluid">';
                    $str .= $this->course_block($entry);
                    $str .= '</div>'; // Row
                }
            }
        }
        if ($levelix == 1) {
            $str .= '</div>';  // Container
        }
    }

    /**
     * A course block prints a span4 course block using fullname, thumb image if found
     * in description filearea or in associated files (first available) and possibly part
     * of the description in a read more collapser.
     */
    function course_block($course, $rpcoptions = null) {

        $str = '';
        $fs = get_file_storage();

        if (!isset($rpcoptions->wwwroot)) {
            // Getting course list from local host.
            $courseurl = new moodle_url('/course/view.php', array('id' => $course->id));
        } else {
            // Getting course list through remote RCP access
            // Note : it is remote host that knows the id of its mnet 'WWW' peer
            $courseurl = new moodle_url('/auth/mnet/jump.php', array('hostid' => '<%%WWWHOSTID%%>', 'wantsurl' => urlencode('/course/view.php?id='.$course->id)));
        }

        $css = $course->visible ? '' : 'dimmed';

        $str .= '<div class="browser-courseblock span3 '.$css.'">';
        $str .= '<div class=" row-fluid">';
        $str .= '<div class="title span12"><a href="'.$courseurl.'">'.$course->fullname.'</a></div>';
        $str .= '</div>';

        $context = context_course::instance($course->id);
        $images = $fs->get_area_files($context->id, 'course', 'overviewfiles', 0);
        if ($image = array_pop($images)) {
            $coursefileurl = moodle_url::make_pluginfile_url($context->id, 'course', 'overviewfiles', '', $image->get_filepath(), $image->get_filename());
            $str .= '<div class="row-fluid">';
            $str .= '<div class="courseimage span12"><img src="'.$coursefileurl.'" /></div>';
            $str .= '</div>';
        }

        $str .= '<div class="row-fluid">';
        $str .= '<div class="summary span12">'.format_string($course->summary).'</div>';
        $str .= '</div>';

        $str .= '</div>';

        return $str;
    }

    function current_category(&$cattree, &$catpath, $depth, $levelix, $allcoursecount, $rpcoptions) {

        $str = '';

        $str .= '<div class="browser-current-category row-fluid">';

        $str .= '<div class="browser-current-category-label span2">';
        $str .= get_string('currentcategory', 'local_courseindex');
        $str .= '</div>';

        $str .= '<div class="browser-current-category-info span9">';
        $str .= format_string($cattree->name);
        $str .= '</div>';

        $str .= '<div id="browser-cat'.$cattree->id.'" class="browser-current-category-count span1">';
        // $str .= 0 + $allcoursecount;
        $str .= '<script type="text/javascript">$(\'#browser-cat'.$cattree->id.'\').animateNumber({ number: '.(0 + @$allcoursecount).' });</script>';
        $str .= '</div>';

        $str .= '</div>';

        return $str;
    }

    function cat_struct_debug($cattree) {
        $str = '';
        $indent = 0;

        if (!$indent) {
            $str .= '<pre>';
        }

        $str .= str_pad("&nbsp;&nbsp;&nbsp;", $indent).$cattree->id.' '.$cattree->name."\n";
        foreach ($cattree->cats as $catid => $cat) {
            $indent++;
            $str .= $this->cat_struct_debug($cat);
            $indent--;
        }

        if (!$indent) {
            $str .= '</pre>';
        }

        return $str;
    }

    public function explorerlink() {

        $str = '';
        $searchstr = get_string('searchintree', 'local_courseindex');
        $exploreurl = new moodle_url('/local/courseindex/explorer.php');
        $str .= '<center><a href="'.$exploreurl.'">'.$searchstr.'</a></center>';
        $str .= '<br/>';

        return $str;
    }

    public function filters($catid) {
        $str = '';

        $browserurl = new moodle_url('/local/courseindex/browser.php', array('catid' => $catid));

        $str .= '<form name="filtering" action="'.$browserurl.'" method="post">';
        $str .= '<input type="hidden" name="cat" value="'.$cat.'"/>';
        $str .= '<input type="hidden" name="level" value="'.$level.'"/>';
        $str .= '<input type="hidden" name="catpath" value="'.$catpath.'"/>';

        if (!empty($filters)) {
            $str .= '<fieldset>';
            $str .= '<table cellspacing="10"><tr><td>';
            foreach ($filters as $key => $afilter) {
                if ($key != 'wwwroot') {
                    $str .= '<b><span style="font-size:80%">'.$afilter->name.' :</span></b><br/>';
                    $str .= html_writer::select($afilter->options, $key, $afilter->value);
                    $str .= '</td><td>';
                }
            }
            $strreload = get_string('reload', 'local_courseindex');

            $str .= '<td><input type="submit" name="go_btn" value="'.$strreload.'" /></td>';
            $str .= '</td></tr></table>';
            $str .= '</fieldset>';
        }

        $str .= '</form>';

        return $str;
    }
}