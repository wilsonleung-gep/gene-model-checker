#!/usr/bin/perl
use warnings;
use strict;

use Getopt::Std;
use File::Basename;

use FindBin qw($Bin);
use lib "${Bin}/perllib";

use Bio::AlignIO;
use Data::Dumper;

use constant {
  LINE_WIDTH => 60
};

sub main {
  my %params = &parse_arguments();

  my $alignIO = Bio::AlignIO->new(-file => $params{"alignment_file"},
                                  -format => "emboss");

  if (my $aln = $alignIO->next_aln()) {
    generate_alignment($aln, \%params);
  } else {
    report_error($params{"outfile"}, "Invalid alignment file");
  }
}

&main();

sub generate_alignment {
  my ($aln, $ref_params) = @_;

  my $ref_metadata = get_alignment_metadata($ref_params->{alignment_file});

  my $ref_blocks = { "query"  => build_block($aln, 1, $ref_params->{"query_coords"}),
                     "target" => build_block($aln, 2, $ref_params->{"target_coords"}) };

  to_html($aln, $ref_metadata, $ref_blocks, $ref_params->{outfile});
}

sub build_block {
  my ($aln, $seq_id, $ref_coords) = @_;

  my $seq_obj = $aln->get_seq_by_pos($seq_id);

  my @bases = (split(//, $seq_obj->seq()));

  return { bases => \@bases,
           num_columns => scalar(@bases),
           id => $seq_obj->id(),
           mask => build_column_bitmask($aln, $seq_obj, $ref_coords) };
}

sub build_column_bitmask {
  my ($aln, $seq_obj, $unpadded_str) = @_;

  my $ref_padded_coords = get_padded_coords($aln, $seq_obj->id(), $unpadded_str);

  my @mask = (0) x $aln->length;
  my $num_coords = scalar(@{$ref_padded_coords});

  my ($s, $e);
  for (my $i=1; $i<$num_coords; $i += 2) {
    ($s, $e) = ($ref_padded_coords->[$i-1], $ref_padded_coords->[$i] - 1);
    @mask[$s .. $e] = (1) x ($e - $s + 1)
  }

  return \@mask;
}

sub generate_color_alignment_blocks {
  my ($fh_outfile, $ref_metadata, $ref_blocks, $match_line) = @_;

  my $q_id = $ref_metadata->{1};
  my $t_id = $ref_metadata->{2};

  my $label_length = max(length($q_id), length($t_id));
  my $num_columns = $ref_blocks->{query}->{num_columns};

  my ($unpadded_qpos, $unpadded_tpos) = (1, 1);

  my $tpl = "%-${label_length}s %5s %s %-5s\n" x 3 . "\n";

  for (my $i=0; $i<$num_columns; $i += LINE_WIDTH) {
    printf {$fh_outfile} $tpl,
      $q_id, decorate_seq($ref_blocks->{query}, $i, "q", \$unpadded_qpos),
      "", "", substr($match_line, $i, LINE_WIDTH), "",
      $t_id, decorate_seq($ref_blocks->{target}, $i, "t", \$unpadded_tpos),
  }
}

sub decorate_seq {
  my ($ref_block, $col_start, $prefix, $ref_unpadded_pos) = @_;

  my $col_end = $col_start + LINE_WIDTH;
  $col_end = $ref_block->{num_columns} if ($col_end > $ref_block->{num_columns});

  my ($ref_bases, $ref_mask) = ($ref_block->{bases}, $ref_block->{mask});

  my $prev_color_idx = $ref_mask->[$col_start];
  my $color_idx;

  my $base = $ref_bases->[$col_start];
  my $num_unpadded_bases = ($base eq "-") ? 0 : 1;
  my @aln_columns = (sprintf("<span class='%scolor%s'>%s", $prefix, $prev_color_idx, $base));

  for (my $i=$col_start + 1; $i<$col_end; $i++) {
    $color_idx = $ref_mask->[$i];

    if ($color_idx ne $prev_color_idx) {
      push(@aln_columns, sprintf("</span><span class='%scolor%s'>", $prefix, $color_idx));
    }

    $base = $ref_bases->[$i];

    $num_unpadded_bases += 1 unless ($base eq "-");

    push (@aln_columns, $base);
    $prev_color_idx = $color_idx;
  }

  push(@aln_columns, "</span>");

  my $start_pos = ${$ref_unpadded_pos};
  ${$ref_unpadded_pos} += $num_unpadded_bases;
  my $end_pos = ${$ref_unpadded_pos} - 1;

  return ($start_pos, join("", @aln_columns), $end_pos);
}

sub to_html {
  my ($alignment, $ref_metadata, $ref_blocks, $outfile) = @_;

  my $title = sprintf("Alignment of %s vs. %s",
                      $ref_metadata->{"1"}, $ref_metadata->{"2"});

  my $params_list = generate_param_list($ref_metadata);
  my $text_alignment_link = generate_alignmentfile_link($outfile);

  open my $fh_outfile, ">", $outfile or &dienice("Cannot open output file $outfile: $!");

  print {$fh_outfile} <<HTML;
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>${title}</title>
    <link href="../styles/alignmentstyles.css" rel="stylesheet" type="text/css">
  </head>
  <body>
    <h1>${title}</h1>
    <div class="navmenu-links" data-html2canvas-ignore>
      <ul>
        <li>$text_alignment_link</li>
        <li><a id="download_alignment" href="#" target="_blank">Download alignment image</a></li>
      </ul>
    </div>
    <div id="alignment_params">$params_list</div>
    <div id="alignment">
<pre>
HTML

  generate_color_alignment_blocks($fh_outfile,
                                  $ref_metadata,
                                  $ref_blocks,
                                  $alignment->match_line());

  print {$fh_outfile} <<HTML;
</pre>
    </div>
    <script src="../lib/html2canvas.min.js"></script>
    <script src="../scripts/downloadalignment.js"></script>
  </body>
</html>
HTML

    close($fh_outfile) or &dienice("Cannot close output file $outfile: $!");
}

sub get_padded_coords {
  my ($aln, $seq_id, $unpadded_str) = @_;

  my @unpadded_coords = (split(",", $unpadded_str));
  shift(@unpadded_coords) if ($unpadded_coords[0] == 0);
  @unpadded_coords = map { $_ - 1 } @unpadded_coords;

  my $num_coords = @unpadded_coords;
  my @padded_coords;

  my $seq;

  foreach my $s ($aln->each_seq) {
    if ($seq_id eq $s->id()) {
      $seq = $s;
    }
  }

  for (my $i=0; $i<$num_coords; $i++) {
    my $residue = $unpadded_coords[$i] + 1;

    if ($residue >= $seq->start() and $residue <= $seq->end()) {
      push(@padded_coords, $aln->column_from_residue_number($seq_id, $residue));
    }
  }

  push(@padded_coords, $aln->length);

  return \@padded_coords;
}

sub generate_alignmentfile_link {
  my ($outfile) = @_;

  return sprintf("<a href='./%s.txt'>%s</a>", basename($outfile), "View plain text version");
}

sub generate_param_list {
  my ($ref_metadata) = @_;

  return sprintf("<b>Identity:</b> %s, <b>Similarity:</b> %s, <b>Gaps:</b> %s",
                 $ref_metadata->{Identity},
                 $ref_metadata->{Similarity},
                 $ref_metadata->{Gaps});
}

sub get_alignment_metadata {
  my ($aln_file) = @_;

  open my $fh_aln_file, "<", $aln_file or &dienice("Cannot open file: $aln_file: $!");

  my $is_in_block = undef;
  my $header_block_pattern = "^#===";
  my %metadata = ();

  while (my $line = <$fh_aln_file>) {
    next if ($line =~ /^\s*$/);
    chomp($line);

    if ($line =~ /$header_block_pattern/) {
      last if (defined $is_in_block);

      $is_in_block = 1;
      next;
    }

    next unless (defined $is_in_block);

    if ($line =~ /^#\s+(.*)\:\s+(.*)/) {
      $metadata{$1} = $2;
    }
  }

  close($fh_aln_file) or &dienice("Cannot close file: $aln_file: $!");

  return \%metadata;
}

sub report_error {
  my ($outfile, $err_message) = @_;

  $err_message ||= "Unknown error";

  open my $fh_outfile, ">", $outfile or &dienice("Cannot open output file $outfile: $!");

  print {$fh_outfile} <<HTML;
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>No alignments available</title>
  </head>
  <body>
    <b>No alignments have been generated because of the following error:</b>
    <p>${err_message}</p>
  </body>
</html>
HTML

  close($fh_outfile) or &dienice("Cannot close output file $outfile: $!");
}

sub max {
  my ($a, $b) = @_;

  return ($a > $b) ? $a : $b;
}

sub parse_arguments {
  my %opts;
  getopts('i:x:y:o:', \%opts) or usage();

  &usage() unless ($opts{'i'} && $opts{'o'});

  return (
          alignment_file => $opts{'i'},
          outfile => $opts{'o'},
          query_coords => $opts{'x'} || "0",
          target_coords => $opts{'y'} || "0"
         );
}

sub dienice {
  my $msg = shift;
  print "Error: ${msg}", "\n";
  exit;
}

sub usage {
  print "usage: $0 -i <infile> -o <outfile> -x <query_coords> -y <target_coords>\n";
  exit;
}
