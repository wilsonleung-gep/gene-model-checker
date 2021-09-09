/*global GEP, Ext */
GEP.SpeciesCombo = function(combo_id, settings) {
    "use strict";

    settings = settings || {};

    Ext.applyIf(settings, {
        fieldLabel: "Species Name",
        displayField: "species",
        emptyText: "Select a species",
        allowBlank: false,
        editable: true,
        triggerAction: "all",
        typeAhead: false,
        tooltip: {
            title: "Species Name",
            description: "Select the species for the proposed gene model"
        },
        store: GEP.Data.speciesdbstore,
        mode: "local"
    });

    settings.id = combo_id;
    settings.name = combo_id;

    settings.tpl = new Ext.XTemplate(
        '<tpl for="."><div class="x-combo-list-item">{species}</div></tpl>'
    );

    settings.listeners = settings.listeners || {};

    Ext.applyIf(settings.listeners, {
        blur: function() {
            GEP.EventManager.fire("hidehint");
        },
        focus: function() {
            var tooltip_cfg = settings.tooltip;

            GEP.EventManager.fire("showhint", {
                title: tooltip_cfg.title,
                description: tooltip_cfg.description,
                pos: this.getPosition()
            });
        },
        select: function(combo, record) {
            var combo_value = record.data[settings.displayField];

            if (! Ext.isEmpty(combo_value)) {
                GEP.EventManager.fire(combo_id+":select", { value: combo_value });

                Ext.state.Manager.set(combo_id, combo_value);
            }
        }
    });

    var species_combo = new Ext.form.ComboBox(settings);

    GEP.EventManager.subscribe("restore_formstate", function() {
        var v = Ext.state.Manager.get(combo_id);

        if (! Ext.isEmpty(v)) {
            species_combo.setValue(v);
        }
    });

    return species_combo;
};
