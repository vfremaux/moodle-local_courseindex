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
 * Wrapper class to pro extended renderer.
 *
 * @package     local_courseindex
 * @author      Valery Fremaux <valery.fremaux@gmail.com>
 * @copyright   Valery Fremaux <valery.fremaux@gmail.com> (MyLearningFactory.com)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_courseindex\output;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/local/courseindex/pro/renderer.php');

/**
 * this is just a class wrapper in standard codespace to pro extended features. It will be
 * only used and loaded if pro features are present. It is imlplemented for the standard
 * $PAGE->get_renderer(pluginname, 'extended') call to succeed.
 */
class extended_renderer extends \local_courseindex_renderer_extended {
}
