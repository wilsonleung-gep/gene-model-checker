/*global GEP, Ext */
GEP.hintPanel = function(panel_id, settings) {
    "use strict";

    settings = settings || {};

    Ext.applyIf(settings, {
        offsetX: 400,
        offsetY: 0,
        panelcfg: {
            html: "tip content",
            title: "",
            closable: true,
            draggable: true,
            width: 350
        }
    });


    settings.panelcfg.target = panel_id;

    var hintpanel = new Ext.Tip(settings.panelcfg);

    function update_panel_content(args) {
        hintpanel.setTitle(args.title);
        hintpanel.update(args.description);
        hintpanel.setPosition(args.pos[0] + settings.offsetX, args.pos[1] + settings.offsetY);
    }

    var task = new Ext.util.DelayedTask(function(){
        hintpanel.el.fadeIn();
    });


    GEP.EventManager.subscribe("hidehint", function() {
        hintpanel.hide();
    });

    GEP.EventManager.subscribe("showhint", function(args) {
        update_panel_content(args);
        task.delay(200);
    });


    hintpanel.render(Ext.getBody());

    return hintpanel;
};
