/*global GEP, Ext */
GEP.SplitsequenceGrid = function(grid_id, settings) {
    "use strict";

    settings = settings || {};

    Ext.applyIf(settings, {
        browserlinkcolumn: 1
    });

    settings.id = grid_id;

    var store = new Ext.data.SimpleStore({
        fields: [
            {name: "exonnum"},
            {name: "phase"},
            {name: "startpos"},
            {name: "endpos"},
            {name: "orientation"},
            {name: "cdslength"},
            {name: "cdssequence"}
        ]
    });

    var expander = settings.expander || new Ext.ux.grid.RowExpander({
        tpl: new Ext.Template(
            '<div class="rowexpand">',
            "<b>CDS Translation:</b><br>",
            "<pre>{cdssequence}</pre>",
            "</div>")
    });

    var xhrdata = null;

    function renderLink(value, metaData, record) {
        var data = record.data;

        var db = xhrdata.assembly;
        var startpos = data.startpos;
        var endpos = data.endpos;

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

    var cm = settings.cm || new Ext.grid.ColumnModel({
        defaults: {
            sortable: true
        },
        columns: [
            expander,
            {id: "extractedexonlink", header: "View", width: 25, sortable: false, renderer: renderLink, dataIndex: "extractedexonlink"},
            {id: "exonnum",header: "Exon", width: 100, dataIndex: "exonnum"},
            {id: "phase",header: "Phase", width: 50, dataIndex: "phase"},
            {id: "startpos", header: "Start", width: 100, dataIndex: "startpos"},
            {id: "endpos", header: "End", width: 100, dataIndex: "endpos"},
            {id: "orientation", header: "Orientation", width: 75, dataIndex: "orientation"},
            {id: "cdslength", header: "Length", width: 100, dataIndex: "cdslength"}
        ]
    });

    var grid = GEP.expandGrid(grid_id, store, expander, cm, settings);

    GEP.EventManager.subscribe("genecheckresults", function(data) {
        xhrdata = data;
        store.loadData(data.extractedexons);
        grid.render(grid_id);
    });

    return grid;
};
