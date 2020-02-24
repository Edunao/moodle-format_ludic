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
 * Main js file of format_ludic
 *
 * @package   format_ludic
 * @copyright 2020 Edunao SAS (contact@edunao.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// Javascript functions for ludic course format

M.course = M.course || {};

M.course.format = M.course.format || {};

/**
 * Get sections config for this format
 *
 * The section structure is:
 * <ul class="topics">
 *  <li class="section">...</li>
 *  <li class="section">...</li>
 *   ...
 * </ul>
 *
 * @return {object} section list configuration
 */
M.course.format.get_config = function () {
    return {
        container_node: 'div',
        container_class: 'container-parents',
        section_node: 'div',
        section_class: 'section'
    };
};

console.log('FORMAT LUDIC JS => OK');

define(['jquery', 'jqueryui'], function ($, ui) {
    let courseid = null;
    let userid = null;
    let editmode = null;
    let ludic = {

        /**
         * Always called in a format_ludic page
         */
        init: function (params) {
            ludic.courseid = params.courseid;
            ludic.userid = params.userid;
            ludic.editmode = params.editmode;
            ludic.init_events();
            if (ludic.editmode) {
                ludic.init_editmode();
            }
        },
        /**
         * Send an ajax request
         * @param {object} params
         */
        ajax_call: function (params) {
            console.log('ajax call ', params);
            let that = this;
            //check optional params
            params.courseid = ludic.courseid;
            params.userid = ludic.userid;
            let datatype = params.datatype ? params.datatype : 'html';
            let method = params.method ? params.method : 'GET';
            let url = params.url ? params.url : M.cfg.wwwroot + '/course/format/ludic/ajax/ajax.php';
            let callback = params.callback ? params.callback : null;
            let callbackerror = params.error ? params.error : null;
            //delete params to not send them in the request
            delete params.datatype;
            delete params.method;
            delete params.url;
            delete params.callback;
            $.ajax({
                method: method,
                url: url,
                data: params,
                dataType: datatype,
                error: function (jqXHR, error, errorThrown) {
                    if (typeof callbackerror === 'function') {
                        callbackerror(jqXHR, error, errorThrown);
                    } else if ((jqXHR.responseText.length > 0) && (jqXHR.responseText.indexOf('pagelayout-login') !== -1)) {
                        that.redirect_login();
                    } else {
                        that.error_popup();
                    }
                }
            }).done(function (response) {
                if ((response.length > 0) && (response.indexOf('pagelayout-login') !== -1)) {
                    that.redirect_login();
                }

                if (typeof callback === 'function') {
                    callback(response);
                }
            });
        },
        /**
         * Redirect to login page.
         */
        redirect_login: function () {
            window.location.href = M.cfg.wwwroot + '/login/index.php';
        },
        /**
         * Initialize all the general events to be monitored at startup in this function.
         */
        init_events: function () {
            console.log('init_events');
            let body = $('body.format-ludic');

            // For each button makes an ajax call to the controller defined in data-controller with the action defined in data-action.
            // Then call a callback function defined in data-callback.
            body.on('click', '.ludic-button', function () {
                console.log('click on button');
                let callback = $(this).data('callback');
                let controller = $(this).data('controller');
                let action = $(this).data('action');
                if (controller && action) {
                    ludic.ajax_call({
                        controller: controller,
                        action: action,
                        callback: function (params) {
                            ludic.call_function(callback, params);
                        }
                    });
                }
            });

            // When you click on an item in .container-parents, call the get_children() function on the controller of the same type.
            // Then display the return in .container-children.
            body.on('click', '.container-items .container-parents .item', function () {
                console.log('click on item, get_children');
                ludic.ajax_call({
                    id: $(this).data('id'),
                    controller: $(this).data('type'),
                    action: 'get_children',
                    callback: function (html) {
                        $('.container-items .container-children').html(html);
                    }
                });
            });


        },
        /**
         * Initialize all events specific to the edit mode to be monitored at startup in this function.
         */
        init_editmode: function () {
            console.log('init_editmode');

            let body = $('body.format-ludic');

            ludic.init_popup();
            ludic.init_drag_and_drop();

            // When you click on an item in .container-parents, call the get_properties() function on the controller of the same type.
            // Then display the return in .container-properties.
            body.on('click', '.container-items .container-parents .item', function () {
                console.log('click on item, get_properties');
                ludic.ajax_call({
                    id: $(this).data('id'),
                    controller: $(this).data('type'),
                    action: 'get_properties',
                    callback: function (html) {
                        $('.container-properties').html(html);
                        console.log('M ', M);
                        //
                        let options = $('.container-properties .ludic-filepicker-container').data('options');
                        console.log(options);
                        // M.core_filepicker.instances = [];
                        // Y.use('node', 'node-event-simulate', function(Y) {
                        //     console.log('grgrgrg');
                        // });
                        M.form_filepicker.init(Y, options);
                    }
                });
            });
        },
        /**
         * Initialize all events specific to the display of popups to be monitored at startup in this function.
         */
        init_popup: function () {
            let body = $('body.format-ludic');

            // Add a background for the display of popup.
            body.prepend('<div id="ludic-background"></div>');

            // Close ludic popup.
            body.on('click', '.close-ludic-popup', function () {
                $('#ludic-background').hide();
                $('.format-ludic.ludic-popup').hide();
            });
        },
        /**
         * Initialize all drag and drop specific events to monitor.
         */
        init_drag_and_drop: function () {
            let body = $('body.format-ludic');

            // Save the selector id of the drag object.
            body.on('dragstart', '.ludic-drag', function (e) {
                console.log('dragstart');
                e.originalEvent.dataTransfer.setData('text/plain', e.target.id);
            });

            // Required to allow drop.
            body.on('dragover', '.ludic-drop', function (e) {
                console.log('dragover');
                e.preventDefault();
            });

            // Management of drop sections and course modules.
            body.on('drop', '.section.ludic-drop, .coursemodule.ludic-drop', function (e) {
                console.log('drop');
                // Get drag item data.
                let dragitem = $('#' + e.originalEvent.dataTransfer.getData('text/plain'));
                let dragid = dragitem.data('id');
                let dragtype = dragitem.data('type');

                // Define the parent of the dragged object here to receive the html of the callback return.
                let dragparent = dragitem.parent();

                // Get drop item data.
                let dropitem = $('#' + e.target.id);
                let dropid = dropitem.data('id');
                let droptype = dropitem.data('type');

                // Define the action here.
                let action = false;
                if (dragtype === 'section' && droptype === 'section') {
                    action = 'move_section_to';
                } else if (dragtype === 'coursemodule' && droptype === 'section') {
                    action = 'move_to_section';
                } else if (dragtype === 'coursemodule' && droptype === 'coursemodule') {
                    action = 'move_on_section';
                }

                // If an action has been found, make an ajax call to the section controller.
                // Then set the html on the parent of the dragged object.
                if (action) {
                    console.log('execute ', action);
                    ludic.ajax_call({
                        idtomove: dragid,
                        toid: dropid,
                        controller: 'section',
                        action: action,
                        callback: function (html) {
                            dragparent.html(html);
                        }
                    });
                }
            });
        },
        /**
         * This function allows you to call another function dynamically with parameters.
         * @param name
         * @param params
         * @returns {boolean|void}
         */
        call_function: function (name, params) {
            // Ensures that params is an object.
            params = typeof params === "object" ? params : JSON.parse(params);
            console.log('call ', name, ' with params ', params);

            // Define all possible parameters here.
            let html = params.html ? params.html : false;

            // Call the right function with the right parameters.
            switch (name) {
                case 'replace_and_show_popup':
                    return ludic.replace_and_show_popup(html);
                default:
                    return false;
            }
        },
        /**
         * Replace the content of a popup and show it.
         * @param html
         */
        replace_and_show_popup: function (html) {
            console.log('replace_and_show_popup');
            $('.format-ludic.ludic-popup').replaceWith(html);
            ludic.show_popup();
        },
        /**
         * Show popup.
         */
        show_popup: function() {
            $('#ludic-background').show();
            $('.format-ludic.ludic-popup').show();
        },
        /**
         * Call the get_parents() function in ajax to the controller in parameter.
         * @param type
         */
        get_parents: function (type) {
            console.log('get_parents');
            ludic.ajax_call({
                controller: type,
                action: 'get_parents',
                callback: function (html) {
                    $('.container-items .container-parents').html(html);
                    $('.container-items .container-parents .item:first-child').trigger('click');
                }
            });
        },
        /**
         * Call the get_properties() function in ajax to the controller in parameter.
         * @param type
         */
        get_properties: function (type) {
            console.log('get_parents');
            ludic.ajax_call({
                controller: type,
                action: 'get_parents',
                callback: function (html) {
                    $('.container-items .container-parents').html(html);
                    $('.container-items .container-parents .item:first-child').trigger('click');
                }
            });
        },
        /**
         * Add savepath (filepath) to filepicker because Moodle don't do it.
         * @param savepath
         */
        init_filepicker: function (savepath) {
            console.log('init_filepicker');
            let interval = setInterval(function () {
                if (M.core_filepicker.instances !== 'undefined') {
                    for (let i in M.core_filepicker.instances) {
                        let currentelement = M.core_filepicker.instances[i].options.elementname;
                        let currentsavepath = savepath[currentelement];
                        console.log('savepath added => ', currentsavepath);
                        M.core_filepicker.instances[i].options.savepath = currentsavepath;
                    }
                    clearInterval(interval);
                }
            }, 1000);
        },
        hello_world: function (params) {
            console.log('hello world', params);
        }
    };
    return ludic;
});