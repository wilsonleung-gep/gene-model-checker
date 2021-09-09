/*global Ext, GEP*/
GEP.viewPort = function() {
    "use strict";

    var northpanel = GEP.northPanel();
    var southpanel = GEP.southPanel();
    var centerpanel = GEP.resultsPanel("resultspanel");
    var westpanel = GEP.modelConfigForm("configformpanel");

    var viewport = new Ext.Viewport({
        layout: "border",
        defaults: {
        },
        items: [
            northpanel,
            westpanel,
            centerpanel,
            southpanel
        ]
    });

    GEP.EventManager.fire("viewport_rendered");

    return viewport;
};
