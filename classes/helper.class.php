<?php


namespace local_courseindex;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/customlabel/type/sessions/customlabel.class.php');

use customlabel_type_sessions;

class helper {

    /**
     * Scans all moodle for customlabel subtype sessions.
     */
    public static function get_all_geomarkers() {
        global $DB;
        static $COURSES = [];

        $allmarkers = $DB->get_records('customlabeltype_sessions', ['display' => 1]);

        if (!$allmarkers) {
            return [];
        }

        foreach ($allmarkers as &$marker) {
            if (!array_key_exists($marker->courseid, $COURSES)) {
                $COURSES[$marker->courseid] = $DB->get_field('course', 'fullname', ['id' => $marker->courseid]);
            }
            $marker->course = $COURSES[$marker->courseid];
            $marker->timestart = userdate($marker->timestart);
            $marker->timeend = userdate($marker->timeend);
            $marker->duration = customlabel_type_sessions::get_attribute('duration', $marker->duration);
            $marker->mode = customlabel_type_sessions::get_attribute('mode', $marker->mode);
            // Add cost ? 
        }

        return $allmarkers;
    }

}