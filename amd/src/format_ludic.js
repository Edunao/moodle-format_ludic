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
    let sectionId = null;
    let userId = null;
    let editMode = null;
    let formChanged = false;
    let ludic = {

        /**
         * Always called in a format_ludic page
         * Initialize all required events.
         * @param {object} params
         */
        init: function (params) {
            console.log('js params ', params);
            // Defines some useful variables.
            ludic.courseId = params.courseid;
            ludic.sectionId = params.sectionid;
            ludic.userId = params.userid;
            ludic.editMode = params.editmode;
            ludic.editSkins = window.location.href.indexOf("ludic/edit_skins.php") > -1;

            // Add a background for the display of popup.
            $('body.format-ludic').prepend('<div id="ludic-background"></div>');

            // Initialize all required events.
            ludic.initEvents();

            // Init Avatar
            ludic.initAvatar('');

            // Edit skins mode
            if (ludic.editSkins) {
                ludic.displaySkinsList();
            }

            // If we are in edit mode.
            if (!ludic.editSkins && ludic.editMode) {
                // Show sections after loading the page.
                ludic.displaySections();

                // Click on last item clicked in order to keep navigation.
                ludic.clickOnLastItemClicked();
            }

            if(ludic.editMode){
                // Set correct height for left content
                ludic.setEditContentHeight();
            }
        },

        /**
         * Initialize all the general events to be monitored at startup in this function.
         */
        initEvents: function () {
            console.log('initEvents');

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

            // Click on element with data-action attribute.
            // Exclude not ludic element, because plugin like quiz use data-action
            $('body.format-ludic.path-course-view, body.format-ludic #ludic-header-bar, #page-course-format-ludic-edit_skins').on('click', '[data-action]', function (e) {
                console.log('test : ', $(this), $(e.target), $(this).is(e.target))

                if (!$(this).is(e.target)) {
                    if ($(e.target).hasClass('no-ludic-event') || $(e.target).parents('.no-ludic-event').length > 0
                        || ($(e.target).parents('.no-ludic-event').length > 0 && $(e.target).data('action') === undefined)
                    ) {
                        console.log('ignore event', e.target, $(e.target).parents('.no-ludic-event'), $(e.target).data('action'));
                        return false;
                    }
                }

                // Get data.
                let item = $(this);
                let action = item.data('action');
                let callback = item.data('callback');
                let controller = item.data('controller');

                // Params for ajax call or javascript function.
                // TODO get all data-* as params
                let params = {
                    item: item,
                    id: item.data('id') ? item.data('id') : null,
                    selectorId: item.data('selectorid') ? item.data('selectorid') : null,
                    type: item.data('type') ? item.data('type') : null,
                    itemId: item.data('itemid') ? item.data('itemid') : null,
                    itemType: item.data('itemtype') ? item.data('itemtype') : null,
                    callback: callback,
                };

                let skindata = {};
                if (action == 'avatar_toggle_item' || action == 'avatar_buy_item') {
                    // Avatar
                    skindata.slotname = item.data('slotname') ? item.data('slotname') : null;
                    skindata.itemname = item.data('itemname') ? item.data('itemname') : null;
                    skindata.sectionid = item.data('sectionid') ? item.data('sectionid') : null;
                    params.selectorId = item.data('sectionid') ? '.item[data-id="' + item.data('sectionid') + '"] .item-content-container' : null;
                    params.id = item.data('sectionid') ? item.data('sectionid') : null;
                }

                console.log('action', action, params);

                // Try to construct a selector id.
                let hasItemSelectorId = params.itemType && params.itemId;
                params.itemSelectorId = hasItemSelectorId ? '.item.' + params.itemType + '[data-id="' + params.itemId + '"]' : null;

                // Controller and action => Ajax call.
                if (controller && action) {
                    console.log('click on ludic action callback => ', callback, ' controller => ', controller, ' action => ', action);

                    ludic.ajaxCall({
                        controller: controller,
                        action: action,
                        id: params.id ? params.id : params.itemId,
                        skindata: skindata,
                        callback: function (response) {
                            if (callback) {

                                // Ensures that response is an object.
                                let isHtml = /<\/?[a-z][\s\S]*>/i.test(response);
                                let responseParams = isHtml ? {html: response} : response;
                                responseParams = typeof params === "object" ? responseParams : JSON.parse(responseParams);

                                // Merge params (initial params + ajax response).
                                params = Object.assign(params, responseParams);

                                // Call function defined in callback (string : name of function).
                                ludic.callFunction(callback, params);

                            }
                        }
                    });

                } else if (action) {
                    // Only an action => call javascript function dynamically.
                    ludic.callFunction(action, params);
                }
            });
        },

        /**
         * This function allows you to call another function dynamically with parameters.
         *
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
            let itemId = params.itemId ? params.itemId : null;
            let itemType = params.itemType ? params.itemType : null;
            let itemSelectorId = params.itemSelectorId ? params.itemSelectorId : null;

            let result = false;
            // Call the right function with the right parameters.
            switch (name) {
                case 'displayCourseModulesHtml':
                case 'displayPopup':
                case 'displaySections':
                case 'displaySkinTypesForm':
                case 'displaySkinTypesHtml':
                    result = eval('ludic.' + name + '(html, callback)');
                    break;
                case 'displayProperties':
                    result = ludic.displayProperties(itemId);
                    break;
                case 'saveForm':
                    result = ludic.saveForm(itemType, itemId);
                    break;
                case 'revertForm':
                    result = ludic.revertForm(itemSelectorId);
                    break;
                case 'updateAvatar':
                    result = ludic.updateAvatar(params.selectorId, id, html);
                    break;
                case 'displayCourseModules':
                    result = ludic.displayCourseModules(id, itemId, callback);
                    break;
                default:
                    result = eval('ludic.' + name + '(item)');
                    return result;
            }

            return result;
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
            let loading = params.loading ? params.loading : null;

            // You can add a loading before Ajax here.
            if (loading) {
                ludic.addLoading(loading);
            }

            // Delete params to not send them in the request.
            delete params.dataType;
            delete params.method;
            delete params.url;
            delete params.async;
            delete params.callback;
            delete params.loading;

            if (params.skindata !== undefined) {
                params = $.extend(params, params.skindata);
                delete params.skindata;
            }
            // delete params.data;

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
         * When you click on an item in .container-parents, call the defined propertiesaction function on the controller of the same type.
         * Then display the return in .container-properties.
         */
        initItemGetPropertiesEvent: function () {
            $('body.format-ludic').on('click', '.container-items .container-parents .item', function () {

                // If the form has changed, the user must confirm his choice to exit and display the properties of another item.
                let itemid = $(this).attr('id');
                if ($(this).closest('.format-ludic.ludic-popup').length === 0 && ludic.formChanged) {
                    ludic.displayChoicePopup('confirmation', 'displayProperties', {
                        title: M.util.get_string('confirmation-form-exit-title', 'format_ludic'),
                        content: M.util.get_string('confirmation-form-exit-content', 'format_ludic'),
                        itemid: itemid
                    });
                } else {
                    // Just display item properties.
                    ludic.displayProperties(itemid);
                }
            });
        },

        /**
         * Display properties of item.
         *
         * @param itemid
         * @returns {boolean}
         */
        displayProperties: function (itemid) {
            let item = $('#' + itemid);
            let container = item.closest('.container-items');
            container.find('.item.selected').removeClass('selected');
            container.find('.item.pre-selected').removeClass('pre-selected');
            item.addClass('selected');
            let id = item.data('id');
            let type = item.data('type');
            let action = item.data('propertiesaction');
            if (!id || !type || !action) {
                return false;
            }
            let content = container.find('.container-properties .container-content');
            ludic.addLoading(content);
            ludic.ajaxCall({
                id: id,
                controller: type,
                action: action,
                callback: function (html) {
                    content.html(html);
                    ludic.initFilepickerComponent(container);
                    ludic.setFormChanged(false);

                    if (item.closest('.format-ludic.ludic-popup').length === 0) {
                        ludic.closeClosestPopup();
                    }
                }
            });
        },

        initAvatar: function (selector) {
            $(selector + ' .skin-type-avatar .open-shop').on('click', function (e) {
                let shopid = $(this).attr('class').split(' open-shop-')[1];
                shopid = shopid.split(' ')[0];
                $('#avatar-shop-' + shopid).modal('show');
            });

            // Put avatar content in popup
            $(selector + ' .skin-type-avatar .open-shop').each(function(){
                let shopid = $(this).attr('class').split(' open-shop-')[1];
                shopid = shopid.split(' ')[0];

                $('#avatar-shop-' + shopid + ' .avatar-preview').html($('#skin-section-' + shopid + ' .skin-tile').prop('outerHTML'));
                $('#avatar-shop-' + shopid + ' .avatar-preview .open-shop').remove();
            });
        },

        updateAvatar: function (selectorId, sectionid, html) {
            $('#avatar-shop-' + sectionid).modal('hide');
            $(selectorId).each(function () {
                $(this).html(html);
            })
            // Remove header shop popup to avoid duplicate
            $('.header-sections-list ' + selectorId + ' .skin-extra-html').remove();

            this.initAvatar(selectorId);
            $('#avatar-shop-' + sectionid).modal('show')
        },

        /**
         * Update input value when clicking on confirm button.
         */
        initFormEvents: function () {
            let body = $('body.format-ludic');

            // Update input value when clicking on confirm button.
            body.on('click', '#selection-popup .item', function () {
                $('#selection-popup .confirmation-button.confirm').data('value', $(this).data('id'));
            });

            // Track when form has been changed.
            body.on('change', '.ludic-form :input', function (e) {
                if (!ludic.formChanged) {
                    ludic.setFormChanged(true);
                }
            });

            // Display description in select if exists.
            body.on('change', '.ludic-form-group[data-type="select"] select', function () {
                let descriptionContainer = $(this).parent().find('.select-description');
                if (!descriptionContainer) {
                    return;
                }
                descriptionContainer.find('.option-description').removeClass('visible');
                descriptionContainer.find('.option-description[for="' + $(this).val() + '"]').addClass('visible');
            });
        },

        /**
         * Events for selected class.
         */
        initSelectedEvents: function () {

            let body = $('body.format-ludic');

            // When item is added with selected class, click on it by default.
            body.on('DOMNodeInserted', function (e) {
                let selectedItem = $(e.target).find('.item.selected').addBack('.item.selected');
                if (selectedItem.length) {
                    console.log('click on selected item added', selectedItem);
                    selectedItem.click();
                }
            });

            // When you click on a child item, add a pre-selected class to his parent.
            body.on('click', '.container-children .item.child', function (e) {
                let parentId = $(this).data('parentid');
                let parent = $('.container-parents .item.parent[data-id="' + parentId + '"]');
                $('.container-parents .item').removeClass('pre-selected');
                parent.addClass('pre-selected');
            });
        },

        /**
         * Revert form content by clicking in related item.
         *
         * @param itemSelectorId
         */
        revertForm(itemSelectorId) {
            ludic.displayProperties($(itemSelectorId).attr('id'));
        },

        /**
         * Save form.
         *
         * @param {string} itemType
         * @param {int} itemId
         * @returns {void}
         */
        saveForm: function (itemType, itemId) {
            let formSelector = '#ludic-form-' + itemType + '-' + itemId;
            let form = $(formSelector);
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

                    // Add html with corresponding class : error or success.
                    let newClass = json.success ? 'success' : 'error';
                    let unwantedClass = json.success ? 'error' : 'success';
                    container.html(json.value);
                    container.removeClass(unwantedClass);
                    container.addClass(newClass);

                    // Form is up to date.
                    ludic.setFormChanged(false);

                    // Now refresh items.
                    let updateFunction = false;
                    let params = {};

                    // Define updateFunction according to the item type.
                    if (itemType === 'section') {

                        // Display sections.
                        updateFunction = 'displaySections';

                        // Then select current section, then display course modules.
                        params.callback = function () {
                            $('.item.' + itemType + '[data-id="' + itemId + '"]').addClass('selected');
                            ludic.displayCourseModules(itemId);
                        };

                    } else if (itemType === 'coursemodule') {

                        // Display course modules.
                        updateFunction = 'displayCourseModules';

                        // Then select current course module.
                        params.callback = function () {
                            $('.item.' + itemType + '[data-id="' + itemId + '"]').addClass('selected');
                        };

                        // Params required to display course modules.
                        params.id = $('.item.' + itemType + '[data-id="' + itemId + '"]').data('parentid');
                        params.itemId = itemId;
                    }

                    // Display items with callback function to select current item.
                    if (updateFunction) {
                        ludic.callFunction(updateFunction, params);
                    }
                }
            });
        },

        /**
         * Show sub buttons
         *
         * @param {jquery} button
         */
        showSubButtons: function (button) {

            // Update class and action.
            $(button).removeClass('show-sub-buttons');
            $(button).addClass('hide-sub-buttons');
            $(button).data('action', 'hideSubButtons');

            // Update the icon (if there is one).
            let icon = $(button).find('i');
            if (icon.length) {
                icon.addClass('fa-minus-square');
                icon.removeClass('fa-plus-square');
            }

            // Find sub buttons.
            let identifier = $(button).data('identifier');
            let subButtons = $('.container-sub-buttons[for="' + identifier + '"]');

            // Resize sub buttons.
            subButtons.outerWidth($(button).outerWidth());
            subButtons.css('top', $(button).position().top + $(button).outerHeight());

            // Show sub button.
            subButtons.removeClass('hide');
        },

        /**
         * Hide sub buttons
         *
         * @param {jquery} button
         */
        hideSubButtons: function (button) {

            // Update class and action.
            $(button).removeClass('hide-sub-buttons');
            $(button).addClass('show-sub-buttons');
            $(button).data('action', 'showSubButtons');

            // Update the icon (if there is one).
            let icon = $(button).find('i');
            if (icon.length) {
                icon.removeClass('fa-minus-square');
                icon.addClass('fa-plus-square');
            }

            // Find sub buttons.
            let identifier = $(button).data('identifier');
            let subButtons = $('.container-sub-buttons[for="' + identifier + '"]');

            // Hide sub buttons.
            subButtons.addClass('hide');
        },

        /**
         * Return data link
         *
         * @param {jquery} item
         */
        getDataLinkAndRedirectTo: function (item) {
            let url = $(item).data('link');
            ludic.redirectTo(url);
        },

        /**
         * Confirm and delete section.
         *
         * @param section
         */
        confirmAndDeleteSection: function (section) {
            // Add confirmation before delete.
            console.log('confirmation popup', section);
            let context = {
                itemid: $(section).data('itemid') ? $(section).data('itemid') : null,
                link: $(section).data('link') ? $(section).data('link') : null
            };
            ludic.displayChoicePopup('confirmation-popup', 'deleteSection', context);
        },

        /**
         * Delete section <-> skin relation, then delete section with moodle function.
         * @param section
         */
        deleteSection: function (section) {
            let id = $(section).data('itemid');
            let link = $(section).data('link');
            if (!id || !link) {
                return;
            }
            ludic.ajaxCall({
                controller: 'section',
                action: 'delete_section_skin_id',
                id: id,
                callback: function () {
                    ludic.redirectTo(link);
                }
            });
        },

        /**
         * Confirm and delete course module.
         *
         * @param coursemodule
         */
        confirmAndDeleteCourseModule: function (coursemodule) {
            // Add confirmation before delete.
            console.log('confirmation', coursemodule);
            let context = {
                itemid: $(coursemodule).data('itemid') ? $(coursemodule).data('itemid') : null,
                link: $(coursemodule).data('link') ? $(coursemodule).data('link') : null
            };
            ludic.displayChoicePopup('confirmation-popup', 'deleteCourseModule', context);
        },

        /**
         * Delete course module <-> skin relation, then delete course module with moodle function.
         *
         * @param coursemodule
         */
        deleteCourseModule: function (coursemodule) {
            let id = $(coursemodule).data('itemid');
            let link = $(coursemodule).data('link');
            if (!id || !link) {
                return;
            }
            ludic.ajaxCall({
                controller: 'coursemodule',
                action: 'delete_format_ludic_cm',
                id: id,
                callback: function () {
                    ludic.redirectTo(link);
                }
            });
        },

        confirmAndDeleteSkin: function(skin){
            // Add confirmation before delete.
            console.log('confirmation', skin.data());
            let context = {
                skinid: $(skin).data('itemid') ? $(skin).data('itemid') : null,
                courseid: $(skin).data('courseid') ? $(skin).data('courseid') : null
            };
            ludic.displayChoicePopup('confirmation-popup', 'deleteSkin', context);
        },

        deleteSkin: function(skin){
            let skinid = $(skin).data('itemid');
            let courseid = $(skin).data('courseid');
            ludic.ajaxCall({
                controller: 'skin',
                action: 'delete_skin',
                id: skinid,
                courseid: courseid,
                callback: function () {
                    // TODO : recharger la liste de skin du skintype
                }
            });
        },

        /**
         * Open a selection popup with items to select.
         * Confirm will execute updateInputAfterSelect function.
         *
         * @param selectionPopup
         * @returns {boolean}
         */
        selectAndUpdateInput: function (selectionPopup) {
            let inputSelectorId = '#' + $(selectionPopup).data('selectorid');
            let inputValue = $(inputSelectorId).val();
            let itemController = $(selectionPopup).data('itemcontroller') ? $(selectionPopup).data('itemcontroller') : null;
            let itemAction = $(selectionPopup).data('itemaction') ? $(selectionPopup).data('itemaction') : null;
            let title = $(selectionPopup).data('title') ? $(selectionPopup).data('title') : null;
            let itemId = $(selectionPopup).data('itemid') ? $(selectionPopup).data('itemid') : null;

            if (!itemAction || !itemController) {
                return false;
            }

            ludic.ajaxCall({
                controller: itemController,
                action: itemAction,
                selectedid: inputValue,
                itemid: itemId,
                callback: function (content) {
                    let context = {
                        selectorid: inputSelectorId,
                        title: title,
                        content: content,
                    };

                    ludic.displayChoicePopup('selection-popup', 'updateInputAfterSelect', context);
                }
            });
        },

        /**
         * In a selection popup after confirm : update value, image url if needed.
         */
        updateInputAfterSelect: function () {
            let popup = $('#selection-popup');
            let inputSelectorId = popup.attr('for');

            // Find selected image url.
            let selectedItem = popup.find('.container-parents .item.selected');

            // Update input value with selected value.
            let newValue = selectedItem.data('id');
            $(inputSelectorId).val(newValue);

            // Form has been changed.
            ludic.setFormChanged(true);

            // Update image url with selected image url.
            let newImgUrl = selectedItem.find('.item-img-container').attr('src');
            if (newImgUrl) {
                $(inputSelectorId + '-overview .overview-img-container').attr('src', newImgUrl);
            }

            // Close popup.
            ludic.closeClosestPopup(popup);
        },

        /**
         * Initialize all events specific to the edit mode to be monitored at startup in this function.
         */
        initEditModeEvents: function () {
            console.log('initEditmode');


            // When you click on an item in .container-parents, display properties in .container-properties.
            ludic.initItemGetPropertiesEvent();

            // Update input value when clicking on confirm button.
            ludic.initFormEvents();

            // Save the element selector of the last clicked event.
            ludic.initSaveLastItemClickedEvents();

            // When item is added with .selected class, click on it by default.
            ludic.initSelectedEvents();

            // Always init drag and drop popup events in edit mode.
            ludic.initDragAndDropEvents();
        },

        /**
         * Close ludic popup.
         */
        closeClosestPopup: function (item) {

            console.log('close popup ', item);
            if (item) {
                let popup = $(item).closest('.format-ludic.ludic-popup');
                $(popup).remove();
            } else {
                $('.format-ludic.ludic-popup').remove();
            }
            if ($('.format-ludic.ludic-popup').length === 0) {
                $('#ludic-background').hide();
            }
        },

        /**
         * Save the last item clicked in sessionStorage.
         */
        initSaveLastItemClickedEvents: function () {
            // Use this variable as an indicator to know if we have already saved the last click.
            let lastTimeStamp = 0;

            // Return the list of classes to select item.
            let getClassListSelector = function (classList) {
                // Remove situational class because they can be added in javascript.
                classList = classList !== undefined ? classList.replace(' is-not-visible', '') : '';
                classList = classList.replace(' selected', '');
                return classList ? "." + $.trim(classList).replace(/\s/gi, ".") : '';
            };

            // Save the selector of the last element clicked by the user in the .container-parents.
            $('#ludic-main-container .container-parents').on('click', '.item', function (e) {
                console.log('initSaveLastItemClickedEvents');

                // If it's not a real click, ignore it.
                if (!e.hasOwnProperty('originalEvent')) {
                    console.log('pas un vrai event');
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
                    console.log('doublon event');
                    return;
                }

                let tree = [this];
                $(this).parentsUntil('.course-content').each(function (id, item) {
                    tree[id + 1] = item;
                });

                let selector = '';
                // For each elements add it's selector.
                $(tree).each(function () {

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

                // Ensure that selector is not empty.
                if (selector) {
                    console.log('Save selector : ', selector);
                    // Set last click selector in session storage.
                    sessionStorage.setItem('lastClick', selector);
                }

            });
        },

        /**
         *  Click on the last item clicked.
         */
        clickOnLastItemClicked: function () {
            console.log('clickOnLastItemClicked', window.location);

            // If an anchor or section is specified click on it.
            let anchor = window.location.hash.substr(1);
            let section = ludic.getUrlParam('section', window.location.href);
            // Section parameter has priority over anchor.
            if (section !== null) {
                anchor = 'section-' + section;
            }
            let lastCLick = '#ludic-' + anchor;

            // Course Module is added.
            if (lastCLick === '#ludic-section-0') {
                anchor = 'childHasBeenAdded';
            }

            // Remove anchor for next refresh.
            history.replaceState(null, null, ' ');

            // Else retrieve selector of the last item clicked.
            if (!anchor || anchor === 'childHasBeenAdded') {
                lastCLick = sessionStorage.getItem('lastClick');
            }

            // If there is not item, select the first by default.
            if (!lastCLick) {
                lastCLick = '.container-parents > .item:first-child';
            }

            // Ensure that the item is in page before clicking on it.
            let interval = setInterval(function () {

                let children = $('.container-items .container-children .item');
                let lastItemClicked = $(lastCLick);

                // If lastClick is in a child, find his parent and click on it before.
                if (lastCLick.search('.container-children') && children.length === 0) {
                    let regex = '\.parent-id-([0-9]+)';
                    let parentId = lastCLick.match(regex) !== null ? lastCLick.match(regex)[1] : false;
                    if (parentId) {
                        $('.container-parents .item[data-id="' + parentId + '"]').click();
                    }
                }

                // If item is ready, click on it.
                if (lastItemClicked.length > 0) {

                    // Click on parent one time only.
                    if (lastItemClicked.hasClass('parent')) {
                        if (children.length === 0) {
                            lastItemClicked.click();
                        }
                    } else {
                        lastItemClicked.click();
                    }

                    // If a child is added, click on it after loading, then clear interval.
                    if (anchor === 'childHasBeenAdded') {
                        if (children.length > 0) {
                            children.last().click();
                            clearInterval(interval);
                        }
                    } else {
                        clearInterval(interval);
                    }

                }

            }, 500);
        },

        /**
         * TODO : fix all drag and drop using jquery ui
         * Initialize all drag and drop specific events to monitor.
         */
        initModuleDragDrop: function(){
            console.log('init drag and drop ? 5');
            $(".container-children.coursemodules .children-elements").sortable({
                items: ".ludic-drag",
                update: function (event, ui) {
                    console.log('change drag and drop ', ui, ui.item.attr('id'), $(".container-children.coursemodules .children-elements .ludic-drag").index(ui.item));

                    let cmid = ui.item.data('id');
                    //update_cm_order
                    ludic.ajaxCall({
                         cmid: cmid,
                         newindex: $(".container-children.coursemodules .children-elements .ludic-drag").index(ui.item),
                         controller: 'section',
                         action: 'update_cm_order',
                         callback: ludic.displayCourseModulesHtml
                     });
                }
            });


            // $(".container-children.coursemodules .children-elements .ludic-drag")


        },

        initSectionDragDrop: function(){
            $(".ludic-container.container-parents").sortable({
                items: ".item.section.ludic-drag",
                update: function (event, ui) {
                    console.log('change drag and drop ', ui, ui.item.attr('id'), $(".ludic-container.container-parents .item.section").index(ui.item));

                    let sectionid = ui.item.data('id');
                    //update_cm_order
                    ludic.ajaxCall({
                        sectionid: sectionid,
                        newindex: $(".ludic-container.container-parents .item.section").index(ui.item),
                        controller: 'section',
                        action: 'update_section_order',
                        callback: ludic.displaySections
                    });
                }
            });
        },

        initDragAndDropEvents: function () {
            let body = $('body.format-ludic');

            // console.log('init drag and drop ? 2');
            // $(".container-children.coursemodules .children-elements").sortable({
            //     items: ".ludic-drag",
            //     change: function (event, ui) {
            //         console.log('change drag and drop ', event);

            //     }
            // });




            // Save the selector id of the drag object.
            // body.on('dragstart', '.ludic-drag', function (e) {
            //     console.log('dragstart');
            //     console.log(e.currentTarget.id);
            //     e.originalEvent.dataTransfer.setData('text/plain', e.currentTarget.id);
            // });
            //
            // // Required to allow drop.
            // body.on('dragover', '.ludic-drop', function (e) {
            //     console.log('dragover');
            //     e.preventDefault();
            // });
            //
            //  // Management of drop sections and course modules.
            //  body.on('drop', '.section.ludic-drop, .coursemodule.ludic-drop', function (e) {
            //      console.log('drop');
            //      console.log(e.currentTarget.id);
            //
            //      // Get drag item data.
            //      let dragItem = $('#' + e.originalEvent.dataTransfer.getData('text/plain'));
            //      let dragId = dragItem.data('id');
            //      let dragType = dragItem.data('type');
            //
            //      // Get drop item data.
            //      let dropItem = $('#' + e.currentTarget.id);
            //      let dropId = dropItem.data('id');
            //      let dropType = dropItem.data('type');
            //
            //      if (dragItem.is(dropItem)) {
            //          console.log('drop on same item, nothing to do');
            //          return false;
            //      }
            //
            //      // Define the action here.
            //      let action = false;
            //      if (dragType === 'section' && dropType === 'section') {
            //          action = 'move_section_to';
            //      } else if (dragType === 'coursemodule' && dropType === 'section') {
            //          action = 'move_to_section';
            //      } else if (dragType === 'coursemodule' && dropType === 'coursemodule') {
            //          action = 'move_on_section';
            //      }
            //
            //      // If an action has been found, make an ajax call to the section controller.
            //      // Then set the html on the parent of the dragged object.
            //      if (action) {
            //          let callbackFunction = action === 'move_section_to' ? ludic.displaySections : ludic.displayCourseModulesHtml;
            //          console.log('execute ', action, callbackFunction);
            //
            //          ludic.ajaxCall({
            //              idtomove: dragId,
            //              toid: dropId,
            //              controller: 'section',
            //              action: action,
            //              callback: callbackFunction
            //          });
            //      }
            //  });
        },

        /**
         * Initialize the filepicker component.
         *
         * @param {object} container jQuery element - where are filepickers
         */
        initFilepickerComponent: function (container) {
            console.log('initFilepickerComponent', container);

            // Search filepicker in container, if there is none, there is nothing to do.
            let filepickers = container.find('.ludic-filepicker-container');
            if (filepickers.length === 0) {
                console.log('no file picker founded')
                return;
            }

            // Initialize each filepicker, with his options.
            filepickers.each(function () {
                console.log('init_filepicker', M.form_filepicker);
                let options = $(this).data('options');
                M.form_filepicker.init(Y, options);

                // Hide options.
                $(this).removeAttr('data-options');
            });

        },

        displayCourseModules: function (sectionId, courseModuleId, callback) {
            console.log(' sectionId ', sectionId);
            ludic.ajaxCall({
                'controller': 'section',
                'action': 'get_course_modules',
                'id': sectionId,
                'selectedid': courseModuleId,
                'callback': function (html) {
                    ludic.displayCourseModulesHtml(html, callback)
                }
            })

        },
        /**
         * Display course modules html and init mod chooser component.
         *
         * @param html
         * @param callback
         */
        displayCourseModulesHtml: function (html, callback) {
            console.log('displayCourseModulesHtml');

            // Course modules container.
            let container = $('.container-children.coursemodules .children-elements');

            // In student view just display course modules html.
            if (!ludic.editMode) {
                container.html(html);

                // Execute callback if exists.
                if (typeof callback === 'function') {
                    callback(container.html());
                }

                return;
            }

            ludic.initModuleDragDrop();

            // In edit view, we have to check some parameters (user is editing, wait moodle mod chooser is ready).
            // If the form has been updated, we ensure that the user has confirmed his choice to leave the edition
            // before showing him the course modules.
            let userConfirmation = setInterval(function () {

                // While ludic.formChanged is true, we are waiting user confirmation.
                if (ludic.formChanged) {
                    return;
                }

                // Find container and add a loading.
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

                        // Init mod chooser.
                        M.course.init_chooser({
                            courseid: ludic.courseId,
                            closeButtonTitle: undefined
                        });

                        // Show content.
                        container.children().each(function () {
                            $(this).removeClass('hide-js');
                        });

                        if (typeof callback === 'function') {
                            callback(container.html());
                        }

                        // The job is done.
                        ludic.removeLoading(container);
                        clearInterval(interval);
                    }
                }, 1000);

                clearInterval(userConfirmation);

            }, 100);
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
         * Reload current page.
         */
        reload: function () {
            window.location.reload()
        },

        /**
         * Display popup.
         * @param {string} html - full html of the popup.
         */
        displayPopup: function (html) {
            let selectorId = '#' + $(html).attr('id');
            console.log('display popup new ', selectorId);
            $(selectorId).remove();
            $('body').append(html);
            console.log(selectorId);
            $(selectorId).modal('show')
        },

        /**
         * Display Error popup.
         */
        displayErrorPopup: function () {
            console.log('displayErrorPopup');

            let context = {
                popupid: 'error',
                action: 'reload',
                title: M.util.get_string('error-popup-title', 'format_ludic'),
                content: M.util.get_string('error-popup-content', 'format_ludic'),
            };

            templates.render('format_ludic/popup_confirm', context).then(
                function (html, js) {
                    ludic.displayPopup(html);
                }).fail(function (ex) {
                    ludic.reload();
                }
            );
        },

        /**
         * Display a choice popup
         * In this popup a javascript function (defined in action) is executed after user confirmation.
         *
         * @param popupid
         * @param action
         * @param params
         */
        displayChoicePopup: function (popupid, action, params) {
            console.log('displayChoicePopup 2', popupid, action, params);

            let context = {
                popupid: popupid,
                action: action,
                link: params.link ? params.link : null,
                itemid: params.itemid ? params.itemid : null,
                selectorid: params.selectorid ? params.selectorid : null,
                value: params.value ? params.value : null,
                title: params.title ? params.title : M.util.get_string('confirmation-popup-title', 'format_ludic'),
                content: params.content ? params.content : M.util.get_string('confirmation-popup-content', 'format_ludic'),
            };

            templates.render('format_ludic/popup_confirm', context).then(
                function (html, js) {
                    ludic.displayPopup(html);
                }).fail(function (ex) {
                    ludic.displayErrorPopup();
                }
            );

        },

        displaySkinTypesHtml: function (html, callback) {
            console.log('displaySkinTypesHtml ', ' /// callback => ', callback);

            // Skins list
            let container = $('.edit-skins-view > .container-items > .container-parents .skin-types-list > .children-elements');
            if (html) {
                ludic.addLoading(container);
                container.html(html);

                if (typeof callback === 'function') {
                    callback(html);

                }
            }
        },

        displaySkinTypesForm: function (html, callback) {
            console.log('displaySkinTypesForm html => ', html, ' /// callback => ', callback);

            // Skins list
            let container = $('.edit-skins-view .container-properties .container-content');
            if (html) {
                ludic.addLoading(container);
                container.html(html);
                ludic.initFilepickerComponent(container);
                if (typeof callback === 'function') {
                    callback(html);
                }
            }
        },

        /**
         * Display sections.
         *
         * @param html (if null, get html in ajax)
         * @param callback (execute after displaying sections)
         */
        displaySections: function (html, callback) {
            console.log('displaySections html => ', html, ' /// callback => ', callback);

            // Display sections in this container.
            let container = $('.container-items .container-parents');

            // If we have html just display it.
            if (html) {

                ludic.addLoading(container);
                container.html(html);
                if (typeof callback === 'function') {
                    callback(html);
                }

            } else {

                // We don't have html, so get it and display it.
                ludic.ajaxCall({
                    controller: 'section',
                    action: 'get_course_sections',
                    loading: container,
                    callback: function (response) {
                        container.html(response);
                        if (typeof callback === 'function') {
                            callback(response);
                        }
                    }
                });

            }

            ludic.initSectionDragDrop();
        },

        displaySkinsList: function (html, callback) {
            console.log('displaySkinsList html => ', html, ' /// callback => ', callback);

            // Skins list
            let container = $('.edit-skins-view > .container-items > .container-parents > .children-elements');
            if (html) {

                ludic.addLoading(container);
                container.html(html);
                if (typeof callback === 'function') {
                    callback(html);
                }

            } else {
                // We don't have html, so get it and display it.
                ludic.ajaxCall({
                    controller: 'skin',
                    action: 'get_course_skins_list',
                    loading: container,
                    callback: function (response) {
                        container.html(response);
                        if (typeof callback === 'function') {
                            callback(response);
                        }
                    }
                });

            }

        },

        openModChooser: function (element) {
            let sectionid = element.data('section');
            $('#section-' + sectionid + '.ludic-modchooser .section-modchooser-link .section-modchooser-text').trigger('click');
        },

        /**
         * Add a loading div.
         * @param {object} parent jquery object
         */
        addLoading: function (parent) {
            parent.html('<div class="loading"></div>');
        },

        /**
         * Remove a loading div.
         * @param {object} parent jquery object
         */
        removeLoading: function (parent) {
            parent.find('.loading').remove();
        },

        /**
         * set Form Changed.
         * @param {boolean} value
         */
        setFormChanged: function (value) {
            ludic.formChanged = value;

            // When form is changed, disable buttons except Save and Revert.
            // When form is not changes, enable buttons.
            let buttons = $('.container-buttons button:not(.form-save):not(.form-revert)');
            buttons.each(function (id, button) {
                let hasDataAction = $(button).data('action') !== undefined;
                console.log('has data action ? ', hasDataAction);
                let disabled = ludic.formChanged;
                if (!hasDataAction) {
                    disabled = true;
                }
                $(button).attr('disabled', disabled);
            })
        },

        setEditContentHeight: function () {
            var bodyheight = $(window).height();
            var paneloffset = $('#ludic-main-container.editmode').offset();
            var panelheight = bodyheight - paneloffset.top - 32;
            $('#ludic-main-container.editmode').css('min-height', panelheight + 'px');
            $('#ludic-main-container.editmode > .ludic-container.container-items > .ludic-container.container-parents').css('max-height', panelheight);
        },

        /**
         * Return value of url param
         *
         * @param name
         * @param url
         * @return string | null | number
         */
        getUrlParam: function (name, url) {
            var results = new RegExp('[\?&]' + name + '=([^&#]*)').exec(url);
            if (results === null) {
                return null;
            }
            return decodeURI(results[1]) || 0;
        }
    };
    return ludic;
});