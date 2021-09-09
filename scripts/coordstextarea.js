/*global Ext, GEP*/
GEP.CoordsTextarea = function(coords_id, settings) {
    "use strict";

    settings = settings || {};

    Ext.applyIf(settings, {
        fieldLabel: "Coding Exons Coordinates",
        name: "coding_exons",
        allowBlank: false,
        height: 80,
        emptyText: "Enter coordinates: 100-200, 300-400",
        tptitle: "Coding Exon Coordinates",
        tpdescription: [
            "Enter a comma-delimited list of coordinates corresponding to the ",
            "<u>translated</u> exons in your gene model (e.g. 100-200, 300-400).",
            "List the coordinates in the order in which the exons are translated.",
            "The complete set of coordinates should encompass the region that ",
            "starts with the <u>methionine</u> and ends just before the <u>stop codon</u>"
        ].join("\n"),
        vtype: "Coordinatestype",
        grow: true,
        growMin: 80,
        growMax: 300
    });

    settings.id = coords_id;
    settings.name = coords_id;

    settings.listeners = settings.listeners || {};

    function reformat_coords(new_value) {
        var formatted_value = new_value.trim();

        formatted_value = formatted_value.replace(/(\r\n|\n|\r)/gm, " ");
        return formatted_value.replace(/\s+/g, " ");
    }

    Ext.applyIf(settings.listeners, {
        blur: function() {
            GEP.EventManager.fire("hidehint");
        },
        focus: function() {
            GEP.EventManager.fire("showhint", {
                title: settings.tptitle,
                description: settings.tpdescription,
                pos: this.getPosition()
            });
        },
        change: function(field, new_value) {
            if (field.nooverlapwith) {
                Ext.getCmp(field.nooverlapwith).validate();
            }

            var formatted_value = reformat_coords(new_value);

            Ext.state.Manager.set(coords_id, formatted_value);

            Ext.getCmp(coords_id).setValue(formatted_value);
        }
    });

    var coords_textarea = new Ext.form.TextArea(settings);

    GEP.EventManager.subscribe("restore_formstate", function() {
        var stored_value = Ext.state.Manager.get(coords_id);

        if (! Ext.isEmpty(stored_value)) {
            var textarea = Ext.getCmp(coords_id);

            textarea.setValue(stored_value);

            textarea.setHeight(settings.height);
        }
    });

    return coords_textarea;

};
