/*global GEP, Ext */
GEP.ScaffoldCombo = function(combo_id, settings) {
    "use strict";

    settings = settings || {};

    Ext.applyIf(settings, {
        fieldLabel: "Scaffold Name",
        displayField: "chrom",
        emptyText: "Enter the scaffold name",
        hideTrigger: true,
        triggerAction: "all",
        loadingText: "Searching...",
        minChars: 1,
        typeAhead: false,
        forceSelection: false,
        allowBlank: false,
        speciesComboId: "species_combo",
        assemblyComboId: "assembly_combo",
        tooltip: {
            title: "Scaffold Name",
            description: "Specify the scaffold name in the selected assembly"
        }
    });

    var selected_db = null;

    settings.id = combo_id;
    settings.name = combo_id;

    settings.tpl = new Ext.XTemplate(
        '<tpl for="."><div class="x-combo-list-item">' +
        "<table><tbody><tr>" +
            '<td class="combo-label">{chrom}</td>' +
            '<td class="combo-details">Length: ' +
                '{[Ext.util.Format.number(values.size, "0,0")]}' +
            "</td>" +
        "</tr></tbody></table></div></tpl>"
    );

    var scaffold_store = new Ext.data.JsonStore({
        fields: ["chrom", "size"],
        root: "data",
        proxy: new Ext.data.HttpProxy({
            method: "GET",
            url: "./services/scaffoldlookupservice.php"
        })
    });

    scaffold_store.on("beforeload", function() {
        scaffold_store.baseParams = {
            scaffold: Ext.getCmp(combo_id).getValue(),
            db: selected_db
        };
    });

    settings.store = scaffold_store;

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
            Ext.state.Manager.set(combo_id, value.trim());
        }
    });

    var scaffold_combo = new Ext.form.ComboBox(settings);

    function reset_combo() {
        scaffold_combo.clearValue();
        Ext.state.Manager.clear(combo_id);
    }

    GEP.EventManager.subscribe(settings.assemblyComboId + ":select", function(obj) {
        reset_combo();
        selected_db = obj.db;
    });

    GEP.EventManager.subscribe(settings.assemblyComboId + ":reset", reset_combo);

    GEP.EventManager.subscribe("restore_formstate", function() {
        var v = Ext.state.Manager.get(combo_id);

        if (! Ext.isEmpty(v)) {
            scaffold_combo.setValue(v);
        }

        selected_db = Ext.state.Manager.get(settings.assemblyComboId + "__db");
    });

    return scaffold_combo;
};
