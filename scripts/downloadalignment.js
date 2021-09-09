/*global html2canvas */
document.addEventListener("DOMContentLoaded", function() {
    var downloadElement = document.getElementById("download_alignment");

    downloadElement.addEventListener("click", function(event) {
        event.preventDefault();
        event.stopPropagation();

        html2canvas(document.body).then( function(canvas) {
            saveAs(canvas.toDataURL("image/png"), buildImageFileName());
        });

        return false;
    });

    // https://stackoverflow.com/questions/41165865/
    function saveAs(uri, filename) {

        var link = document.createElement("a");

        if (typeof link.download === "string") {
            link.href = uri;
            link.download = filename;

            document.body.appendChild(link);

            link.click();

            document.body.removeChild(link);

        } else {
            window.open(uri);
        }
    }

    function buildImageFileName() {
        var pathname = window.location.pathname;

        var m = pathname.match(/([^/]+)\.html/);

        if (m === null) {
            return "Gene_Model_Checker_Alignment.png";
        }

        return m[1] + ".png";
    }
});
