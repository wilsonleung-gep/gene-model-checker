/*global Ext, GEP */
(function() {
    "use strict";

    function init_extjs() {
        Ext.util.CSS.swapStyleSheet("theme", "./lib/ext/resources/css/xtheme-red.css");
        Ext.BLANK_IMAGE_URL = "./lib/ext/resources/images/default/s.gif";
        Ext.QuickTips.init();

        Ext.form.Field.prototype.msgTarget = "side";
    }

    function init_statemanager() {
        Ext.state.Manager.setProvider(new Ext.state.CookieProvider({
            expires: new Date(new Date().getTime() + (1000*60*60*24))
        }));

        GEP.EventManager.fire("restore_formstate");
    }

    function init_gui() {
        GEP.errorDialog();

        GEP.loadingPanel("loading");

        GEP.hintPanel("hintpanel");

        GEP.browserViewer("browserviewer");

        GEP.viewPort();
    }

    function init() {
        init_extjs();

        init_gui();
        init_statemanager();

        GEP.EventManager.fire("initgui_complete");
    }

    Ext.onReady(init);
}());
