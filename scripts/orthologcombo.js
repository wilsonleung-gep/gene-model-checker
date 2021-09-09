/*global GEP, Ext */
GEP.OrthologCombo = function(combo_id, settings) {
    "use strict";

    settings = settings || {};

    Ext.applyIf(settings, {
        fieldLabel: "Ortholog Name",
        displayField: "seqname",
        emptyText: "Enter the ortholog name",
        hideTrigger: true,
        triggerAction: "all",
        loadingText: "Searching...",
        minChars: 1,
        typeAhead: false,
        forceSelection: false,
        allowBlank: false,
        tooltip: {
            title: "Ortholog in D. melanogaster",
            description: "Specify the isoform of the orthologous gene you have annotated"
        },
        vtype: "Genetype"
    });

    settings.id = combo_id;
    settings.name = combo_id;

    settings.tpl = new Ext.XTemplate(
        '<tpl for="."><div class="x-combo-list-item">' +
        "<table><tbody><tr>" +
            '<td class="combo-label">{seqname}</td>' +
            '<td class="combo-details">FBid: {fbid}</td>' +
        "</tr></tbody></table></div></tpl>"
    );

    var gene_store = new Ext.data.JsonStore({
        fields: ["seqname", "fbid"],
        root: "data",
        proxy: new Ext.data.HttpProxy({
            method: "GET",
            url: "./services/genelookupservice.php"
        })
    });

    gene_store.on("beforeload", function(store) {
        var protein_name = store.baseParams.query.trim();

        gene_store.baseParams = {
            protein_name: protein_name
        };
    });

    settings.store = gene_store;

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

    var ortholog_combo = new Ext.form.ComboBox(settings);

    GEP.EventManager.subscribe("restore_formstate", function() {
        var v = Ext.state.Manager.get(combo_id);

        if (! Ext.isEmpty(v)) {
            ortholog_combo.setValue(v);
        }
    });

    return ortholog_combo;
};
