WalmartTemplateCategoryCategoriesSpecificBlockRenderer = Class.create(WalmartTemplateCategoryCategoriesSpecificRenderer, {

    // ---------------------------------------

    isRootBlock : false,
    isFlatBlock : false,

    childFilteredSpecifics: {
        all        : [],
        containers : [],
        rows       : []
    },

    // ---------------------------------------

    process: function()
    {
        if (this.specificHandler.isSpecificRendered(this.indexedXPath)) {
            return '';
        }

        if (!this.load()) {
            return '';
        }

        this.prepareFilteredChildSpecifics();

        this.renderParentSpecific();
        this.renderSelf();
        this.renderChildRequiredBlocks();

        this.tuneStyles();
        this.throwEventsToParent();
    },

    load: function($super)
    {
        var loadResult = $super();

        // First 2 blocks will be hidden
        if (this.specific.parent_specific_id == null ||
            '/' + this.specific.product_data_nick === this.specific.xpath)
        {
            this.isRootBlock = true;
            return loadResult;
        }

        var specifics = this.dictionaryHelper.getChildSpecifics(this.specific);

        // First 2 blocks will be hidden. So in fact, flat block will be used for nesting level > 3
        if (this.indexedXPath.split('/').length >= 5) {
            this.isFlatBlock = true;
        }

        return loadResult;
    },

    //########################################

    prepareFilteredChildSpecifics: function()
    {
        var self       = this,
            all        = [],
            containers = [],
            rows       = [];

        var specifics = self.dictionaryHelper.getChildSpecifics(this.specific);
        specifics.each(function(specific) {

            self.dictionaryHelper.isSpecificTypeContainer(specific) ? containers.push(specific)
                                                                    : rows.push(specific);
            all.push(specific);
        });

        self.childFilteredSpecifics = {
            all        : all,
            containers : containers,
            rows       : rows
        };
    },

    // ---------------------------------------

    renderParentSpecific: function()
    {
        if (this.specific.parent_specific_id == null) {
            return '';
        }

        if (!this.dictionaryHelper.isSpecificTypeContainer(this.parentSpecific)) {
            return '';
        }

        var parentBlockRenderer = new WalmartTemplateCategoryCategoriesSpecificBlockRenderer();
        parentBlockRenderer.setSpecificsHandler(this.specificHandler);
        parentBlockRenderer.setIndexedXpath(this.getParentIndexedXpath());

        parentBlockRenderer.process();
    },

    renderSelf: function()
    {
        if (this.specificHandler.isSpecificRendered(this.indexedXPath)) {
            return;
        }

        this.prepareDomStructure();

        // ---------------------------------------
        $(this.indexedXPath).observe('my-duplicate-is-rendered', this.onMyDuplicateRendered.bind(this));
        $(this.indexedXPath).observe('undeleteble-specific-appear', this.onWhenUndeletebleSpecificAppears.bind(this));
        // ---------------------------------------

        this.specificHandler.markSpecificAsRendered(this.indexedXPath);

        var selectedObj = {};
        selectedObj['mode'] = this.MODE_NONE;
        selectedObj['is_required'] = this.dictionaryHelper.isSpecificRequired(this.specific) ? 1 : 0;

        this.specificHandler.markSpecificAsSelected(this.indexedXPath, selectedObj);

        this.renderButtons();

        this.renderAddSpecificsRow();
        this.renderGrid();
    },

    renderChildRequiredBlocks: function()
    {
        var self = this;

        this.childFilteredSpecifics.containers.each(function(specific) {

            if (!self.dictionaryHelper.isSpecificRequired(specific)) {
                return true;
            }

            var renderer = new WalmartTemplateCategoryCategoriesSpecificBlockRenderer();
            renderer.setSpecificsHandler(self.specificHandler);
            renderer.setIndexedXpath(self.getChildIndexedPart(specific));

            renderer.process();
        });
    },

    // ---------------------------------------

    tuneStyles: function()
    {
        var block  = $(this.indexedXPath),
            header = $$('table[id="' + this.indexedXPath + '"]  div.block-title').first();

        var blockStyles = {
                'width'         : '100%',
                'padding'       : '15px 15px 15px 15px',
                'border-right'  : '1px solid #D6D6D6 !important',
                'border-bottom' : '1px solid #D6D6D6 !important',
                'border-left'   : '1px solid #D6D6D6 !important'
                },
            headerStyles = {
                'padding'       : '2px 0 2px 10px',
                'margin'        : '-15px -15px 15px -15px',
                'color'         : 'white',
                'background'    : '#6F8992',
                'border-bottom' : '1px solid #D6D6D6 !important',
                'font-weight'   : 'bold'
            };

        if (this.isRootBlock) {
            headerStyles['display'] = 'none';
            blockStyles = {};
            blockStyles['width'] = '100%';
        }

        // margin ParentGrid => Block
        var parentGrid = $(this.getParentIndexedXpath() + '_grid');
        if (parentGrid) {
            blockStyles['margin-top'] = '13px';
        }

        if (this.isFlatBlock) {

            headerStyles['color'] = 'black';
            headerStyles['background'] = '#FAFAFA';

            delete(blockStyles['border-right']);
            delete(blockStyles['border-left']);
            delete(blockStyles['border-bottom']);
            blockStyles['padding'] = '15px 15px 0 15px';
        }

        var compuledHeaderStyle = '';
        $H(headerStyles).each(function(el) { compuledHeaderStyle += el.key + ': ' + el.value + '; '; });
        header.setAttribute('style', compuledHeaderStyle);

        var compuledBlockStyle = '';
        $H(blockStyles).each(function(el) { compuledBlockStyle += el.key + ': ' + el.value + '; '; });
        block.setAttribute('style', compuledBlockStyle);
    },

    throwEventsToParent: function()
    {
        var parentXpath = this.getParentIndexedXpath();

        var myEvent = new CustomEvent('child-specific-rendered');
        parentXpath && $(parentXpath).dispatchEvent(myEvent);

        // my duplicate is already rendered
        this.touchMyNeighbors();
        // ---------------------------------------
    },

    //########################################

    prepareDomStructure: function()
    {
        var table = new Element('table', {
            'id':           this.indexedXPath,
            'class':        'form-list specifics-block',
            'cellspacing':  0,
            'cellpadding':  0
        });
        var td = table.appendChild(new Element('tr', {}))
                      .appendChild(new Element('td', {'colspan': 2}));

        var titleContainer = new Element('div', {
            'class': 'block-title'
        });
        td.appendChild(titleContainer).appendChild(new Element('span', {class: 'title'}))
                                      .insert(this.specific.title);

        this.getContainer().appendChild(table);
    },

    renderAddSpecificsRow: function()
    {
        var renderer = new WalmartTemplateCategoryCategoriesSpecificBlockGridAddSpecificRenderer();
        renderer.setSpecificsHandler(this.specificHandler);
        renderer.setIndexedXpath(this.indexedXPath);
        renderer.setBlockRenderer(this);

        renderer.childAllSpecifics  = this.childFilteredSpecifics.all;
        renderer.childRowsSpecifics = this.childFilteredSpecifics.rows;

        renderer.process();
    },

    renderGrid: function()
    {
        var renderer = new WalmartTemplateCategoryCategoriesSpecificBlockGridRenderer();
        renderer.setSpecificsHandler(this.specificHandler);
        renderer.setIndexedXpath(this.indexedXPath);
        renderer.childRowsSpecifics = this.childFilteredSpecifics.rows;

        renderer.process();
    },

    renderButtons: function()
    {
        var buttonsBlock = new Element('div', {
            'style': 'display: inline-block; float: right; margin-right: 5px;'
        });

        buttonsBlock.appendChild(this.getAddSpecificButton());

        var cloneButton = this.getCloneButton();
        if(cloneButton !== null) buttonsBlock.appendChild(cloneButton);

        var removeButton = this.getRemoveButton();
        if(removeButton !== null) buttonsBlock.appendChild(removeButton);

        var div = $$('table[id="' + this.indexedXPath + '"] div.block-title').first();
        div.appendChild(buttonsBlock);
    },

    getAddSpecificButton: function()
    {
        var button = new Element('a', {
            'id'          : this.indexedXPath + '_add_button',
            'indexedxpath': this.indexedXPath,
            'href'        : 'javascript:void(0);',
            'class'       : 'specific-add-button',
            'style'       : 'vertical-align: middle;',
            'title'       : M2ePro.translator.translate('Add Specific into current container')
        });

        return button;
    },

    //########################################

    removeAction: function($super, event)
    {
        var deleteResult = $super(event);
        this.throwEventsToParent();

        return deleteResult;
    },

    //########################################

    getContainer: function()
    {
        if (this.isRootBlock) {
            return $('specifics_container');
        }

        return $(this.getParentIndexedXpath());
    }

    // ---------------------------------------
});