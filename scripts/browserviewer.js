/*global Ext, GEP */
GEP.browserViewer = function(viewer_id, settings) {
    "use strict";

    settings = settings || {};

    var frame_id = viewer_id + "-iframe";

    Ext.applyIf(settings, {
        constrain: true,
        layout: "fit",
        title: "Gene Model Checker Viewer",
        closeAction: "hide",
        width: 850,
        height: 400,
        deferredRender: false,
        defaultType: "iframepanel",
        defaults: {
            loadMask: {
                hideOnReady: true,
                msg: "Loading External Data..."
            }
        },
        items: {
            frameConfig: { id: frame_id }
        }
    });

    settings.id = viewer_id;

    var browserviewer = null;

    GEP.EventManager.subscribe("updateiframe", function(args) {

        var href = args.href;

        if (! Ext.isEmpty(browserviewer)) {
            browserviewer.destroy();
        }

        settings.items.defaultSrc = href;
        browserviewer = new Ext.Window(settings);

        if (args.type === "browser") {
            browserviewer.setTitle("UCSC Genome Browser Feature Viewer");
        } else {
            browserviewer.setTitle("Global Alignment Viewer");
        }

        browserviewer.show();
    });

    return browserviewer;
};
