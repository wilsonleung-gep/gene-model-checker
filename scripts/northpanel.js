/*global Ext, GEP*/
GEP.northPanel = function(settings) {
    "use strict";

    settings = settings || {};

    Ext.applyIf(settings, {
        region: "north",
        height: 32,
        el: "north"
    });

    return new Ext.BoxComponent(settings);
};
