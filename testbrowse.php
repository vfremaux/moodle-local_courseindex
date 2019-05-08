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
 */

require('../../config.php');
require_once($CFG->dirroot.'/local/lib.php');
require_once($CFG->dirroot.'/local/courseindex/rpclib.php');

$url = new moodle_url('/local/courseindex/testbrowse.php');
$PAGE->set_url($url);

$context = context_system::instance();
$PAGE->set_context($context);

require_login();

echo $OUTPUT->header();

if (empty($config->enabled)) {
    print_error('disabled', 'local_courseindex');
}

$cat = optional_param('cat', null, PARAM_INT);
$filter = optional_param('filter', 0, PARAM_INT);
$rcpoptions = new StdClass();
/*
$cattree = tao_generate_navigation($cat, $filter);
// print_object($cattree);
$str = '';
tao_print_navigation($str, $cattree, 0, 2, null, $filter, false, $rcpoptions);
*/
echo courseindex_get_catalog('admin', 'http://ac-test1.prfcommon.fr:8080', 'http://ac-test1.prfcommon.fr:8080', 0, 0, $filters = '');
echo "<br/><a href=\"?cat=0\">revenir</a>";
