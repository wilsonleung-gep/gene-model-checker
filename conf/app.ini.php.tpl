; <?php exit; ?>
;-------------------
; Configurations for Gene Model Checker
;-------------------

[database]
username = "[Database username]"
password = "[Database password]"
hgcentralDb = "[UCSC Genome Browser hgcentral database]"
informantDb = "[Gene Record Finder database]"

[app]
trashdir = "[Path to directory for temporary files]"
rootdir = "[Path to gene-model-checker directory]"
webroot = "[URL to gene-model-checker directory]"
assemblylookup = "services/assemblylookup.php"
scaffoldlookup = "services/scaffoldlookup.php"
alignservice = "services/alignmentservice.php"
dotplotviewer = "[URL to dot-plot-viewer directory]"
title = "Genome Assemblies on the GEP UCSC Genome Browser"
gbdbdir = "[Path to the gbdb directory for the UCSC Genome Browser]"
twobitdir = "[Path to directory containing the UCSC TwoBit files]"
twobitcache = "[Path to cache directory]"

[bin]
twoBitToFa = "/path/to/twoBitToFa"
bgzip = "/path/to/bgzip"
tabix = "/path/to/tabix"
vcfConsensus = "/path/to/runVcfConsensus.sh"
colorDotplot = "/path/to/color_dotplot.pl"
colorAligner = "/path/to/color_alignment.pl"
