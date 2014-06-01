Phlexible.focalpoint.MainPanel = Ext.extend(Ext.Panel, {
    title: Phlexible.focalpoint.Strings.focal_point,
    strings: Phlexible.focalpoint.Strings,
    iconCls: 'p-focalpoint-focalpoint-icon',
    layout: 'border',
    hideMode: 'offsets',

    initComponent: function() {
        this.items = [{
            xtype: 'grid',
            region: 'east',
            width: 180,
            title: Phlexible.focalpoint.Strings.image_templates,
            collapsible: true,
            disabled: true,
            viewConfig: {
                deferEmptyText: false,
                emptyText: '_no templates with focal point',
                forceFit: true
            },
            store: new Ext.data.JsonStore({
                url: Phlexible.Router.generate('focalpoint_templates'),
                fields: ['id', 'title', 'type', 'width', 'height'],
                root: 'templates',
                id: 'id',
                autoLoad: true
            }),
            columns: [{
                dataIndex: 'title',
                header: this.strings.title,
                renderer: function(v, md, r) {
                    if (r.data.type == 'safe') {
                        v = Phlexible.inlineIcon('p-focalpoint-safe-icon') + ' ' + v;
                    }
                    else {
                        v = Phlexible.inlineIcon('p-mediatemplates-type_' + r.data.type + '-icon') + ' ' + v;
                    }

                    return v;
                }
            }],
            sm: new Ext.grid.RowSelectionModel({
                singleSelect: true,
                listeners: {
                    selectionchange: function(sm) {
                        var r = sm.getSelected();

                        if (!r) {
                            return;
                        }

                        if (r.data.type == 'safe') {
                            var records = this.getComponent(0).getStore().getRange();
                            var data = [];
                            for (var i=0; i<records.length; i++) {
                                if (records[i].data.type == 'safe') continue;
                                data.push([records[i].data.width, records[i].data.height]);
                            }
                            this.getComponent(1).setAllTemplatesWidthHeight(data);
                        }
                        else {
                            this.getComponent(1).setTemplateWidthHeight(r.data.width, r.data.height);
                        }
                    },
                    scope: this
                }
            })
        },{
            xtype: 'focalpoint-focalpointpanel',
            region: 'center',
            file_id: this.file_id,
            file_version: this.file_version,
            listeners: {
                focalpointenabled: function(state) {
                    this.getComponent(0).setDisabled(state < 1);

                    if (state > 0) {
                        this.getComponent(0).getSelectionModel().selectFirstRow();
                    }
                },
                scope: this
            }
        }];

        Phlexible.focalpoint.MainPanel.superclass.initComponent.call(this);
    }
});


Ext.reg('focalpoint-mainpanel', Phlexible.focalpoint.MainPanel);