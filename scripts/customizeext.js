/*global Ext*/

(function () {
    "use strict";

    if (!Ext.grid.GridView.prototype.templates) {
        Ext.grid.GridView.prototype.templates = {};
    }

    Ext.grid.GridView.prototype.templates.cell = new Ext.Template(
        '<td class="x-grid3-col x-grid3-cell x-grid3-td-{id} x-selectable {css}" style="{style}" tabIndex="0" {cellAttr}>',
        '<div class="x-grid3-cell-inner x-grid3-col-{id}" {attr}>{value}</div>',
        "</td>");

    Ext.apply(Ext.util.Format, {
        seqHighlight: function(arr) {
            if ((arr === null) || (arr.length !== 3)) {
                return "N/A";
            }

            return '<span class="surroundbase">'+arr[0] + "</span>" +
                '<span class="highlightbase">'+arr[1] + "</span>" +
                '<span class="surroundbase">'+arr[2] + "</span>";
        },

        coordinates: function(coord) {
            if ((coord === null) || (coord.length !== 2) || coord[0] === 0) {
                return "N/A";
            }

            return coord[0] + "-" + coord[1];
        }
    });
}());
