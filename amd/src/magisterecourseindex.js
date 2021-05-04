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

        init: function(args) {

            $('.courseindex-coursethumb.direct').bind('click', this.goto_course);
            $('.courseindex-coursename.direct').bind('click', this.goto_course);
            $('.courseindex-coursethumb.detailed').bind('click', this.load_course_detail);
            $('.courseindex-coursename.detailed').bind('click', this.load_course_detail);
            $('.courseindex-readmorelink').bind('click', this.load_course_detail);
            $('.modal-close').bind('click', this.close_course_detail);
            $('.courseindex-filter-value').bind('change', this.submitfilters);

            $('.ftoggle-handle').bind('click', this.toggle);
            $('#courseindex-modal-shadow').bind('click', this.close_course_detail);

            $('#search-input').bind('keypress', this.search_form_submit);

            if ($('#courseindex-nav').length) {
                // If in browser.
                magisterecourseindex.opencurrenttree();
            }

            args = args.replace(/,/g, '-');
            var cattoopen = '#ftoggle-handle' + args;
            $(cattoopen).trigger('click');

            log.debug("AMD local_courseindex magistere module init.");
        },

        submitfilters: function () {
            $('#courseindex-magistere-filter-form').submit();
        },

        search_form_submit: function(ev) {
            var keycode = (ev.keyCode ? ev.keyCode : ev.which);
            if (keycode == '13') {
                var url = cfg.wwwroot + '/local/courseindex/explorer.php';
                url += '?lpstatus=';
                url += '&searchtext=' + $('#search-input').val();
                url += '&title=1&description=1&go_freesearch=Chercher';

                window.location = url;
            }
        },

        // magistere layout related additions.
        // loads a course description or public front page cover in the detail panel
        load_course_detail: function() {

            var that = $(this);

            var waiter = '<div class="centered"><center><img id="detail-waiter" src="';
            waiter += cfg.wwwroot + '/pix/i/ajaxloader.gif" /></center></div>';
            $('#courseindex-course-detail-content').html(waiter);
            $('#courseindex-course-detail-actions').html(waiter);
            var coursebox = that.closest('.local-courseindex-fp-coursebox');

            // Self adapts when "page" format is installed.
            // @see https://github.com/vfremaux/moodle-format_page
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
                var url = cfg.wwwroot + '/local/courseindex/pro/ajax/viewcourse.php';
                url += '?id=' + coursebox.attr('data-course');

                $.get(url, function(data) {
                    $('#courseindex-course-detail-content').html(data);
                }, 'html');
            }

            // Invokes the action fragment.
            var url = cfg.wwwroot + '/local/courseindex/pro/ajax/courseactions.php';
            url += '?id=' + coursebox.attr('data-course');

            $.get(url, function(data) {
                $('#courseindex-course-detail-actions').html(data);
            }, 'html');

            $('body').addClass('courseindex-detail-open');
            $('#courseindex-modal-shadow').css('display', 'block');
        },

        close_course_detail: function() {
            $('body').removeClass('courseindex-detail-open');
            $('#courseindex-modal-shadow').css('display', 'none');
        },

        goto_course: function() {
            var that = $(this);

            var coursebox = that.closest('.local-courseindex-fp-coursebox');
            var url = cfg.wwwroot + '/course/view.php';
            url += '?id=' + coursebox.attr('data-course');
            window.location = url;
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