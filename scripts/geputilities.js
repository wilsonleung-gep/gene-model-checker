/*global Ext */

if (window.GEP === undefined) {
    window.GEP = {
        Data: {
            browsercgiroot: "[URL for the UCSC Genome Browser hgTracks CGI]",

            genechecker_service: "./services/genechecker.php",

            speciesdbstore: new Ext.data.SimpleStore({
                fields: ["species"],
                data: [
                    ["D. melanogaster"], ["D. simulans"], ["D. sechellia"], ["D. yakuba"],
                    ["D. erecta"], ["D. eugracilis"], ["D. biarmipes"], ["D. suzukii"],
                    ["D. takahashii"], ["D. ficusphila"], ["D. rhopaloa"], ["D. elegans"],
                    ["D. kikkawai"], ["D. serrata"], ["D. ananassae"], ["D. bipectinata"],
                    ["D. pseudoobscura"], ["D. persimilis"], ["D. miranda"], ["D. obscura"],
                    ["D. willistoni"], ["D. arizonae"], ["D. mojavensis"], ["D. navojoa"],
                    ["D. hydei"], ["D. virilis"], ["D. grimshawi"], ["D. busckii"]
                ]
            })
        }
    };
}
