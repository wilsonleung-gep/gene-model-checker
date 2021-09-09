/*global Ext, GEP*/
GEP.modelConfigForm = function(form_id, settings) {
    "use strict";

    settings = settings || {};

    Ext.applyIf(settings, {
        region: "west",
        frame: true,
        collapsible: true,
        width: 580,
        minSize: 300,
        maxSize: 650,
        labelWidth: 175,
        title: "Configure Gene Model",
        autoScroll: true,
        deferredRender: false,
        split: true,
        xtype: "form",
        fileUpload: true,
        monitorValid: true,
        defaults: {
            width: 550
        }
    });

    settings.id = form_id;

    var xhr_results = { "genecheckresults": false };

    function handle_successful_submit(form, action) {
        try {
            var genedata = action.result.data;
            GEP.EventManager.fire("genecheckresults", genedata);
        } catch(e) {
            GEP.EventManager.fire("errorevent", {msg: e});
        }
    }

    function handle_failed_submit(form, action) {
        try {
            if (Ext.isEmpty(action)) {
                throw "Gene Checker service is down.  Please try again later";
            }

            var message = "Unable to analyze the submitted gene model due to the following errors:<br>" +
                (action.result.message || action.result || action);

            GEP.EventManager.fire("errorevent", {msg: message});
        } catch(e) {
            GEP.EventManager.fire("errorevent", {msg: e});
        }
    }

    /**
     * Configure Form fields
     */
    var cds_coords_textarea = new GEP.CoordsTextarea("coding_exons", {
        fieldLabel: "Coding Exon Coordinates",
        nooverlapwith: "stop_codon"
    });

    var annotated_utr_radio = new GEP.RadioGroupSelect("utrselect", [
        {boxLabel: "Yes", id: "utryes", name: "annotated_utr", inputValue: "yes"},
        {boxLabel: "No", id: "utrno", name: "annotated_utr", inputValue: "no", checked: true}
    ], {labelSeparator: ""});

    var exon_coords_textarea = new GEP.HiddenCoordsTextarea("transcribed_exons", {
        fieldLabel: "Transcribed Exon Coordinates",
        showevents: [{
            eventname: "utrselect:change",
            eventpredicate: function(val) { return val === "yes"; }
        }]
    });

    var has_vcf_radio = new GEP.RadioGroupSelect("hasvcfselect", [
        {boxLabel: "Yes", id: "vcfyes", name: "has_vcf", inputValue: "yes"},
        {boxLabel: "No", id: "vcfno", name: "has_vcf", inputValue: "no", checked: true}
    ], {
        labelSeparator: "",
        fieldLabel: "Errors in Consensus Sequence?",
        tptitle: "Changes to consensus sequence",
        tpdescription: "Specify whether the project consensus sequence contains errors"
    });

    var vcffile_field = new GEP.VCFFilefield("vcffile", {
        showevents: [{
            eventname: "hasvcfselect:change",
            eventpredicate: function(val) { return val === "yes"; }
        }]
    });

    var orientation_radio = new GEP.RadioGroupSelect("geneorientation", [
        {boxLabel: "Plus", id: "orientplus", name: "orientation", inputValue: "plus", checked: true},
        {boxLabel: "Minus", id: "orientminus", name: "orientation", inputValue: "minus"}
    ], {
        fieldLabel: "Orientation of Gene Relative to Query Sequence",
        tptitle: "Gene Model Orientation",
        tpdescription: "Orientation of the gene model relative to the contig sequence"
    } );

    var completeness_radio = new GEP.RadioGroupSelect("modelcompleteness", [
        {boxLabel: "Complete", id: "modelcomplete", name: "model", inputValue: "complete", checked: true},
        {boxLabel: "Partial", id: "modelpartial", name: "model", inputValue: "partial"}
    ], {
        fieldLabel: "Completeness of Gene Model Translation"
    });

    var region_missing_combo = new GEP.SimpleCombo("partial_type",
        new Ext.data.SimpleStore({
            fields: [ "partialid", "partialinfo", "description"],
            data: [
                ["miss5", "Missing 5' end of translated region", "Skip check for start codon"],
                ["miss3", "Missing 3' end of translated region", "Skip check for stop codon"],
                ["miss53", "Missing 5' and 3' ends of translated region", "Skip checks for start and stop codons"]
            ]
        }),
        '<tpl for="."><div ext:qtip="{description}" class="x-combo-list-item">{partialinfo}</div></tpl>',
        {
            fieldLabel: "Region Missing",
            emptyText: "Select the type of partial gene",
            displayField: "partialinfo",
            tptitle: "Region Missing",
            tpdescription: "Specify the region of the gene that is missing in the partial gene model",
            showevents: [{
                eventname: "modelcompleteness:change",
                eventpredicate: function(val) {
                    return (val === "partial");
                }
            }]
        });

    var start_phase_combo = new GEP.SimpleCombo("phase_info",
        new Ext.data.SimpleStore({
            fields: ["phaseid", "phaseinfo", "description"],
            data: [
                [0, "Phase 0", "First"],
                [1, "Phase 1", "Second"],
                [2, "Phase 2", "Third"]]
        }),
        '<tpl for="."><div ext:qtip="{phaseinfo}: {description} base of the exon is the start of the first complete codon." class="x-combo-list-item">{phaseinfo}</div></tpl>',
        {
            fieldLabel: "Phase of First Exon",
            emptyText: "Select the phase of the first annotated exon",
            displayField: "phaseinfo",
            tptitle: "Phase of first annotated exon",
            tpdescription: "For genes missing the 5' end, specify the phase of the first annotated exon to translate from the correct reading frame",
            showevents: [{
                eventname: "partial_type:select",
                eventpredicate: function(val) {
                    var selected_radio = Ext.getCmp("modelcompleteness").getValue();
                    var modelcompleteness = selected_radio.getGroupValue();

                    return ((modelcompleteness === "partial") &&
                            (val !== "Missing 3' end of translated region"));
                }
            },{
                eventname: "modelcompleteness:change",
                eventpredicate: function(val) {
                    if (val === "complete") {
                        return false;
                    }

                    var partialType = Ext.getCmp("partial_type").getValue();
                    return (partialType !== "Missing 3' end of translated region");
                }
            }]
        }
    );

    var stop_codons_textfield = new GEP.SimpleTextfield("stop_codon", {
        fieldLabel: "Stop Codon Coordinates",
        tptitle: "Stop codon coordinates",
        tpdescription: "Specify the nucleotide positions for the stop codon",
        emptyText: "Enter coordinates: (i.e. 401-403)",
        showevents: [{
            eventname: "partial_type:select",
            eventpredicate: function(val) {

                var selected_radio = Ext.getCmp("modelcompleteness").getValue();
                var modelcompleteness = selected_radio.getGroupValue();

                return ( (modelcompleteness === "complete") ||
                         ((modelcompleteness === "partial") && (val === "Missing 5' end of translated region")));
            }
        },{
            eventname: "modelcompleteness:change",
            eventpredicate: function(val) {
                if (val === "complete") {
                    return true;
                }

                var partial_type = Ext.getCmp("partial_type").getValue();
                return (partial_type !== "Missing 3' end of translated region");
            }
        }],
        nooverlapwith: "coding_exons",
        strandfield: "geneorientation",
        vtype: "Stopcodontype"
    });

    var species_combo = new GEP.SpeciesCombo("species_combo");

    var assembly_combo = new GEP.AssemblyCombo("assembly_combo");

    var scaffold_combo = new GEP.ScaffoldCombo("scaffold_combo");

    var ortholog_combo = new GEP.OrthologCombo("ortholog_name", {
        fieldLabel: "Ortholog in D. melanogaster"
    });

    var gene_model_fieldset = {
        xtype: "fieldset",
        title: "Model Details",
        autoHeight: true,
        defaultType: "textfield",
        defaults: { width: 325 },
        items: [
            has_vcf_radio,
            vcffile_field,
            cds_coords_textarea,
            annotated_utr_radio,
            exon_coords_textarea,
            orientation_radio,
            completeness_radio,
            region_missing_combo,
            start_phase_combo,
            stop_codons_textfield
        ]
    };

    var project_fieldset = {
        xtype: "fieldset",
        title: "Project Details",
        autoHeight: true,
        defaultType: "textfield",
        defaults: { width: 325 },
        items: [
            species_combo,
            assembly_combo,
            scaffold_combo
        ]
    };

    var ortholog_fieldset = {
        xtype: "fieldset",
        title: "Ortholog Details",
        autoHeight: true,
        defaultType: "textfield",
        defaults: { width: 325 },
        items: [
            ortholog_combo
        ]
    };

    settings.items = [
        project_fieldset,
        ortholog_fieldset,
        gene_model_fieldset
    ];

    GEP.EventManager.subscribe("genecheckresults", function() {
        xhr_results.genecheckresults = true;
    });

    settings.buttons = [{
        text: "Verify Gene Model",
        formBind: true,
        handler: function() {
            xhr_results = { "genechecker": false };

            var genome_db = Ext.state.Manager.get("assembly_combo__db");

            Ext.getCmp(form_id).form.submit({
                url: GEP.Data.genechecker_service,
                params: { genome_db: genome_db },
                waitMsg: "Checking Gene Model...",
                success: handle_successful_submit,
                failure: handle_failed_submit
            });
        }
    }, {
        text: "Reset Form",
        handler: function() {
            Ext.getCmp(form_id).form.reset();
        }
    }];

    var form_panel = new Ext.FormPanel(settings);

    return form_panel;
};
