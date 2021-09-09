/*global Ext, GEP*/
GEP.SequenceFilefield = function(filefield_id, settings) {
    "use strict";

    settings = settings || {};

    Ext.applyIf(settings, {
        xtype: "fileuploadfield",
        emptyText: "Select sequence file in fasta format",
        fieldLabel: "Sequence File",
        allowBlank: false,
        buttonCfg: {
            text: "Browse..."
        },
        regex: /(fasta|fa|txt)$/,
        regexText: "The sequence file must be a plain text files with extensions .txt, .fasta, or .fa"
    });

    settings.id = filefield_id;
    settings.name = filefield_id;

    var filefield = new Ext.ux.form.FileUploadField(settings);

    return filefield;
};
