/*global Ext, GEP*/
GEP.southPanel = function(settings) {
    "use strict";

    settings = settings || {};

    Ext.applyIf(settings, {
        region: "south",
        height: 25,
        el: "south"
    });

    return new Ext.BoxComponent(settings);
};
