/*global GEP, Ext */
GEP.SimpleCombo = function(combo_id, store, tpl, settings) {
    "use strict";

    settings = settings || {};

    Ext.applyIf(settings, {
        fieldLabel: "Region Missing",
        emptyText: "Select the type of partial gene",
        displayField: "partialinfo",
        id: "partial_type",
        mode: "local",
        typeAhead: true,
        forceSelection: true,
        editable: true,
        triggerAction: "all",
        tptitle: "hint title",
        tpdescription: "hint description"
    });

    settings.store = store;

    settings.id = combo_id;
    settings.name = combo_id;

    settings.tpl = tpl;
    settings.listeners = settings.listeners || {};

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
        select: function(combo, record) {
            var combo_value = record.data[settings.displayField];

            if (! Ext.isEmpty(combo_value)) {
                GEP.EventManager.fire(combo_id+":select", {value: combo_value});
                Ext.state.Manager.set(combo_id, combo_value);
            }
        }
    });

    var simple_combo = new Ext.form.ComboBox(settings);

    function hide_field() {
        simple_combo.allowBlank = true;
        simple_combo.container.up(".x-form-item").setDisplayed(false);
    }

    function show_field() {
        simple_combo.allowBlank = false;
        simple_combo.container.up(".x-form-item").setDisplayed(true);
    }

    GEP.EventManager.subscribe("restore_formstate", function() {
        var v = Ext.state.Manager.get(combo_id);

        if (! Ext.isEmpty(v)) {
            simple_combo.setValue(v);
            GEP.EventManager.fire(combo_id+":select", {value: v});
        }
    });

    GEP.EventManager.subscribe("viewport_rendered", function() {
        if (! Ext.isEmpty(settings.showevents)) {
            hide_field();
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

    return simple_combo;
};
