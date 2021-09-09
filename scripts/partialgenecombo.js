/*global GEP, Ext */
GEP.PartialGeneCombo = function(combo_id, settings) {
    "use strict";

    settings = settings || {};

    Ext.applyIf(settings, {
        tpl: '<tpl for="."><div ext:qtip="{description}" class="x-combo-list-item">{partialinfo}</div></tpl>',
        fieldLabel: "FieldLabel",
        emptyText: "Select the type of partial gene",
        displayField: "partialinfo",
        mode: "local",
        typeAhead: true,
        forceSelection: true,
        editable: false,
        triggerAction: "all"
    });

    settings.id = combo_id;
    settings.name = combo_id;

    settings.store = new Ext.data.SimpleStore({
        fields: ["partialinfo", "description"],
        data: [
            ["Missing 5' end of translated region", "Skip check for start codon"],
            ["Missing 3' end of translated region", "Skip check for stop codon"],
            ["Missing 5' and 3' ends of translated region", "Skip checks for start and stop codons"]
        ]
    });

    var partial_gene_combo = new Ext.form.ComboBox(settings);

    return partial_gene_combo;
};
