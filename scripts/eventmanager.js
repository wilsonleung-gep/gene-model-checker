/*global Ext, GEP */
GEP.EventManager = (function() {
    "use strict";

    var events = {};

    function removeEvent(type) {
        if (events[type]) {
            delete events[type];
        }
    }

    function fire(type, obj) {
        var current_event = events[type];

        if (current_event === undefined) {
            return;
        }

        Ext.each(current_event, function(func) {
            func(obj);
        });
    }

    function subscribe(type, subscriber) {
        events[type] = events[type] || [];
        events[type].push(subscriber);
    }

    function unsubscribe(type, subscriber) {
        var current_event = events[type];

        if (current_event === undefined) {
            return;
        }

        current_event.remove(subscriber);
    }

    return {
        removeEvent: removeEvent,
        fire: fire,
        subscribe: subscribe,
        unsubscribe: unsubscribe
    };
}());
