/*global GEP, Ext */
GEP.RadioGroupSelect = function(radiogroup_id, radiogroup_items, settings) {
    "use strict";

    settings = settings || {};

    Ext.applyIf(settings, {
        fieldLabel: "Annotated Untranslated Regions?",
        items: radiogroup_items,
        tptitle: "Untranslated Regions",
        tpdescription: "Specify whether you have annotated untranslated exons in addition to coding exons"
    });

    settings.id = radiogroup_id;
    settings.name = radiogroup_id;

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
        change: function(radiogroup, checked_radio) {
            var checked_value = checked_radio.getGroupValue();

            GEP.EventManager.fire(radiogroup_id+":change", {value: checked_value });
            Ext.state.Manager.set(radiogroup_id, checked_value);
        }
    });

    var radio_group = new Ext.form.RadioGroup(settings);

    GEP.EventManager.subscribe("restore_formstate", function() {
        var group_value = Ext.state.Manager.get(radiogroup_id);

        if (group_value === undefined) {
            return;
        }

        Ext.each(radiogroup_items, function(r) {
            Ext.getCmp(r.id).setValue( (r.inputValue === group_value) );
        });
    });

    return radio_group;
};
