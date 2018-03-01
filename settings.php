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

if ($hassiteconfig) {

    // Needs this condition or there is error on login page.

    $settings = new admin_settingpage('local_courseindex', get_string('pluginname', 'local_courseindex'));
    $ADMIN->add('localplugins', $settings);

    $label = get_string('configfeatures', 'local_courseindex');
    $settings->add(new admin_setting_heading('featureshdr', $label, ''));

    $key = 'local_courseindex/indexisopen';
    $label = get_string('configopenindex', 'local_courseindex');
    $desc = get_string('configopenindex_desc', 'local_courseindex');
    $settings->add(new admin_setting_configcheckbox($key, $label, $desc, 1));

    $key = 'local_courseindex/enableexplorer';
    $label = get_string('configenableexplorer', 'local_courseindex');
    $desc = get_string('configenableexplorer_desc', 'local_courseindex');
    $settings->add(new admin_setting_configcheckbox($key, $desc, $desc, 1));

    /* Graphics configuration */

    $label = get_string('configgraphics', 'local_courseindex');
    $settings->add(new admin_setting_heading('graphicshdr', $label, ''));

    $key = 'local_my/rendererimages';
    $label = get_string('configrendererimages', 'local_courseindex');
    $desc = get_string('configrendererimages_desc', 'local_courseindex');
    $options = array('subdirs' => false, 'maxfiles' => 20);
    $settings->add(new admin_setting_configstoredfile($key, $label, $desc, 'rendererimages', 0, $options));

    $label = get_string('configmetadatabinding', 'local_courseindex');
    $desc = get_string('configmetadatabinding_desc', 'local_courseindex');
    $settings->add(new admin_setting_heading('metadatabindinghdr', $label, $desc));

    $key = 'local_courseindex/course_metadata_table';
    $label = get_string('configcoursemetadatatable', 'local_courseindex');
    $desc = get_string('configcoursemetadatatable_desc', 'local_courseindex');
    $settings->add(new admin_setting_configtext($key, $label, $desc, 'customlabel_course_metadata'));

    $key = 'local_courseindex/course_metadata_course_key';
    $label = get_string('configcoursemetadatacoursekey', 'local_courseindex');
    $desc = get_string('configcoursemetadatacoursekey_desc', 'local_courseindex');
    $settings->add(new admin_setting_configtext($key, $label, $desc, 'courseid'));

    $key = 'local_courseindex/course_metadata_value_key';
    $label = get_string('configcoursemetadatavaluekey', 'local_courseindex');
    $desc = get_string('configcoursemetadatavaluekey_desc', 'local_courseindex');
    $settings->add(new admin_setting_configtext($key, $label, $desc, 'valueid'));

    $key = 'local_courseindex/classification_value_table';
    $label = get_string('configclassificationvaluetable', 'local_courseindex');
    $desc = get_string('configclassificationvaluetable_desc', 'local_courseindex');
    $settings->add(new admin_setting_configtext($key, $label, $desc, 'customlabel_mtd_value'));

    $key = 'local_courseindex/classification_value_type_key';
    $label = get_string('configclassificationvaluetypekey', 'local_courseindex');
    $desc = get_string('configclassificationvaluetypekey_desc', 'local_courseindex');
    $settings->add(new admin_setting_configtext($key, $label, $desc, 'typeid'));

    $key = 'local_courseindex/classification_type_table';
    $label = get_string('configclassificationtypetable', 'local_courseindex');
    $desc = get_string('configclassificationtypetable_desc', 'local_courseindex');
    $settings->add(new admin_setting_configtext($key, $label, $desc, 'customlabel_mtd_type'));

    $key = 'local_courseindex/classification_constraint_table';
    $label = get_string('configclassificationconstrainttable', 'local_courseindex');
    $desc = get_string('configclassificationconstrainttable_desc', 'local_courseindex');
    $settings->add(new admin_setting_configtext($key, $label, $desc, 'customlabel_mtd_constraint'));
}

