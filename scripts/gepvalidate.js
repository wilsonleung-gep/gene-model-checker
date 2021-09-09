/*global Ext */
(function() {
    "use strict";

    Ext.apply(Ext.form.VTypes, {
        checkoverlapping_coordinates: function(coord_set) {
            coord_set.sort(function(a,b) {
                if (a.minpos === b.minpos) {
                    return (b.maxpos - a.maxpos);
                }
                return (a.minpos - b.minpos);
            });

            var num_coord_set = coord_set.length;
            var i, current, previous;
            for (i=1; i<num_coord_set; i++) {
                current = coord_set[i];
                previous = coord_set[i-1];

                if (current.minpos <= previous.maxpos) {
                    throw String.format("Coordinates set {0}-{1} overlaps with {2}-{3}",
                        current.minpos, current.maxpos, previous.minpos, previous.maxpos);
                }
            }

            return true;
        },

        build_coorditem: function(field) {
            var pattern = /^(\d+)-(\d+)$/;
            var m = field.match(pattern);

            if ((m === null) || (m.length !== 3)) {
                throw "Invalid coordinates";
            }

            var startpos = parseInt(m[1], 10);
            var endpos = parseInt(m[2], 10);

            if ((startpos === 0) || (endpos === 0)) {
                throw "Coordinates must be positive integers";
            }

            return {
                minpos: ((startpos < endpos) ? startpos : endpos),
                maxpos: ((startpos < endpos) ? endpos : startpos)
            };
        },

        build_coordset: function(fieldvalue) {
            var fields = fieldvalue.replace(/\s+/g, "").split(",");

            var coordinates_set = [];
            var num_fields = fields.length;
            var i;
            for (i=0; i<num_fields; i++) {
                coordinates_set.push(this.build_coorditem(fields[i]));
            }

            return coordinates_set;
        },

        Project: function(val, field) {
            var constraint = field.constraint;
            var pattern = new RegExp("^" + constraint.prefix + "(\\d+)$");

            if (constraint.maxcount === 0) {
                return (val.indexOf(constraint.prefix) === 0);
            }

            var m = val.match(pattern);

            if ((m === null) || (m.length !== 2)) {
                return false;
            }

            return (m[1] <= constraint.maxcount);
        },

        ProjectText: "Cannot find project name within the selected project group",

        Genetype: function(val) {
            var pattern = /^[-:.()a-zA-Z0-9]+$/;
            var m = val.match(pattern);
            return (m !== null);
        },

        GenetypeText: "Entry does not match FlyBase isoform naming convention",

        Coordinatestype: function(val, field) {
            try {
                var coordinates_set = this.build_coordset(val);

                if (field.nooverlapwith !== undefined) {
                    var stopcodon = Ext.getCmp(field.nooverlapwith).getValue();

                    if (stopcodon !== "") {
                        coordinates_set.push(this.build_coorditem(stopcodon));
                    }
                }

                return (this.checkoverlapping_coordinates(coordinates_set));
            } catch(e) {
                this.CoordinatestypeText = e;
                return false;
            }
        },

        CoordinatestypeText: "Invalid coordinates",

        Stopcodontype: function(val, field) {
            try {
                var stopcodon = this.build_coorditem(val);

                if ((stopcodon.maxpos - stopcodon.minpos + 1) !== 3) {
                    throw "Stop codon coordinates must have a length of 3";
                }

                var cds_coords = Ext.getCmp(field.nooverlapwith).getValue();

                if (cds_coords === "") {
                    return true;
                }

                var compare_set = this.build_coordset(cds_coords);
                compare_set.push(stopcodon);

                return (this.checkoverlapping_coordinates(compare_set));

            } catch(e) {
                this.StopcodontypeText = e;
                return false;
            }
        },

        StopcodontypeText: "Invalid coordinates"
    });
}());
