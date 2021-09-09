/*global GEP, Ext */
GEP.SimpleTextfield = function(textfield_id, settings) {
    "use strict";

    settings = settings || {};

    Ext.applyIf(settings, {
        tptitle: "hint title",
        tpdescription: "hint description",
        invalidText: "Invalid coordinates",
        allowBlank: false
    });

    settings.id = textfield_id;
    settings.name = textfield_id;

    function trySetCoordsValue(field, start, end) {
        if (start >= 0 && end >= 0) {
            field.setValue(start + "-" + end);
        }
    }

    function tryInferStopCodon(field, nooverlapCmp, strandCmp) {
        var codonSizeOffset = 2,
            cdsCoords = nooverlapCmp.getValue().trim(),
            coordSet, selectedStrandRadio, isReversed, start, end;

        if ((! nooverlapCmp.validate()) || (cdsCoords === "")) {
            return false;
        }

        try {
            coordSet = Ext.form.VTypes.build_coordset(cdsCoords);
            selectedStrandRadio = strandCmp.getValue();
            isReversed = (selectedStrandRadio.inputValue === "minus");

            coordSet.sort(function(a, b) {
                if (a.minpos === b.minpos) {
                    return (b.maxpos - a.maxpos);
                }

                return (a.minpos - b.minpos);
            });

            if (isReversed) {
                end = coordSet[0].minpos - 1;
                start = end - codonSizeOffset;

                trySetCoordsValue(field, end, start);
            } else {
                start = coordSet[coordSet.length - 1].maxpos + 1;
                end = start + codonSizeOffset;
                trySetCoordsValue(field, start, end);
            }

            return true;
        } catch(ignore) {
            return false;
        }
    }


    settings.listeners = settings.listeners || {};

    Ext.applyIf(settings.listeners, {
        blur: function() {
            GEP.EventManager.fire("hidehint");
        },
        focus: function(field) {
            var cdsField = Ext.getCmp(field.nooverlapwith),
                strandField = Ext.getCmp(field.strandfield);

            GEP.EventManager.fire("showhint", {
                title: settings.tptitle,
                description: settings.tpdescription,
                pos: this.getPosition()
            });

            if (field.nooverlapwith && field.strandfield) {
                if (this.getValue().trim() === "") {
                    tryInferStopCodon(field, cdsField, strandField);
                }
            }
        },
        change: function(textfield, field_value) {
            var clean_value = field_value.trim();
            textfield.setValue(clean_value);
            Ext.state.Manager.set(textfield_id, clean_value);
        }
    });


    var simple_field = new Ext.form.TextField(settings);

    function hide_field() {
        simple_field.allowBlank = true;
        simple_field.container.up(".x-form-item").setDisplayed(false);
    }

    function show_field() {
        simple_field.allowBlank = false;
        simple_field.container.up(".x-form-item").setDisplayed(true);
    }

    GEP.EventManager.subscribe("restore_formstate", function() {
        var v = Ext.state.Manager.get(textfield_id);

        if (! Ext.isEmpty(v)) {
            simple_field.setValue(v);
        }
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

    return simple_field;
};
