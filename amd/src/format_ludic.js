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

define(['jquery', 'jqueryui', 'core/templates'], function ($, ui, templates) {
    let courseId = null;
    let userId = null;
    let editMode = null;
    let ludic = {

        /**
         * Always called in a format_ludic page
         * Initialize all required events.
         * @param {object} params
         */
        init: function (params) {
            // Defines some useful variables.
            ludic.courseId = params.courseid;
            ludic.userId = params.userid;
            ludic.editMode = params.editmode;

            // Add a background for the display of popup.
            $('body.format-ludic').prepend('<div id="ludic-background"></div>');

            // Initialize all required events.
            ludic.initEvents();

            // If we are in edit mode, show sections after loading the page.
            if (ludic.editMode) {
                ludic.displaySections();
            }

            // Click on last item clicked in order to keep navigation.
            ludic.clickOnTheLastItemClicked();
        },

        /**
         * Initialize all the general events to be monitored at startup in this function.
         */
        initEvents: function () {
            console.log('initEvents');

            // Save the element selector of the last clicked event.
            ludic.initSaveLastItemClickedEvents();

            /**
             * For each element with data-action attribute.
             *
             * If there is a controller and an action makes an ajax call to the controller defined in data-controller
             * with the action defined in data-action.
             * Then call a callback function defined in data-callback.
             *
             * Else if there is only an action call javascript function defined in data-action.
             */
            ludic.initLudicActionEvent();

            // If we are in edit mode, initialize related events.
            if (ludic.editMode) {
                ludic.initEditModeEvents();
            }
        },

        /**
         * For each element with data-action attribute.
         *
         * If there is a controller and an action makes an ajax call to the controller defined in data-controller
         * with the action defined in data-action.
         * Then call a callback function defined in data-callback.
         *
         * Else if there is only an action call javascript function defined in data-action with this on param and a callback.
         */
        initLudicActionEvent: function () {
            $('body.format-ludic').on('click', '[data-action]', function () {
                let item = $(this);
                let action = item.data('action');
                let callback = item.data('callback');
                let controller = item.data('controller');

                let params = {
                    item: item,
                    id: item.data('id') ? item.data('id') : null,
                    type: item.data('type') ? item.data('type') : null,
                    itemId: item.data('itemid') ? item.data('itemid') : null,
                    itemType: item.data('itemtype') ? item.data('itemtype') : null,
                    callback: callback
                };

                params.itemSelectorId = params.itemType && params.itemId ? '.item.' + params.itemType + '[data-id="' + params.itemId + '"]' : null;
                if (controller && action) {
                    console.log('click on ludic action callback => ', callback, ' controller => ', controller, ' action => ', action);
                    ludic.ajaxCall({
                        controller: controller,
                        action: action,
                        id: params.id,
                        callback: function (response) {
                            if (callback) {

                                // Ensures that response is an object.
                                let isHtml = /<\/?[a-z][\s\S]*>/i.test(response);
                                let responseParams = isHtml ? {html: response} : response;
                                responseParams = typeof params === "object" ? responseParams : JSON.parse(responseParams);

                                // Merge params.
                                params = Object.assign(params, responseParams);

                                // Call function defined in callback (string : name of function).
                                ludic.callFunction(callback, params);

                            }
                        }
                    });
                } else if (action) {
                    console.log('click on ludic action => ', action, ' with params => ', params);
                    ludic.callFunction(action, params);
                }
            });
        },

        /**
         * When you click on an item in .container-parents, call the getProperties() function on the controller of the same type.
         * Then display the return in .container-properties.
         */
        initItemGetPropertiesEvent: function () {
            $('body.format-ludic').on('click', '.container-items .container-parents .item', function () {
                console.log('click on item, getProperties');
                let item = $(this);
                let container = item.closest('.container-items');
                container.find('.item.selected').removeClass('selected');
                item.addClass('selected');
                let id = item.data('id');
                let type = item.data('type');
                if (!id || !type) {
                    return false;
                }
                let content = container.find('.container-properties .container-content');
                ludic.addLoading(content);
                ludic.ajaxCall({
                    id: id,
                    controller: type,
                    action: 'get_properties',
                    callback: function (html) {
                        if (!html) {
                            return false;
                        }
                        content.html(html);
                        ludic.initFilepickerComponent(container);
                    }
                });
            });
        },

        /**
         * Submit button in a selection popup.
         * Add a input hidden with selected value.
         * Update overview image with selected image.
         */
        initSubmitInSelectionPopupEvent: function () {
            $('body.format-ludic').on('click', '.selection-submit', function () {
                console.log('click on selection submit');
                // Find popup.
                let popup = $(this).closest('.format-ludic.ludic-popup');

                // Find selected item to get img and value.
                let selectedItem = popup.find('.item.selected');
                let selectedImg = selectedItem.find('.item-img-container').html();
                let selectedValue = selectedItem.data('id');

                // Find input and update value.
                let inputId = $(this).data('inputid');
                $(inputId).attr('value', selectedValue);

                // Find img in overview and update value.
                let overView = $(inputId + '-overview .overview-img-container');
                overView.html(selectedImg);

                // Data required for DOMNodeInserted event.
                let container = $('#ludic-main-container');
                let popupId = popup.attr('id');

                // Be sure to have only one active event.
                container.unbind('DOMNodeInserted');

                // Add event to auto select selected-item when reopening a popup.
                container.on('DOMNodeInserted', function (e) {
                    if (e.target.id && e.target.id === popupId) {
                        $('#' + popupId + ' .item[data-id="' + selectedValue + '"]').addClass('selected');
                    }
                });

                // Trigger click on close button to close popup.
                popup.find('.close-ludic-popup').click();
            });
        },

        /**
         * Revert form content by clicking in related item.
         *
         * @param itemSelectorId
         */
        revertForm(itemSelectorId) {
            $(itemSelectorId).click();
        },

        /**
         * Save form.
         *
         * @param {jquery} button
         * @returns {void}
         */
        saveForm: function (button) {
            let itemType = $(button).data('itemtype');
            let itemId = $(button).data('itemid');
            if (!itemType || !itemId) {
                return;
            }
            let form = $('#ludic-form-' + itemType + '-' + itemId);
            let serialize = form.serializeArray();
            let container = form.parent().find('.container-success');
            ludic.addLoading(container);
            ludic.ajaxCall({
                controller: itemType,
                id: itemId,
                data: serialize,
                dataType: 'json',
                action: 'validate_form',
                callback: function (json) {
                    console.log('form is validate', json);
                    let newClass = json.success ? 'success' : 'error';
                    let unwantedClass = json.success ? 'error' : 'success';
                    container.html(json.value);
                    container.removeClass(unwantedClass);
                    container.addClass(newClass);

                    // Refresh the updated elements - updateFunction ex : displaySections.
                    let updateFunction = 'display' + itemType.charAt(0).toUpperCase() + itemType.slice(1) + 's';
                    let params = {
                        callback: function () {
                            $('.item.' + itemType + '[data-id="' + itemId + '"]').addClass('selected');
                        }
                    };
                    ludic.callFunction(updateFunction, params);
                }
            });
        },

        /**
         * Show sub buttons
         *
         * @param {jquery} button
         */
        showSubButtons: function (button) {
            let identifier = $(button).data('identifier');
            $(button).removeClass('show-sub-buttons');
            $(button).addClass('hide-sub-buttons');
            let subButtons = $('.container-sub-buttons[for="' + identifier + '"]');
            subButtons.outerWidth($(button).outerWidth());
            subButtons.css('top', $(button).position().top + $(button).outerHeight());
            subButtons.css('left', $(button).position().left);
            subButtons.removeClass('hide');
            $(button).data('action', 'hideSubButtons');
        },

        /**
         * Hide sub buttons
         *
         * @param {jquery} button
         */
        hideSubButtons: function (button) {
            let identifier = $(button).data('identifier');
            $(button).removeClass('hide-sub-buttons');
            $(button).addClass('show-sub-buttons');
            let subButtons = $('.container-sub-buttons[for="' + identifier + '"]');
            subButtons.addClass('hide');
            $(button).data('action', 'showSubButtons');
        },

        /**
         * Redirect to the link in button.
         *
         * @param {jquery} button
         */
        redirectToFromButton: function (button) {
            let url = $(button).data('link');
            ludic.redirectTo(url);
        },

        deleteSection: function (button) {
            // Add confirmation before delete.
            console.log('confirmation');
            ludic.displayConfirmationPopup();
            // if (!confirm('vraiment ?')) {
            e.stopImmediatePropagation();
            // }
            return false;
        },

        /**
         * Revert form content by clicking in related item.
         * Submit form in ajax, validate and update if all is fine.
         * Show / Hide sub buttons.
         * Redirect for link button.
         */

        /**
         * Initialize all events specific to the edit mode to be monitored at startup in this function.
         */
        initEditModeEvents: function () {
            console.log('initEditmode');

            // Always init drag and drop popup events in edit mode.
            ludic.initDragAndDropEvents();

            // When you click on an item in .container-parents, call the getProperties() function on the controller of the same type.
            // Then display the return in .container-properties.
            ludic.initItemGetPropertiesEvent();

            // Submit button in a selection popup.
            // Add a input hidden with selected value.
            // Update overview image with selected image.
            ludic.initSubmitInSelectionPopupEvent();

        },

        /**
         * Close ludic popup.
         *
         * @param {jquery} button
         */
        closePopup: function (button) {
            let popup = $(button).closest('.format-ludic.ludic-popup');
            $('#ludic-background').hide();
            $(popup).remove();
        },


        /**
         * Save the last item clicked in sessionStorage.
         */
        initSaveLastItemClickedEvents: function () {
            // Use this variable as an indicator to know if we have already saved the last click.
            let lastTimeStamp = 0;

            // Save the selector of the last element clicked by the user in the .container-parents.
            $('#ludic-main-container .container-parents').on('click', '*', function (e) {

                // If it's not a real click, ignore it.
                if (!e.hasOwnProperty('originalEvent')) {
                    return;
                }

                // Compare the event timestamp with the last timestamp setted.
                let eventTimeStamp = e.timeStamp;
                if (lastTimeStamp !== eventTimeStamp) {
                    // If the last timestamp is different of the event timestamp, save the event timestamp as last timestamp.
                    // We can continue and save the last click.
                    lastTimeStamp = eventTimeStamp;
                } else {
                    // If the last timestamp is equal to the event timestamp, this means that we already save the last click, so return.
                    return;
                }

                // Defines some useful variables.
                let id = $(this).attr('id');
                let selector = '';

                // If there is not consistent id, save the DOM tree selector.
                if (!id || id.indexOf('yui_') === 0) {

                    // Return the list of classes to select it.
                    let getClassListSelector = function (classList) {
                        return classList ? "." + $.trim(classList).replace(/\s/gi, ".") : '';
                    };

                    // Set indicator to know if we are in a ludic course tree.
                    let inLudicContainer = true;

                    // For each parent we add its selector if it is part of the ludic course tree.
                    $(this).parents().each(function () {

                        // Check if we are in a ludic course tree.
                        if ($(this).attr('id') === 'ludic-main-container') {
                            // From now on we are no longer in a ludic course tree.
                            inLudicContainer = false;
                        }

                        // Not in ludic course tree, continue.
                        if (!inLudicContainer) {
                            return;
                        }

                        // If it exists, set the id selector.
                        let currentId = $(this).attr("id");
                        let selectorId = currentId && currentId.indexOf('yui_') === -1 ? '#' + currentId : '';

                        // Composes the selector of the current element.
                        let currentSelector = ' > ' + this.tagName + selectorId + getClassListSelector($(this).attr("class"));

                        // Add it to selector.
                        selector = currentSelector + selector;
                    });

                    // Remove first ' > '.
                    selector = selector.substring(3);

                } else {

                    // Element has id attribute, so save it.
                    let selector = '#' + id;

                }

                // Ensure that selector is not empty.
                if (selector) {

                    // Set last click selector in session storage.
                    sessionStorage.setItem('lastClick', selector);

                }

            });
        },

        /**
         *  Click on the last item clicked.
         */
        clickOnTheLastItemClicked: function () {

            // If an anchor is specified click on it.
            let anchor = window.location.hash.substr(1);
            let lastCLick = '#ludic-' + anchor;

            // Remove anchor for next refresh.
            history.replaceState(null, null, ' ');

            // Else retrieve selector of the last item clicked.
            if (!anchor) {
                lastCLick = sessionStorage.getItem('lastClick');
            }

            // If there is not item, there is nothing to do.
            if (!lastCLick) {
                return;
            }

            // Ensure that the item is in page before clicking on it.
            let interval = setInterval(function () {
                let lastItemClicked = $(lastCLick);
                if (lastItemClicked.length > 0) {
                    lastItemClicked.click();
                    clearInterval(interval);
                } else {
                    console.log('wait item ', lastCLick, ' is ready');
                }
            }, 500);
        },

        /**
         * Initialize all drag and drop specific events to monitor.
         */
        initDragAndDropEvents: function () {
            let body = $('body.format-ludic');

            // Save the selector id of the drag object.
            body.on('dragstart', '.ludic-drag', function (e) {
                console.log('dragstart');
                console.log(e.currentTarget.id);
                e.originalEvent.dataTransfer.setData('text/plain', e.currentTarget.id);
            });

            // Required to allow drop.
            body.on('dragover', '.ludic-drop', function (e) {
                console.log('dragover');
                e.preventDefault();
            });

            // Management of drop sections and course modules.
            body.on('drop', '.section.ludic-drop, .coursemodule.ludic-drop', function (e) {
                console.log('drop');
                console.log(e.currentTarget.id);

                // Get drag item data.
                let dragItem = $('#' + e.originalEvent.dataTransfer.getData('text/plain'));
                let dragId = dragItem.data('id');
                let dragType = dragItem.data('type');

                // Define the parent of the dragged object here to receive the html of the callback return.
                let dragParent = dragItem.parent();

                // Get drop item data.
                let dropItem = $('#' + e.currentTarget.id);
                let dropId = dropItem.data('id');
                let dropType = dropItem.data('type');

                if (dragItem.is(dropItem)) {
                    console.log('drop on same item, nothing to do');
                    return false;
                }

                // Define the action here.
                let action = false;
                if (dragType === 'section' && dropType === 'section') {
                    action = 'move_section_to';
                } else if (dragType === 'coursemodule' && dropType === 'section') {
                    action = 'move_to_section';
                } else if (dragType === 'coursemodule' && dropType === 'coursemodule') {
                    action = 'move_on_section';
                }

                // If an action has been found, make an ajax call to the section controller.
                // Then set the html on the parent of the dragged object.
                if (action) {
                    let callbackFunction = action === 'move_section_to' ? ludic.displaySections : ludic.displayCourseModulesHtml;
                    console.log('execute ', action, callbackFunction);

                    ludic.ajaxCall({
                        idtomove: dragId,
                        toid: dropId,
                        controller: 'section',
                        action: action,
                        callback: callbackFunction
                    });
                }
            });
        },

        /**
         * Initialize the filepicker component.
         * @param {object} container jQuery element - where are filepickers
         */
        initFilepickerComponent: function (container) {
            console.log('initFilepickerComponent');


            // Search filepicker in container, if there is none, there is nothing to do.
            let filepickers = container.find('.container-properties .ludic-filepicker-container');
            if (filepickers.length === 0) {
                return;
            }

            // Initialize each filepicker, with his options.
            filepickers.each(function () {
                console.log('init_filepicker');
                let options = $(this).data('options');
                M.form_filepicker.init(Y, options);

                // Hide options.
                $(this).removeAttr('data-options');
            });

        },

        /**
         * Display course modules html and init mod chooser component.
         *
         * @param html
         */
        displayCourseModulesHtml(html) {
            console.log('displayCourseModulesHtml');

            // Find container and add a loading.
            let container = $('.container-children.coursemodules');
            ludic.addLoading(container);

            // Search modchooser in container, if there is none, just show content and return.
            let modChooser = $(html).closest('.ludic-modchooser');
            if (modChooser.length === 0) {
                container.html(html);
                return;
            }

            // Adds the content in a non-visible way to give the modchooser time to initialize.
            $(html).each(function () {
                let hiddenDiv = $(this).addClass('hide-js');
                container.append(hiddenDiv);
            });

            // Ensure that moodle function which init mod chooser is ready before using it.
            let interval = setInterval(function () {
                if (typeof M.course.init_chooser === 'function') {

                    // Init mod chooser
                    M.course.init_chooser({
                        courseid: ludic.courseId,
                        closeButtonTitle: undefined
                    });

                    // Show content.
                    container.children().each(function () {
                        $(this).removeClass('hide-js');
                    });

                    // The job is done.
                    ludic.removeLoading(container);
                    clearInterval(interval);
                }
            }, 1000);
        },

        /**
         * Send an ajax request
         * @param {object} params
         */
        ajaxCall: function (params) {
            console.log('ajaxCall => ', params);

            let that = this;

            // Constant params.
            params.courseid = ludic.courseId;
            params.userid = ludic.userId;

            // Check optional params.
            let dataType = params.dataType ? params.dataType : 'html';
            let method = params.method ? params.method : 'GET';
            let url = params.url ? params.url : M.cfg.wwwroot + '/course/format/ludic/ajax/ajax.php';
            let async = params.async ? params.async : true;
            let callback = params.callback ? params.callback : null;
            let callbackError = params.error ? params.error : null;

            // Delete params to not send them in the request.
            delete params.dataType;
            delete params.method;
            delete params.url;
            delete params.async;
            delete params.callback;

            // Execute ajax call with good params.
            $.ajax({
                method: method,
                url: url,
                data: params,
                dataType: dataType,
                async: async,
                error: function (jqXHR, error, errorThrown) {
                    if (typeof callbackError === 'function') {
                        callbackError(jqXHR, error, errorThrown);
                    } else if ((jqXHR.responseText.length > 0) && (jqXHR.responseText.indexOf('pagelayout-login') !== -1)) {
                        that.redirectLogin();
                    } else {
                        that.displayErrorPopup();
                    }
                }
            }).done(function (response) {
                if ((response.length > 0) && (response.indexOf('pagelayout-login') !== -1)) {
                    that.redirectLogin();
                }

                if (typeof callback === 'function') {
                    callback(response);
                }
            });
        },

        /**
         * This function allows you to call another function dynamically with parameters.
         * @param {string} name
         * @param  params
         * @returns {mixed}
         */
        callFunction: function (name, params = {}) {
            console.log('callFunction => ', name, ' with params => ', params);

            // Define all possible parameters here.
            let html = params.html ? params.html : null;
            let callback = params.callback ? params.callback : null;
            let item = params.item ? params.item : null;
            let id = params.id ? params.id : null;
            let itemSelectorId = params.itemSelectorId ? params.itemSelectorId : null;
            let container = params.container ? params.container : null;

            let result = false;
            // Call the right function with the right parameters.
            switch (name) {
                case 'displayCourseModulesHtml' :
                    result = ludic.displayCourseModulesHtml(html);
                    break;
                case 'deleteSection' :
                    result = ludic.deleteSection(item);
                    break;
                case 'redirectToFromButton' :
                    result = ludic.redirectToFromButton(item);
                    break;
                case 'showSubButtons' :
                    result = ludic.showSubButtons(item);
                    break;
                case 'hideSubButtons' :
                    result = ludic.hideSubButtons(item);
                    break;
                case 'saveForm' :
                    result = ludic.saveForm(item);
                    break;
                case 'revertForm' :
                    result = ludic.revertForm(itemSelectorId);
                    break;
                case 'displaySections' :
                    result = ludic.displaySections(callback);
                    break;
                case 'displayPopup':
                    result = ludic.displayPopup(html);
                    break;
                default:
                    return result;
            }

            return result;
        },

        /**
         * Redirect to login page.
         */
        redirectLogin: function () {
            window.location.href = M.cfg.wwwroot + '/login/index.php';
        },

        /**
         * Redirect to url.
         * @param url
         */
        redirectTo: function (url) {
            window.location.href = url;
        },

        /**
         * Display popup.
         * @param {string} html - full html of the popup.
         */
        displayPopup: function (html) {
            let selectorId = '#' + $(html).attr('id');
            $(selectorId).remove();
            console.log(selectorId);

            $('#ludic-background').show();
            $('#ludic-main-container').prepend(html);
        },

        /**
         * Display confirmation popup.
         */
        displayConfirmationPopup: function () {

            let context = {
                popupid: 'confirmation-popup',
                title: M.util.get_string('confirmation-popup-title', 'format_ludic'),
                content: M.util.get_string('confirmation-popup-content', 'format_ludic'),
            };

            console.log('displayConfirmationPopup', context);

            templates.render('format_ludic/popup_confirm', context).then(
                function (html, js) {
                    ludic.displayPopup(html);
                }).fail(function (ex) {
                console.log('ConfirmationPopupError');
            });
        },

        /**
         * Display sections in ajax.
         * @param callback
         */
        displaySections: function (callback) {
            console.log('displaySections');

            let container = $('.container-items .container-parents');
            ludic.addLoading(container);
            ludic.ajaxCall({
                controller: 'section',
                action: 'get_course_sections',
                callback: function (html) {
                    container.html(html);
                    if (typeof callback === 'function') {
                        callback(html);
                    }
                }
            });
        },

        /**
         * Add a loading div
         * @param {object} parent jquery object
         */
        addLoading: function (parent) {
            parent.html('<div class="loading"></div>');
        },

        /**
         * Remove a loading div
         * @param {object} parent jquery object
         */
        removeLoading: function (parent) {
            parent.find('.loading').remove();
        },


    };
    return ludic;
});