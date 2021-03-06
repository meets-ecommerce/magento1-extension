EbayTemplateShippingExcludedLocationsHandler = Class.create(CommonHandler, {

    excludedLocationsPopup: null,

    selectedLocations: [],

    // ---------------------------------------

    setSelectedLocations: function(locations)
    {
        var self = EbayTemplateShippingExcludedLocationsHandlerObj;

        self.selectedLocations = locations;
        self.updateTemplateTitles();

        $('excluded_locations_hidden').value = Object.toJSON(locations);
    },

    // ---------------------------------------

    clear: function()
    {
        var self = EbayTemplateShippingExcludedLocationsHandlerObj;

        if (self.excludedLocationsPopup !== null) {
            self.excludedLocationsPopup.destroy();
            self.excludedLocationsPopup = null;
        }

        self.selectedLocations = [];
    },

    // ---------------------------------------

    showPopup: function()
    {
        var self = EbayTemplateShippingExcludedLocationsHandlerObj;

        if (self.excludedLocationsPopup == null) {
            self.excludedLocationsPopup = Dialog.info(null, {
                draggable: true,
                resizable: true,
                closable: true,
                className: "magento",
                windowClassName: "popup-window",
                title: M2ePro.translator.translate('Excluded Shipping Locations'),
                top: 0,
                width: 635,
                height: 445,
                zIndex: 100,
                recenterAuto: true,
                destroyOnClose: false,
                hideEffect: Element.hide,
                showEffect: Element.show
            });

            self.excludedLocationsPopup.getContent().insert(
                $('magento_block_ebay_template_shipping_form_data_exclude_locations_popup').show()
            );

            self.excludedLocationsPopup.options.destroyOnClose = false;

        } else {
            self.excludedLocationsPopup.showCenter(true, 0);
        }

        EbayTemplateShippingHandlerObj.isInternationalShippingModeNoInternational()
            ? $('excluded_locations_international').hide()
            : $('excluded_locations_international').show();

        self.autoHeightFix();

        //copy of array
        self.excludedLocationsPopup.selectedLocations = JSON.parse(JSON.stringify(self.selectedLocations));

        // ---------------------------------------
        var firstRegionContainer = $$('.excluded_location_region_title_container').shift();
        firstRegionContainer && firstRegionContainer.simulate('click');
        // ---------------------------------------

        self.renderSelected();
    },

    closePopup: function()
    {
        var self = EbayTemplateShippingExcludedLocationsHandlerObj;
        self.excludedLocationsPopup && self.excludedLocationsPopup.close();
    },

    resetPopup: function()
    {
        var self = EbayTemplateShippingExcludedLocationsHandlerObj;

        self.excludedLocationsPopup.selectedLocations = [];
        self.renderSelected();
    },

    savePopup: function()
    {
        var self = EbayTemplateShippingExcludedLocationsHandlerObj;

        //copy of array
        self.setSelectedLocations(JSON.parse(JSON.stringify(self.excludedLocationsPopup.selectedLocations)));

        self.updateTemplateTitles();
        self.closePopup();
    },

    // ---------------------------------------

    regionClick: function()
    {
        $$('.excluded_location_region_container').invoke('hide');
        $$('.excluded_location_region_title_container').invoke('removeClassName', 'selected_region');

        $('excluded_location_region_container_' + this.getAttribute('region')).show();
        this.addClassName('selected_region');
    },

    // ---------------------------------------

    regionOnchange: function()
    {
        var self = EbayTemplateShippingExcludedLocationsHandlerObj;
        var location  = {
            code   : this.value,
            title  : this.next().innerHTML,
            region : null,
            type   : this.getAttribute('location_type')
        };

        if (this.checked) {
            self.deleteExcludedLocation(location.code, 'region');
            self.addExcludedLocation(location);
        } else {
            self.deleteExcludedLocation(location.code, 'code');
            self.deleteExcludedLocation(location.code, 'region');
        }

        self.renderSelected();
    },

    // ---------------------------------------

    countryOnchange: function()
    {
        var my = this;

        var self = EbayTemplateShippingExcludedLocationsHandlerObj;
        var location  = {
            code   : this.value,
            title  : this.next().innerHTML,
            region : this.getAttribute('region'),
            type   : this.getAttribute('location_type')
        };

        if (this.checked) {
            self.addExcludedLocation(location);
        } else {
            self.deleteExcludedLocation(location.code, 'code');
        }

        // ---------------------------------------
        var myRegion = $('excluded_location_international_' + location.region),
            isAllLocationsOfRegionAreSelected = self.isAllCountriesOfRegionAreSelected(location.region);

        if (myRegion && isAllLocationsOfRegionAreSelected && !myRegion.checked) {

            self.deleteExcludedLocation(myRegion.value, 'region');
            self.addExcludedLocation({
                code   : myRegion.value,
                title  : myRegion.next().innerHTML,
                region : null,
                type   : myRegion.getAttribute('location_type')
            });

        } else if (myRegion && !isAllLocationsOfRegionAreSelected && myRegion.checked) {

            self.deleteExcludedLocation(myRegion.value, 'code');
            self.getCountriesByRegion(location.region)['locations'].each(function(childEl){

                if (childEl.value === location.code) {
                    return true;
                }

                self.addExcludedLocation({
                    code   : childEl.value,
                    title  : childEl.next().innerHTML,
                    region : location.region,
                    type   : location.type
                });
            });
        }
        // ---------------------------------------

        // ---------------------------------------
        /**
         * For example Russian Federation is located in both of Regions [Europa and Asia]
         */
        $$('.excluded_location[value="' + location.code + '"]').each(function(duplicatedLocation) {
            if (duplicatedLocation.region !== location.region && duplicatedLocation.checked !== my.checked) {
                duplicatedLocation.checked = my.checked;
                duplicatedLocation.simulate('change');
            }
        });
        // ---------------------------------------

        self.renderSelected();
    },

    //########################################

    renderSelected: function()
    {
        var self = EbayTemplateShippingExcludedLocationsHandlerObj;

        $$('.excluded_location', '.excluded_location_region').each(function(el){
            el.checked = 0;
        });

        self.excludedLocationsPopup.selectedLocations.each(function(location) {

            /**
             * For example Russian Federation is located in both of Regions [Europa and Asia]
             */
            $$('.excluded_location[value="' + location.code + '"]').each(function(childLocation) {
                childLocation.checked = 1;
            });

            if (location.region === null && location.type === 'international') {

                $$('.excluded_location_region[value="' + location.code + '"]').shift().checked = 1;

                self.getCountriesByRegion(location.code)['locations'].each(function(childLocation) {
                    $$('.excluded_location[value="' + childLocation.value + '"]').each(function(elem) {
                        elem.checked = 1;
                    });
                });
            }
        });

        self.updatesPopupTitles();
        self.updateRegionStatistics();
    },

    updatesPopupTitles: function()
    {
        var self  = EbayTemplateShippingExcludedLocationsHandlerObj;

        if (!self.excludedLocationsPopup.selectedLocations.length) {

            $('excluded_locations_reset_link').hide();
            $('excluded_locations_popup_titles').innerHTML = M2ePro.translator.translate('None');
            return;
        }

        $('excluded_locations_reset_link').show();
        $('excluded_locations_popup_titles').innerHTML = self.getTitles(self.excludedLocationsPopup.selectedLocations);
    },

    updateTemplateTitles: function()
    {
        var self = EbayTemplateShippingExcludedLocationsHandlerObj;

        if (!self.selectedLocations.length) {

            $('excluded_locations_titles').innerHTML = M2ePro.translator.translate('No Locations are currently excluded.');
            return;
        }

        $('excluded_locations_titles').innerHTML = self.getTitles(self.selectedLocations);
    },

    getTitles: function(selectedLocations)
    {
        var titles = [];

        selectedLocations.each(function(location) {

            if (location.region === null) {
                titles.unshift('<b>' + location.title + '</b>');
            } else {
                titles.push(location.title)
            }
        });

        return titles.join('; ');
    },

    updateRegionStatistics: function()
    {
        var self = EbayTemplateShippingExcludedLocationsHandlerObj;

        $$('.excluded_location_region_title_container').each(function(element) {

            var locations = self.getCountriesByRegion(element.getAttribute('region'));

            element.removeClassName('have_selected_locations');
            if (locations['selected'] > 0 && locations['selected'] < locations['total']) {
                element.addClassName('have_selected_locations');
                element.down('span').innerHTML = '(' + locations['selected'] + ' ' + M2ePro.translator.translate('selected') + ')';
            }
        });
    },

    // ---------------------------------------

    addExcludedLocation: function(location)
    {
        var self = EbayTemplateShippingExcludedLocationsHandlerObj;

        if (self.isExcludedLocationAlreadyAdded(location)) {
            return;
        }

        self.excludedLocationsPopup.selectedLocations.push(location);
    },

    isExcludedLocationAlreadyAdded: function(location)
    {
        var self = EbayTemplateShippingExcludedLocationsHandlerObj;

        var existedCodes = [];
        self.excludedLocationsPopup.selectedLocations.each(function(element) {

            if (element.region == null) {
                $$('.excluded_location[region="' +element.code+ '"]').each(function(childElement) {
                    existedCodes.push(childElement.value);
                });
            } else {
                existedCodes.push(element.code);
            }
        });

        return existedCodes.indexOf(location.code) !== -1;
    },

    deleteExcludedLocation: function(code, key)
    {
        var self = EbayTemplateShippingExcludedLocationsHandlerObj;

        for (var i = self.excludedLocationsPopup.selectedLocations.length - 1; i >= 0; i--) {
            if (key === 'region') {
                $$('.excluded_location[region="' +code+ '"]').each(function(childElement) {
                    self.deleteExcludedLocation(childElement.value, 'code');
                });
            } else {
                if (self.excludedLocationsPopup.selectedLocations[i][key] === code) {
                    self.excludedLocationsPopup.selectedLocations.splice(i, 1);
                }
            }
        }
    },

    // ---------------------------------------

    //########################################

    getCountriesByRegion: function(region)
    {
        if (region == null) {
            return false;
        }

        var locations         = [],
            selectedLocations = [];

        $$('div[id="excluded_location_region_container_' + region + '"] .excluded_location').each(function(el) {
            locations.push(el);
            el.checked && selectedLocations.push(el);
        });

        return {
            total             : locations.length,
            selected          : selectedLocations.length,
            locations         : locations,
            selected_locations: selectedLocations
        };
    },

    isAllCountriesOfRegionAreSelected: function(region)
    {
        var locations = EbayTemplateShippingExcludedLocationsHandlerObj.getCountriesByRegion(region);
        if (!locations) {
            return false;
        }

        return locations['total'] === locations['selected'];
    }

    // ---------------------------------------
});