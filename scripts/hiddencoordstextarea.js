/*global Ext, GEP*/
GEP.HiddenCoordsTextarea = function(coords_id, settings) {
    "use strict";

    settings = settings || {};

    Ext.applyIf(settings, {
        fieldLabel: "Transcribed Exons Coordinates",
        allowBlank: true,
        tptitle: "Transcribed Exon Coordinates",
        tpdescription: [ "Enter a comma-delimited list of coordinates corresponding to the ",
            "<u>transcribed</u> exons in your gene model. (e.g. 100-200, 300-400).",
            "List the coordinates in the order in which the exons are transcribed.",
            "The coordinates should be listed in the order in which the exons ",
            "are transcribed. The complete set of coordinates should encompass ",
            "the region that starts with the <u>5' UTR</u> and ends with the <u>3' UTR</u>"
        ].join("\n")
    });

    settings.name = coords_id;

    var coords_textarea = new GEP.CoordsTextarea(coords_id, settings);

    function hide_field() {
        coords_textarea.allowBlank = true;
        coords_textarea.container.up(".x-form-item").setDisplayed(false);
    }

    function show_field() {
        coords_textarea.allowBlank = false;
        coords_textarea.container.up(".x-form-item").setDisplayed(true);
    }

    GEP.EventManager.subscribe("viewport_rendered", function() {
        if (! Ext.isEmpty(settings.showevents)) {
            hide_field();
        }
        hide_field();
    });

    function make_handler(show_event) {
        return function(args) {
            if (show_event.eventpredicate(args.value)) {
                show_field();
            } else {
                hide_field();
            }
        };
    }

    if (! Ext.isEmpty(settings.showevents)) {
        var show_events = settings.showevents;
        var current_event;

        var i;
        for (i=0; i < show_events.length; i++) {
            current_event = show_events[i];
            GEP.EventManager.subscribe(current_event.eventname,
                make_handler(current_event));
        }
    }

    return coords_textarea;

};
