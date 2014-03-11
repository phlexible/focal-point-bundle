Phlexible.focalpoint.FocalpointPanel = Ext.extend(Ext.Panel, {
    title: Phlexible.focalpoint.Strings.focal_point,
    strings: Phlexible.focalpoint.Strings,
    bodyStyle: 'width: 400px; height: 400px;',
    hideMode: 'offsets',

    dragging: false,
    loading: false,

    imageWidth: 0,
    imageHeight: 0,

    templateWidth: 0,
    templateHeight: 0,

    pointActive: false,
    pointX: null,
    pointY: null,

    pointWidth: 30,
    pointHeight: 30,

    boundaryWidth: 0,
    boundaryHeight: 0,

    initComponent: function() {
        //src: 'http://csb.stephan.brainbits-gmbh.local/cms/media/4dd26af3-82b4-41fa-921f-59827f000001/_mm_extra/1?ok',
      //      '<img src="' + Ext.BLANK_IMAGE_URL + '" width="200" height="200" style="width: 200px; height: 200px; border: 1px dotted black;" />';

        //this.html = 'bla';

        this.tbar = [{
            xtype: 'cycle',
            showText: true,
            //enableToggle: true,
            //pressed: false,
            items: [{
                text: this.strings.no_focal_point,
                iconCls: 'm-focalpoint-no-icon',
                focal: 0,
                checked: true
            },{
                text: this.strings.use_focal_point,
                iconCls: 'm-focalpoint-set-icon',
                focal: 1
            },{
                text: this.strings.never_crop,
                iconCls: 'm-focalpoint-never-icon',
                focal: -1
            }],
            changeHandler: function(btn, item) {
                this.pointActive = item.focal;

               //this.getTopToolbar().items.items[2].enable();

                if (item.focal > 0) {
                    this.pointEl.show();
                }
                else if (item.focal < 0) {
                    this.pointEl.hide();
                    this.boundaryEl.hide();
                }
                else {
                    if (!this.pointActive || (this.pointX === null && this.pointY === null)) {
                        this.setPointLeftTop(this.imageWidth / 2, this.imageHeight / 2);
                    }
                    this.pointEl.show();
                    this.boundaryEl.hide();
                }

                this.fireEvent('focalpointenabled', item.focal);
            },
            scope: this
        },'->',{
            text: this.strings.save,
            iconCls: 'm-focalpoint-save-icon',
            //disabled: true,
            handler: this.savePoint,
            scope: this
        }];

        Phlexible.focalpoint.FocalpointPanel.superclass.initComponent.call(this);

        this.on({
            render: {
                fn: this.initTracker,
                scope: this
            }
        });
    },

    initTracker: function() {
        Phlexible.console.log('initTracker');

        this.innerEl = this.body.createChild({
            cls: 'fp-inner',
            cn: [{
                cls: 'fp-boundary',
                style: 'left: 190px; top: 190px; width: 40px; height: 40px;'
            },{
                cls: 'fp-point'
            },{
                tag: 'a',
                cls: 'x-slider-focus',
                href: '#',
                tabIndex: '-1',
                hidefocus: 'on'
            }]
        });
        this.boundaryEl = this.innerEl.first();
        this.pointEl = this.boundaryEl.next();
        this.focusEl = this.pointEl.next();

        this.imageEl = this.body.createChild({
            cn: {
                tag: 'img'
                //src: this.getImageUrl(this.file_id, this.file_version)
                //width: 400,
                //height: 400
                //style: 'border: 0px dotted black'
            }
        }).first();
        this.imageEl.on('load', this.updateImageSize, this);
        this.imageEl.dom.src = this.getImageUrl(this.file_id, this.file_version);

        if (this.pointActive != 0) {
            this.pointEl.hide();
        }
        this.boundaryEl.hide();

        this.focusEl.swallowEvent("click", true);

        this.tracker = new Ext.dd.DragTracker({
            tolerance: 3,
            autoStart: 300,
            onStart: this.onDragStart.createDelegate(this),
            onDrag: this.onDrag.createDelegate(this),
            onEnd: this.onDragEnd.createDelegate(this)
        });
        this.tracker.initEl(this.pointEl);
    },

    getImageUrl: function(file_id, file_version) {
        return Phlexible.baseUrl + '/focalpoint/data/image?file_id=' + this.file_id + '&file_version=' + this.file_version + '&dc=' + (new Date().getTime());
    },

    loadFile: function(file_id, file_version) {
        this.loading = true;

        Phlexible.console.log('loadFile('+file_id+','+file_version+')');
        if (file_id == this.file_id && file_version == this.file_version) {
            return;
        }

        this.file_id = file_id;
        this.file_version = file_version | 1;
        /*
        this.pointActive = pointActive;
        if (pointActive) {
            this.pointX = pointX;
            this.pointY = pointY;
        }
        */

        if (!this.rendered) {
            return;
        }

        this.setImage(this.getImageUrl(file_id, file_version));
    },

    setImage: function(image) {
        Phlexible.console.log('setImage('+image+')');
        this.imageEl.dom.src = image;
    },

    updateImageSize: function() {
        Phlexible.console.log('updateImageSize(' + this.imageEl.dom.width + ',' + this.imageEl.dom.height + ')');

        this.setImageSize(this.imageEl.dom.width, this.imageEl.dom.height);

        this.updateBoundary();

        this.loadPoint();
    },

    setImageSize: function(width, height) {
        Phlexible.console.log('setImageSize('+width+','+height+')');

        this.imageWidth = width;
        this.imageHeight = height;
    },

    setTemplateWidthHeight: function(width, height) {
        this.templateWidth = width;
        this.templateHeight = height;

        this.boundaryEl.show();
        this.updateBoundary();
        this.setPointLeftTop(this.pointX, this.pointY);
    },

    setAllTemplatesWidthHeight: function(data) {
        var ratioX = 0,
            ratioY = 0,
            templateWidth = 0,
            templateHeight = 0,
            width = 0,
            height = 0,
            smallestWidth = 1000000,
            smallestHeight = 1000000;

        for (var i=0; i<data.length; i++) {
            templateWidth = data[i][0];
            templateHeight = data[i][1];
            ratioX = this.imageWidth / templateWidth;
            ratioY = this.imageHeight / templateHeight;
            if (ratioX < ratioY) {
                width = this.imageWidth;
                height = templateHeight * ratioX;
            }
            else if (ratioX > ratioY) {
                width = templateWidth * ratioY;
                height = this.imageHeight;
            }
            else {
                width = this.imageWidth;
                height = this.imageHeight;
            }

            if (width < smallestWidth) smallestWidth = width;
            if (height < smallestHeight) smallestHeight = height;
        }

        this.boundaryEl.show();

        this.boundaryWidth = smallestWidth;
        this.boundaryHeight = smallestHeight;
        this.boundaryMode = 'both';

        Phlexible.console.log('boundary: '+this.boundaryWidth+','+this.boundaryHeight);
        this.boundaryEl.setWidth(this.boundaryWidth);
        this.boundaryEl.setHeight(this.boundaryHeight);

        this.setPointLeftTop(this.pointX, this.pointY);
    },

    setPointLeftTop: function(left, top) {
        this.setPointLeft(left);
        this.setPointTop(top);

        //this.setBoundary(left, top);
    },

    setPointLeft: function(left) {
        var imageWidth = this.imageWidth;// - this.pointWidth;

        if (left < 0) {
            left = 0;
        }
        else if (left > imageWidth) {
            left = imageWidth;
        }

        this.pointX = left;

        left -= this.pointWidth / 2;
        this.pointEl.setLeft(left);

        if (this.boundaryMode === 'horizontal' || this.boundaryMode === 'both') {
            this.setBoundaryLeft(left - this.boundaryWidth / 2 + this.pointWidth / 2);
        }
    },

    setPointTop: function(top) {
        var imageHeight = this.imageHeight;// - this.pointHeight;

        if (top < 0) {
            top = 0;
        }
        else if (top > imageHeight) {
            top = imageHeight;
        }

        this.pointY = top;

        top -= this.pointHeight / 2;
        this.pointEl.setTop(top);

        if (this.boundaryMode === 'vertical' || this.boundaryMode === 'both') {
            this.setBoundaryTop(top - this.boundaryHeight / 2 + this.pointHeight / 2);
        }
    },

    updateBoundary: function() {
        var ratioX = this.imageWidth / this.templateWidth,
            ratioY = this.imageHeight / this.templateHeight;

        if (ratioX < ratioY) {
            this.boundaryWidth = this.imageWidth;
            this.boundaryHeight = this.templateHeight * (ratioX);

            this.boundaryMode = 'vertical';
            this.setBoundaryLeft(0);
        }
        else if  (ratioX > ratioY) {
            this.boundaryWidth = this.templateWidth * (ratioY);
            this.boundaryHeight = this.imageHeight;

            this.boundaryMode = 'horizontal';
            this.setBoundaryTop(0);
        }
        else {
            this.boundaryWidth = this.imageWidth;
            this.boundaryHeight = this.imageHeight;

            this.boundaryMode = 'none';
            this.setBoundaryLeftTop(0, 0);
        }

        Phlexible.console.log('boundary: '+this.boundaryWidth+','+this.boundaryHeight);
        this.boundaryEl.setWidth(this.boundaryWidth);
        this.boundaryEl.setHeight(this.boundaryHeight);
    },

    setBoundaryLeftTop: function(left, top) {
        this.setBoundaryLeft(left);
        this.setBoundaryTop(top);
    },

    setBoundaryLeft: function(left) {
        var imageWidth = this.imageWidth - this.boundaryWidth;

        if (left < 0) {
            left = 0;
        }
        else if (left > imageWidth) {
            left = imageWidth;
        }

        this.boundaryEl.setLeft(left);
    },

    setBoundaryTop: function(top) {
        var imageHeight = this.imageHeight - this.boundaryHeight;

        if (top < 0) {
            top = 0;
        }
        else if (top > imageHeight) {
            top = imageHeight;
        }

        this.boundaryEl.setTop(top);
    },

    onDragStart: function(e) {
        if (this.pointActive < 1) {
            return;
        }
        this.dragging = true;
    },

    onDragEnd: function(e) {
        if (this.pointActive < 1) {
            return;
        }
        this.dragging = false;
    },

    onDrag: function(e) {
        if (this.pointActive < 1) {
            return;
        }

        var pos = this.innerEl.translatePoints(this.tracker.getXY());

        this.setPointLeftTop(pos.left, pos.top);
    },

    loadPoint: function(e) {
        Ext.Ajax.request({
            url: Phlexible.baseUrl + '/focalpoint/data/get',
            params: {
                file_id: this.file_id,
                file_version: this.file_version,
                width: this.imageWidth,
                height: this.imageHeight
            },
            success: function(response) {
                var data = Ext.decode(response.responseText);

                if (data.success) {
                    this.pointActive = data.data.focalpoint_active;
                    this.pointX = data.data.focalpoint_x;
                    this.pointY = data.data.focalpoint_y;

                    Phlexible.console.log('LOAD');
                    Phlexible.console.log('pointActive: ' + this.pointActive);
                    Phlexible.console.log('pointX: ' + this.pointX);
                    Phlexible.console.log('pointY: ' + this.pointY);

                    if (!this.pointActive || (this.pointX === null && this.pointY === null)) {
                        this.setPointLeftTop(this.imageWidth / 2, this.imageHeight / 2);
                    }
                    else {
                        this.setPointLeftTop(this.pointX, this.pointY);
                    }

                }
                else {
                    Ext.MessageBox.alert('Failure', data.msg);
                }

                if (this.pointActive > 0) {
                    this.getTopToolbar().items.items[0].setActiveItem(1);
                }
                else if (this.pointActive < 0) {
                    this.getTopToolbar().items.items[0].setActiveItem(2);
                }
                else {
                    this.getTopToolbar().items.items[0].setActiveItem(0);
                }

                this.loading = false;
            },
            scope: this
        });
    },

    savePoint: function(e) {
        Phlexible.console.log('SAVE');
        Phlexible.console.log('pointActive: ' + this.pointActive);
        Phlexible.console.log('pointX: ' + this.pointX);
        Phlexible.console.log('pointY: ' + this.pointY);

        Ext.Ajax.request({
            url: Phlexible.baseUrl + '/focalpoint/data/set',
            params: {
                file_id: this.file_id,
                file_version: this.file_version,
                point_active: this.pointActive,
                point_x: this.pointX,
                point_y: this.pointY,
                width: this.imageWidth,
                height: this.imageHeight
            },
            success: function(response) {
                var data = Ext.decode(response.responseText);

                if (data.success) {
                    Phlexible.success(data.msg);
                }
                else {
                    Ext.MessageBox.alert('Failure', data.msg);
                }
            },
            scope: this
        });
    }
});

Ext.reg('focalpoint-focalpointpanel', Phlexible.focalpoint.FocalpointPanel);
