/*global Ext, GEP*/
GEP.VCFFilefield = function(filefield_id, settings) {
    "use strict";

    settings = settings || {};

    Ext.applyIf(settings, {
        xtype: "fileuploadfield",
        emptyText: "Select file in VCF format",
        fieldLabel: "File with Changes to the Consensus Sequence",
        allowBlank: true,
        buttonCfg: {
            text: "Browse..."
        },
        regex: /(\.vcf\.txt|txt)$/,
        regexText: "The sequence file must be a plain text files with extensions .txt, or .vcf"
    });

    settings.id = filefield_id;
    settings.name = filefield_id;

    var filefield = new Ext.ux.form.FileUploadField(settings);

    function hide_field() {
        filefield.allowBlank = true;
        filefield.container.up(".x-form-item").setDisplayed(false);
    }

    function show_field() {
        filefield.allowBlank = false;
        filefield.container.up(".x-form-item").setDisplayed(true);
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

    return filefield;
};
