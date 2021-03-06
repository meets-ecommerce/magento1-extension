EbayListingAutoActionHandler = Class.create(ListingAutoActionHandler, {

    // ---------------------------------------

    getController: function()
    {
        return 'adminhtml_ebay_listing_autoAction';
    },

    // ---------------------------------------

    addingModeChange: function()
    {
        if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Ebay_Listing::ADDING_MODE_ADD_AND_ASSIGN_CATEGORY')) {
            $('confirm_button').hide();
            $('continue_button').show();
            $('breadcrumb_container').show();
        } else {
            $('continue_button').hide();
            $('breadcrumb_container').hide();
            $('confirm_button').show();
        }

        if (this.value != M2ePro.php.constant('Ess_M2ePro_Model_Listing::ADDING_MODE_NONE')) {
            $$('[id$="adding_add_not_visible_field"]')[0].show();
        } else {
            $$('[id$="adding_add_not_visible"]')[0].value = M2ePro.php.constant('Ess_M2ePro_Model_Listing::AUTO_ADDING_ADD_NOT_VISIBLE_YES');
            $$('[id$="adding_add_not_visible_field"]')[0].hide();
        }
    },

    // ---------------------------------------

    loadCategoryChooser: function(callback)
    {
        new Ajax.Request(M2ePro.url.get(ListingAutoActionHandlerObj.getController() + '/getCategoryChooserHtml'), {
            method: 'get',
            asynchronous: true,
            parameters: {
                auto_mode: $('auto_mode').value,
                group_id: this.internalData.id,
                // this parameter only for auto_mode=category
                magento_category_id: typeof categories_selected_items != 'undefined' ? categories_selected_items[0] : null
            },
            onSuccess: function(transport) {

                $('data_container').update(transport.responseText);

                if (typeof callback == 'function') {
                    callback();
                }
            }.bind(this)
        });
    },

    loadSpecific: function(callback)
    {
        var category = EbayListingCategoryChooserHandlerObj.getSelectedCategory(0);

        if (!category.mode) {
            return;
        }

        new Ajax.Request(M2ePro.url.get(ListingAutoActionHandlerObj.getController() + '/getCategorySpecificHtml'), {
            method: 'get',
            asynchronous: true,
            parameters: {
                auto_mode: this.internalData.auto_mode,
                category_mode: category.mode,
                category_value: category.value,
                group_id: this.internalData.id,
                // this parameter only for auto_mode=category
                magento_category_id: typeof categories_selected_items != 'undefined' ? categories_selected_items[0] : null
            },
            onSuccess: function(transport) {

                $('data_container').innerHTML = transport.responseText;
                try {
                    $('data_container').innerHTML.evalScripts();
                } catch (ignored) {

                }

                if (typeof callback == 'function') {
                    callback();
                }

            }.bind(this)
        });
    },

    // ---------------------------------------

    globalStepThree: function()
    {
        if (!EbayListingCategoryChooserHandlerObj.validate()) {
            return;
        }

        ListingAutoActionHandlerObj.collectData();

        var callback = function() {
            ListingAutoActionHandlerObj.highlightBreadcrumbStep(3);

            $('confirm_button').show();
            $('continue_button').hide();
        };

        ListingAutoActionHandlerObj.loadSpecific(callback);
    },

    websiteStepThree: function()
    {
        if (!EbayListingCategoryChooserHandlerObj.validate()) {
            return;
        }

        ListingAutoActionHandlerObj.collectData();

        var callback = function() {
            ListingAutoActionHandlerObj.highlightBreadcrumbStep(3);

            $('confirm_button').show();
            $('continue_button').hide();
        };

        ListingAutoActionHandlerObj.loadSpecific(callback);
    },

    categoryStepThree: function()
    {
        if (!EbayListingCategoryChooserHandlerObj.validate()) {
            return;
        }

        ListingAutoActionHandlerObj.collectData();

        var callback = function() {
            ListingAutoActionHandlerObj.highlightBreadcrumbStep(3);

            $('confirm_button').show();
            $('continue_button').hide();
        };

        ListingAutoActionHandlerObj.loadSpecific(callback);
    },

    // ---------------------------------------

    collectData: function()
    {
        if ($('auto_mode')) {
            switch (parseInt($('auto_mode').value)) {
                case M2ePro.php.constant('Ess_M2ePro_Model_Listing::AUTO_MODE_GLOBAL'):
                    ListingAutoActionHandlerObj.internalData = {
                        auto_mode: $('auto_mode').value,
                        auto_global_adding_mode: $('auto_global_adding_mode').value,
                        auto_global_adding_add_not_visible: $('auto_global_adding_add_not_visible').value,
                        auto_global_adding_template_category_id: null
                    };
                    break;

                case M2ePro.php.constant('Ess_M2ePro_Model_Listing::AUTO_MODE_WEBSITE'):
                    ListingAutoActionHandlerObj.internalData = {
                        auto_mode: $('auto_mode').value,
                        auto_website_adding_mode: $('auto_website_adding_mode').value,
                        auto_website_adding_add_not_visible: $('auto_website_adding_add_not_visible').value,
                        auto_website_adding_template_category_id: null,
                        auto_website_deleting_mode: $('auto_website_deleting_mode').value
                    };
                    break;

                case M2ePro.php.constant('Ess_M2ePro_Model_Listing::AUTO_MODE_CATEGORY'):
                    ListingAutoActionHandlerObj.internalData = {
                        id: $('group_id').value,
                        title: $('group_title').value,
                        auto_mode: $('auto_mode').value,
                        adding_mode: $('adding_mode').value,
                        adding_add_not_visible: $('adding_add_not_visible').value,
                        deleting_mode: $('deleting_mode').value,
                        categories: categories_selected_items
                    };
                    break;
            }
        }

        if ($('ebay_category_chooser')) {
            ListingAutoActionHandlerObj.internalData.template_category_data = EbayListingCategoryChooserHandlerObj.getInternalData();
        }

        if ($('category_specific_form')) {
            ListingAutoActionHandlerObj.internalData.template_category_specifics_data = EbayListingCategorySpecificHandlerObj.getInternalData();
        }
    }

    // ---------------------------------------
});
