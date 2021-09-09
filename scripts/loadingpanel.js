/*global GEP, Ext */
GEP.loadingPanel = function(panel_id, settings) {
    "use strict";

    settings = settings || {};

    Ext.applyIf(settings, {
        msg_suffix: "-msg",
        mask_suffix: "-mask",
        status_msg: "Initializing User Interface..."
    });

    var loading_panel = Ext.get(panel_id);
    var loading_mask = Ext.get(panel_id + settings.mask_suffix);
    var loading_msg = Ext.get(panel_id + settings.msg_suffix);

    loading_msg.dom.innerHTML = settings.status_msg;

    GEP.EventManager.subscribe("update_message", function(args) {
        loading_msg.dom.innerHTML = args.msg;
    });

    GEP.EventManager.subscribe("initgui_complete", function() {
        loading_panel.remove();
        loading_mask.fadeOut({remove: true});
    });

    return loading_panel;
};
