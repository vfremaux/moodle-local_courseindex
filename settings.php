<?php
// This file is NOT part of Moodle - http://moodle.org/
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
 * @category   local
 * @author     Valery Fremaux <valery.fremaux@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if ($hassiteconfig) { // needs this condition or there is error on login page
    $settings = new admin_settingpage('local_courseindex', get_string('pluginname', 'local_courseindex'));
    $ADMIN->add('localplugins', $settings);

    $settings->add(new admin_setting_configcheckbox('local_courseindex/indexisopen', get_string('configopenindex', 'local_courseindex'),
                       get_string('configopenindex_desc', 'local_courseindex'), 1));

    $settings->add(new admin_setting_configtext('local_courseindex/maxnavigationdepth', get_string('configmaxnavigationdepth', 'local_courseindex'),
                       get_string('configmaxnavigationdepth_desc', 'local_courseindex'), 3));

    $settings->add(new admin_setting_configcheckbox('local_courseindex/classification_display_empty_level_0', get_string('configclassificationdisplayemptylevel0', 'local_courseindex'),
                       get_string('configclassificationdisplayemptylevel0_desc', 'local_courseindex'), 1));

    $settings->add(new admin_setting_configcheckbox('local_courseindex/classification_display_empty_level_1', get_string('configclassificationdisplayemptylevel1', 'local_courseindex'),
                       get_string('configclassificationdisplayemptylevel1_desc', 'local_courseindex'), 1));

    $settings->add(new admin_setting_configcheckbox('local_courseindex/classification_display_empty_level_2', get_string('configclassificationdisplayemptylevel2', 'local_courseindex'),
                       get_string('configclassificationdisplayemptylevel2_desc', 'local_courseindex'), 1));

    $settings->add(new admin_setting_heading('metadatabinding', get_string('configmetadatabinding', 'local_courseindex'), get_string('configmetadatabinding_desc', 'local_courseindex')));

    $settings->add(new admin_setting_configtext('local_courseindex/course_metadata_table', get_string('configcoursemetadatatable', 'local_courseindex'),
                       get_string('configcoursemetadatatable_desc', 'local_courseindex'), 'customlabel_course_metadata'));

    $settings->add(new admin_setting_configtext('local_courseindex/course_metadata_course_key', get_string('configcoursemetadatacoursekey', 'local_courseindex'),
                       get_string('configcoursemetadatacoursekey_desc', 'local_courseindex'), 'courseid'));

    $settings->add(new admin_setting_configtext('local_courseindex/course_metadata_value_key', get_string('configcoursemetadatavaluekey', 'local_courseindex'),
                       get_string('configcoursemetadatavaluekey_desc', 'local_courseindex'), 'valueid'));

    $settings->add(new admin_setting_configtext('local_courseindex/classification_value_table', get_string('configclassificationvaluetable', 'local_courseindex'),
                       get_string('configclassificationvaluetable_desc', 'local_courseindex'), 'customlabel_mtd_value'));

    $settings->add(new admin_setting_configtext('local_courseindex/classification_value_type_key', get_string('configclassificationvaluetypekey', 'local_courseindex'),
                       get_string('configclassificationvaluetypekey_desc', 'local_courseindex'), 'typeid'));

    $settings->add(new admin_setting_configtext('local_courseindex/classification_type_table', get_string('configclassificationtypetable', 'local_courseindex'),
                       get_string('configclassificationtypetable_desc', 'local_courseindex'), 'customlabel_mtd_type'));

    $settings->add(new admin_setting_configtext('local_courseindex/classification_constraint_table', get_string('configclassificationconstrainttable', 'local_courseindex'),
                       get_string('configclassificationconstrainttable_desc', 'local_courseindex'), 'customlabel_mtd_constraint'));


}

