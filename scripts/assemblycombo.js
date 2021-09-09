/*global GEP, Ext */
GEP.AssemblyCombo = function(combo_id, settings) {
    "use strict";

    settings = settings || {};

    Ext.applyIf(settings, {
        fieldLabel: "Genome Assembly",
        displayField: "assembly",
        emptyText: "Select a genome assembly",
        triggerAction: "all",
        loadingText: "Searching...",
        typeAhead: false,
        allowBlank: false,
        speciesComboId: "species_combo",
        tooltip: {
            title: "Genome Assembly",
            description: "Select the genome assembly for the proposed gene model"
        }
    });

    settings.id = combo_id;
    settings.name = combo_id;

    settings.tpl = new Ext.XTemplate(
        '<tpl for="."><div class="x-combo-list-item">' +
        "<table><tbody><tr>" +
            '<td class="combo-label">{assembly}</td>' +
            '<td class="combo-details"></td>' +
        "</tr></tbody></table></div></tpl>"
    );

    var assembly_store = new Ext.data.JsonStore({
        fields: ["db", "assembly"],
        root: "data",
        proxy: new Ext.data.HttpProxy({
            method: "GET",
            url: "./services/assemblylookupservice.php"
        })
    });

    assembly_store.on("beforeload", function() {
        assembly_store.baseParams = {
            species: Ext.getCmp(settings.speciesComboId).getValue()
        };
    });

    settings.store = assembly_store;

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
        change: function(combo, value) {
            var assembly = value.trim();

            Ext.state.Manager.set(combo_id, assembly);

            var record = assembly_store.query("assembly", assembly);
            var db = null;

            if (record.getCount() > 0) {
                db = record.first().get("db");
                Ext.state.Manager.set(combo_id + "__db", db);
            }

            GEP.EventManager.fire(combo_id+":select", { value: value, db: db });
        }
    });

    var assembly_combo = new Ext.form.ComboBox(settings);

    GEP.EventManager.subscribe(settings.speciesComboId + ":select", function() {
        assembly_combo.clearValue();
        Ext.state.Manager.clear(combo_id);

        assembly_store.load();
        GEP.EventManager.fire(combo_id+":reset");
    });

    GEP.EventManager.subscribe("restore_formstate", function() {
        var v = Ext.state.Manager.get(combo_id);

        if (! Ext.isEmpty(v)) {
            assembly_combo.setValue(v);
        }
    });

    return assembly_combo;
};
