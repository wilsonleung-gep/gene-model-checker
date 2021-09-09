/*global Ext, GEP */
GEP.resultsPanel = function(panel_id, items, settings) {
    "use strict";

    settings = settings || {};

    Ext.applyIf(settings, {
        region: "center",
        deferredRender: false,
        activeTab: 0,
        defaults: {autoScroll: true}
    });

    settings.id = panel_id;
    settings.listeners = settings.listeners || {};

    var checklist = new GEP.ChecklistGrid("checklist", {title: "Checklist"});

    var dotplotpanel = {
        contentEl: "dotplotpanel",
        title: "Dot Plot"
    };

    var transcriptpanel = {
        contentEl: "transcriptpanel",
        title: "Transcript Sequence"
    };

    var peptidepanel = {
        contentEl: "peptidepanel",
        title: "Peptide Sequence"
    };

    var splitpanel = new GEP.SplitsequenceGrid("splitpanel", {title: "Extracted Coding Exons"});

    var downloadlinks = {
        contentEl: "downloadpanel",
        title: "Downloads"
    };

    settings.items = [
        checklist,
        dotplotpanel,
        transcriptpanel,
        peptidepanel,
        splitpanel,
        downloadlinks
    ];

    var results_panel = new Ext.TabPanel(settings);

    var seq_tpl = ['<table class="seqtable"><tr>',
        '<td><pre><span id="{0}" class="seqpos">{1}</span></pre></td>',
        '<td class="displayseq"><pre><span id="{2}">{3}</span></pre></td></tr></table></div>'
    ].join("");

    var download_tpl = [
        "<p>Right-click on the links below to save the files required for project submission:</p>",
        "<ul>",
        '<li><a href="{0}" download>GFF File</a></li>',
        '<li><a href="{1}" download>Transcript Sequence File</a></li>',
        '<li><a href="{2}" download>Peptide Sequence File</a></li>',
        "</ul>"
    ].join("");

    GEP.EventManager.subscribe("genecheckresults", function(data) {
        if (data.alignfilename === "") {
            Ext.fly(dotplotpanel.contentEl).update(String.format(
                "<p>Fail to retrieve ortholog sequence for comparison</p>"));
        } else {
            Ext.fly(dotplotpanel.contentEl).update(String.format(
                "<a id='alignlink' target='_blank' href='{0}'>View protein alignment</a><br>"+
                "<a id='dplink' target='_blank' href='{1}'>View dot plot in the Dot Plot Viewer</a><br>"+
                "<img id='dpimage' src='{2}'>",
                data.alignfilename,
                data.dpUrl,
                data.dpfilename ));
        }

        var transcriptpanel_id = transcriptpanel.contentEl;

        Ext.fly(transcriptpanel_id).update(String.format(seq_tpl,
            transcriptpanel_id + "-pos",
            data.transcriptlabels,
            transcriptpanel_id + "-seq",
            data.transcript ));

        var peptidepanel_id = peptidepanel.contentEl;

        Ext.fly(peptidepanel_id).update(String.format(seq_tpl,
            peptidepanel_id + "-pos",
            data.peptidelabels,
            peptidepanel_id + "-seq",
            data.peptide ));

        var download = data.downloadlinks;

        Ext.fly(downloadlinks.contentEl).update(String.format(
            download_tpl,
            download.gfflink,
            download.transcriptseqlink,
            download.peptideseqlink ));
    });

    return results_panel;
};
