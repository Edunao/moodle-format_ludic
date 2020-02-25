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

// Javascript functions for ludic course format.

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
                ludic.get_parents('section');
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

            ludic.init_popup();

            // For each element with ludic-action class.
            // Makes an ajax call to the controller defined in data-controller with the action defined in data-action.
            // Then call a callback function defined in data-callback.
            body.on('click', '.ludic-action', function () {
                console.log('click on ludic action');
                let callback = $(this).data('callback');
                let controller = $(this).data('controller');
                let action = $(this).data('action');
                console.log('callback => ', callback);
                console.log('controller => ', controller);
                console.log('action => ', action);
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
                let container = $(this).closest('.container-items');
                let id = $(this).data('id');
                let type =  $(this).data('type');
                if (!id || !type) {
                    return false;
                }
                ludic.ajax_call({
                    id: id,
                    controller: type,
                    action: 'get_children',
                    callback: function (html) {
                        container.find('.container-children').html(html);
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

            ludic.init_drag_and_drop();

            // When you click on an item in .container-parents, call the get_properties() function on the controller of the same type.
            // Then display the return in .container-properties.
            body.on('click', '.container-items .container-parents .item', function () {
                console.log('click on item, get_properties');
                let item = $(this);
                console.log('ITEM', item);

                let container = item.closest('.container-items');
                container.find('.item.selected').removeClass('selected');
                item.addClass('selected');
                let id = item.data('id');
                let type =  item.data('type');
                if (!id || !type) {
                    return false;
                }
                ludic.ajax_call({
                    id: id,
                    controller: type,
                    action: 'get_properties',
                    callback: function (html) {

                        M.core_filepicker.instances = [];

                        container.find('.container-properties .container-content').html(html);

                        container.find('.container-properties .ludic-filepicker-container').each(function() {
                            let options =  $(this).data('options');
                            if (options) {
                                ludic.init_filepicker(options);
                            }
                        });

                        let modchooserconfig = {
                            courseid: ludic.courseid,
                            closeButtonTitle: undefined
                        };

                        let modchooser = container.find('.ludic-modchooser');
                        if (modchooser) {
                            console.log('AJOUT D\'UN MODCHOOSER !!!');
                            let interval = setInterval(function () {
                                if (typeof M.course.init_chooser === 'function') {
                                    M.course.init_chooser(modchooserconfig);
                                    modchooser.show();
                                    clearInterval(interval);
                                }
                            }, 1000);
                        }
                    }
                });
            });

            body.on('click', '.selection-submit', function () {
                console.log('click on selection submit');
                // Find popup.
                let popup = $(this).closest('.format-ludic.ludic-popup');

                // Find selected item to get img and value.
                let selecteditem = popup.find('.item.selected');
                let selectedimg  = selecteditem.find('.item-img-container').html();
                let selectedvalue = selecteditem.data('id');

                // Find input and update value.
                let inputid = $(this).data('inputid');
                $(inputid).attr('value', selectedvalue);

                // Find img in overview and update value.
                let overview = $(inputid + '-overview .overview-img-container');
                overview.html(selectedimg);

                // Data required for DOMNodeInserted event.
                let container = $('#ludic-main-container');
                let popupid = popup.attr('id');

                // Be sure to have only one active event
                container.unbind('DOMNodeInserted');

                // Add event to auto select selected-item when reopening a popup
                container.on('DOMNodeInserted', function (e) {
                    if (e.target.id && e.target.id === popupid) {
                        $('#' + popupid + ' .item[data-id="'+ selectedvalue +'"]').addClass('selected');
                    }
                });

                // Trigger click on close button to close popup.
                popup.find('.close-ludic-popup').click();
            });

            body.on('DOMNodeInserted', function (e) {
                if (e.target.className && e.target.className === 'ludic-container container-form') {
                    // Set form before update.
                    let form   = $(e.target.outerHTML);
                    let formid = form.children().attr('id');
                    sessionStorage.setItem(formid, form.html());
                }
            });

            body.on('click', '.form-revert', function () {
                // Find the item linked to the form and click on it to reset the form.
                let container = $(this).closest('.container-properties');
                let form = container.find('form.ludic-form');
                let itemid = '#' + form.data('type') + '-' + form.data('itemid');
                $(itemid).click();
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
                let popup = $(this).closest('.format-ludic.ludic-popup');
                $('#ludic-background').hide();
                $(popup).remove();
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
                case 'display_popup':
                    return ludic.display_popup(html);
                default:
                    return false;
            }
        },
        /**
         * Show popup.
         */
        display_popup: function(html) {
            let selectorid  = '#' + $(html).attr('id');
            $(selectorid).remove();
            console.log(selectorid);

            $('#ludic-background').show();
            $('#ludic-main-container').prepend(html);
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
                    let children = $('.container-items .container-parents .container-children')[0].outerHTML;
                    console.log('children', children);
                    $('.container-items .container-parents').html(html);
                    $('.container-items .container-parents').append(children);
                    $('.container-items .container-parents .item:first-child').trigger('click');
                }
            });
        },
        /**
         * Call the get_properties() function in ajax to the controller in parameter.
         * @param type
         */
        get_properties: function (type) {
            console.log('get_properties');
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
         * Initialise the filepicker component
         * @param options
         */
        init_filepicker: function (options) {
            console.log('init_filepicker', options);

            let interval = setInterval(function () {
                if (M.core_filepicker.instances !== 'undefined') {
                    M.form_filepicker.init(Y, options);

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