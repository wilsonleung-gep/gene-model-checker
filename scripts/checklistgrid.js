/*global GEP, Ext */
GEP.ChecklistGrid = function(grid_id, settings) {
    "use strict";

    settings = settings || {};

    Ext.applyIf(settings, {
        browserlinkcolumn: 1,
        browseroffset: 30
    });

    var store = new Ext.data.SimpleStore({
        fields: [
            {name: "criteria"},
            {name: "status"},
            {name: "featurecoords"},
            {name: "seqsnippet"},
            {name: "message"}
        ]
    });

    var expander = settings.expander || new Ext.ux.grid.RowExpander({
        tpl: new Ext.Template(
            '<div class="rowexpand">',
            "<p><b>Feature Coordinates:</b> {featurecoords:coordinates}</p>",
            "<p><b>Surrounding Sequence:</b> {seqsnippet:seqHighlight}</p>",
            "</div>")
    });

    var xhrdata = null;

    function renderLink(value, metaData, record) {
        var data = record.data;

        if ((data.status === "Skip") || (Ext.isEmpty(xhrdata))) {
            return "";
        }

        var db = xhrdata.assembly;
        var startpos = data.featurecoords[0] - settings.browseroffset;
        var endpos = data.featurecoords[1] + settings.browseroffset;

        if (startpos <= 0) {
            startpos = 1;
        }

        var position = xhrdata.scaffoldname + ":" + startpos + "-" + endpos;

        var customText = xhrdata.downloadlinks.webroot + "/" +
                xhrdata.downloadlinks.gfflink;

        var updatedURL = GEP.Data.browsercgiroot + "?" +
            [
                "enableHighlightingDialog=0",
                "ruler=full",
                "pix=800",
                "db=" + db,
                "complement_" + db + "=" + xhrdata.orientation,
                "position=" + position,
                "hgt.customText=" + customText
            ].join("&");

        return '<a class="zoom-icon" href="' + encodeURI(updatedURL) + '"></a>';
    }

    function formatStatus(val) {
        return String.format('<div class="{0}status">{1}</div>', val, val);
    }

    var cm = settings.cm || new Ext.grid.ColumnModel({
        defaults: {
            sortable: true
        },
        columns: [
            expander,
            {id: "checklistlink", header: "View", sortable: false, hideable: false, width: 20, renderer: renderLink, dataIndex: "checklistlink"},
            {id: "criteria",header: "Criteria", dataIndex: "criteria"},
            {id: "status", header: "Status", width: 40, renderer: formatStatus, dataIndex: "status"},
            {id: "message", header: "Message", dataIndex: "message"}
        ]
    });

    var grid = GEP.expandGrid(grid_id, store, expander, cm, settings);

    GEP.EventManager.subscribe("genecheckresults", function(data) {
        xhrdata = data;
        store.loadData(data.checklist);
        grid.render(grid_id);
    });

    return grid;
};
