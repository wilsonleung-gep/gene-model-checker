/*global Ext, GEP*/
GEP.errorDialog = function(settings) {
    "use strict";

    settings = settings || {};

    Ext.applyIf(settings, {
        title: "Gene Model Checker Error",
        msg: "",
        buttons: Ext.MessageBox.OK,
        icon: Ext.MessageBox.ERROR,
        errorevent: "errorevent"
    });

    GEP.EventManager.subscribe(settings.errorevent, function(args) {
        args = args || {};

        settings.msg = (Ext.isEmpty(args.msg)) ?
            "Unknown error occurred.  Please contact the GEP staff and report the problem" :
            args.msg;

        Ext.MessageBox.show(settings);
    });

    return settings;
};
