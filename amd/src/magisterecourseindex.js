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
define(['jquery', 'core/log', 'core/config'], function($, log, cfg) {

    var magisterecourseindex = {

        init: function() {
            $('.courseindex-coursethumb').bind('click', this.load_course_detail);
            $('.courseindex-coursename').bind('click', this.load_course_detail);
            $('.courseindex-readmorelink').bind('click', this.load_course_detail);
            $('.modal-close').bind('click', this.close_course_detail);

            $('.ftoggle-handle').bind('click', this.toggle);
            $('#courseindex-modal-shadow').bind('click', this.close_course_detail);

            magisterecourseindex.opencurrenttree();

            log.debug("AMD local_courseindex magistere module init.");
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

        special_form_submit: function() {
            var statuschoice = document.forms['statusform'].lpstatus;
            if (statuschoice){ // might not be in page
                var statusix = statuschoice.selectedIndex;
                if (statusix) {
                    document.forms['specialform'].lpstatus.value = statuschoice.options[statusix].value;
                }
            }
            // document.forms['specialform'].submit();
        },

        // magistere layout related additions.
        // loads a course description or public front page cover in the detail panel
        load_course_detail: function() {

            var that = $(this);

            var waiter = '<div class="centered"><center><img id="detail-waiter" src="';
            waiter += cfg.wwwroot + '/pix/i/ajaxloader.gif" /></center></div>';
            $('#courseindex-course-detail-content').html(waiter);
            var coursebox = that.closest('.local-courseindex-fp-coursebox');

            if (coursebox.attr('data-format') === 'page') {
                // invokes a pure page content view. with no side blocks.
                var url = cfg.wwwroot + '/course/format/page/viewpage.php';
                url += '?id=' + coursebox.attr('data-course');
                url += '&page=' + coursebox.attr('data-page');

                $.get(url, function(data) {
                    $('#courseindex-course-detail-content').html(data);
                }, 'html');
            } else {
                // invokes a standard course info panel.
                var url = cfg.wwwroot + '/local/courseindex/ajax/viewcourse.php';
                url += '?id=' + coursebox.attr('data-course');

                $.get(url, function(data) {
                    $('#courseindex-course-detail-content').html(data);
                }, 'html');
            }

            $('body').addClass('courseindex-detail-open');
            $('#courseindex-modal-shadow').css('display', 'block');
        },

        close_course_detail: function() {
            $('body').removeClass('courseindex-detail-open');
            $('#courseindex-modal-shadow').css('display', 'none');
        },

        toggle: function() {
            var that = $(this);

            var target = that.attr('data-toggle');
            var toggleid = target.replace('toggle-', '');

            if ($('#' + target).css('display') === 'none') {
                $('#' + target).css('display', 'block');
                var src = $('#ftoggle-handle-' + toggleid + ' img').attr('src');
                if (src) {
                    src.replace('collapsed', 'expanded');
                }
                $('#ftoggle-handle-' + toggleid + ' img').attr('src', src);
                if ($('#ftoggle-handle-' + toggleid + ' i').hasClass('fa-caret-down')) {
                    $('#ftoggle-handle-' + toggleid + ' i').removeClass('fa-caret-down');
                    $('#ftoggle-handle-' + toggleid + ' i').addClass('fa-caret-up');
                } else {
                    $('#ftoggle-handle-' + toggleid + ' i').removeClass('fa-plus-square');
                    $('#ftoggle-handle-' + toggleid + ' i').addClass('fa-minus-square');
                }
            } else {
                $('#' + target).css('display', 'none');
                var src = $('#ftoggle-handle-' + toggleid + ' img').attr('src');
                if (src) {
                    src.replace('expanded', 'collapsed');
                }
                $('#ftoggle-handle-' + toggleid + ' img').attr('src', src);
                if ($('#ftoggle-handle-' + toggleid + ' i').hasClass('fa-caret-up')) {
                    $('#ftoggle-handle-' + toggleid + ' i').removeClass('fa-caret-up');
                    $('#ftoggle-handle-' + toggleid + ' i').addClass('fa-caret-down');
                } else {
                    $('#ftoggle-handle-' + toggleid + ' i').removeClass('fa-minus-square');
                    $('#ftoggle-handle-' + toggleid + ' i').addClass('fa-plus-square');
                }
            }
        },

        opencurrenttree: function() {
            var current = $('.is-current');
            var parents = current.parents('.subcats');
            var target;
            var toggleid;
            var that;

            parents.each(function() {
                // open each parent up to the top.
                that = $(this);
                target = that.attr('id');
                toggleid = target.replace('toggle-', '');
                that.css('display', 'block');
                var src = $('#ftoggle-handle-' + toggleid + ' img').attr('src');
                if (src) {
                    src.replace('collapsed', 'expanded');
                }
                if ($('#ftoggle-handle-' + toggleid + ' i').hasClass('fa-caret-down')) {
                    $('#ftoggle-handle-' + toggleid + ' i').removeClass('fa-caret-down');
                    $('#ftoggle-handle-' + toggleid + ' i').addClass('fa-caret-up');
                } else {
                    $('#ftoggle-handle-' + toggleid + ' i').removeClass('fa-plus-square');
                    $('#ftoggle-handle-' + toggleid + ' i').addClass('fa-minus-square');
                }
                $('#ftoggle-handle-' + toggleid + ' img').attr('src', src);
            });
        }

    };

    return magisterecourseindex;
});