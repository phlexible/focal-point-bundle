Ext.ns('Phlexible.focalpoint');

Phlexible.mediamanager.FileDetailWindow.prototype.populateTabs =
    Phlexible.mediamanager.FileDetailWindow.prototype.populateTabs.createSequence(function() {
        if (Phlexible.User.isGranted('focalpoint')) {
            return;
        }

        this.tabs.push({
            xtype: 'focalpoint-mainpanel',
            disabled: this.asset_type != Phlexible.mediamanager.IMAGE
        });
    });

Phlexible.mediamanager.FileDetailWindow.prototype.load =
    Phlexible.mediamanager.FileDetailWindow.prototype.load.createSequence(function() {
        var foundItem = null;

        this.getComponent(1).items.each(function(item) {
            if (item.title === Phlexible.focalpoint.Strings.focal_point) {
                foundItem = item;
                return false;
            }
        }, this);

        if (!foundItem) {
            return;
        }

        if (this.asset_type != Phlexible.mediamanager.IMAGE) {
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
