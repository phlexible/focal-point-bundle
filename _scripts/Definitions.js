Ext.ns('Media.focalpoint');

Media.manager.FileDetailWindow.prototype.populateTabs =
    Media.manager.FileDetailWindow.prototype.populateTabs.createSequence(function() {
        if (Makeweb.config.user.Resources.indexOf('focalpoint') === -1) {
            return;
        }

        this.tabs.push({
            xtype: 'focalpoint-mainpanel',
            disabled: this.asset_type != Media.manager.IMAGE
        });
    });

Media.manager.FileDetailWindow.prototype.load =
    Media.manager.FileDetailWindow.prototype.load.createSequence(function() {
        var foundItem = null;

        this.getComponent(1).items.each(function(item) {
            if (item.title === Media.strings.Focalpoint.focal_point) {
                foundItem = item;
                return false;
            }
        }, this);

        if (!foundItem) {
            return;
        }

        if (this.asset_type != Media.manager.IMAGE) {
            foundItem.disable();
            //foundItem.getComponent(1).clear();
        }
        else {
            foundItem.enable();
            foundItem.getComponent(1).loadFile(this.file_id, this.file_version);
        }

        //if (!this.cache.pdf || 'ok' !== this.cache.pdf) {
        //        foundItem.ownerCt.setActiveTab(0);
        //        foundItem.disable();
        //} else {
        //        foundItem.enable();
        //}
    });
