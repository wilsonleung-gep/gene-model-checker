/*global GEP, Ext */
GEP.expandGrid = function(grid_id, store, expander, cm, settings) {
    "use strict";

    settings = settings || {};

    Ext.applyIf(settings, {
        selectionmodel: {singleSelect: true},
        stripRows: true,
        height: 500,
        width: 600,
        viewConfig: {
            forceFit: true
        }
    });

    settings.plugins = expander;

    settings.tbar = [{
        text: "Expand All",
        iconCls: "icon-expand-all",
        handler: function() {
            var numRows = store.getCount();
            var i;
            for (i=0; i<numRows; i++) {
                expander.expandRow(i);
            }
        }
    },"-",
    {
        text: "Collapse All",
        iconCls: "icon-collapse-all",
        handler: function() {
            var numRows = store.getCount();

            var i;
            for (i=0; i<numRows; i++) {
                expander.collapseRow(i);
            }
        }
    }];

    settings.id = grid_id;
    settings.store = store;
    settings.sm = new Ext.grid.RowSelectionModel(settings.selectionmodel);
    settings.sm.lock();
    settings.cm = cm;

    var expandGrid = new Ext.grid.GridPanel(settings);

    expandGrid.on("cellclick", function(grid, rowIndex, colIndex, event) {
        if (colIndex === settings.browserlinkcolumn) {
            event.stopEvent();

            var updatedURL = event.getTarget().href;

            GEP.EventManager.fire("updateiframe", {
                href: updatedURL,
                type: "browser"
            });
        }
    });

    return expandGrid;
};
