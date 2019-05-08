
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
 * Javascript controller for controlling the sections.
 *
 * @module     block_multicourse_navigation/collapse_control
 * @package    block_multicourse_navigation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// jshint unused: true, undef:true
define(['jquery', 'core/log'], function($, log) {

    var courseindex = {

        init: function() {
            $('#id-courseindex-search-submit').bind('click', this.search_form_submit);
            $('#id-courseindex-textsearch-submit').bind('click', this.textsearch_form_submit);
            $('#id-courseindex-special-submit').bind('click', this.special_form_submit);

            log.debug("AMD local_courseindex module init.");
        },

        search_form_submit: function() {
            var statuschoice = document.forms['statusform'].lpstatus;
            if (statuschoice) {
                // might not be in page
                var statusix = statuschoice.selectedIndex;
                if (statusix) {
                    document.forms['classifierform'].lpstatus.value = statuschoice.options[statusix].value;
                }
            }
        },

        textsearch_form_submit: function() {
            var statuschoice = document.forms['textsearchform'].lpstatus;
            if (statuschoice) {
                // Might not be in page.
                var statusix = statuschoice.selectedIndex;
                if (statusix) {
                    document.forms['textsearchform'].lpstatus.value = statuschoice.options[statusix].value;
                }
            }
        },

        special_form_submit: function(){
            var statuschoice = document.forms['statusform'].lpstatus;
            if (statuschoice){ // might not be in page
                var statusix = statuschoice.selectedIndex;
                if (statusix) {
                    document.forms['specialform'].lpstatus.value = statuschoice.options[statusix].value;
                }
            }
            // document.forms['specialform'].submit();
        }

    };

    return courseindex;
});