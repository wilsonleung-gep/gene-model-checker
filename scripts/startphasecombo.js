/*global GEP, Ext */
GEP.StartPhaseCombo = function(combo_id, settings) {
    "use strict";

    settings = settings || {};

    Ext.applyIf(settings, {
        tpl: '<tpl for="."><div ext:qtip="{phaseinfo}: {description} base of the exon is the start of the first complete codon." class="x-combo-list-item">{phaseinfo}</div></tpl>',
        fieldLabel: "Phase",
        emptyText: "Select the phase of the first annotated CDS",
        displayField: "phaseinfo",
        mode: "local",
        typeAhead: true,
        triggerAction: "all",
        editable: false
    });

    settings.id = combo_id;
    settings.name = combo_id;

    settings.store = new Ext.data.SimpleStore({
        fields: ["phaseinfo", "description"],
        data: [
            ["Phase 0", "First"],
            ["Phase 1", "Second"],
            ["Phase 2", "Third"]
        ]
    });


    var start_phase_combo = new Ext.form.ComboBox(settings);

    return start_phase_combo;
};
