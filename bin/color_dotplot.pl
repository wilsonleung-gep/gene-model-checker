#!/usr/bin/perl
use warnings;
use strict;

use Getopt::Std;
use FindBin qw($Bin);
use lib "${Bin}/perllib";

use SeqFeature;
use DotPlot;
use Point;

use GD;

sub main {
  my %params = &parse_arguments();

  my $query_seq = SeqFeature->new_from_file($params{"query_file"}, $params{"query_coords"});
  my $subject_seq = SeqFeature->new_from_file($params{"subject_file"}, $params{"subject_coords"});

  my $dot_plot = init_dot_plot($query_seq, $subject_seq);

  draw_matches($query_seq, $subject_seq, $dot_plot, $params{"word_size"});

  save_dot_plot($dot_plot, $params{"outfile"});
}

&main();

sub save_dot_plot {
  my ($dot_plot, $outfile) = @_;

  open my $fh_outfile, ">", $outfile or &dienice("Cannot open output file $outfile: $!");

  binmode $fh_outfile;
  print {$fh_outfile} $dot_plot->{im}->png;

  close($fh_outfile) or &dienice("Cannot close output file $outfile: $!");
}

sub draw_matches {
  my ($query_seq, $subject_seq, $dot_plot, $word_size) = @_;

  my %query_words = $query_seq->build_word_table($word_size);

  my $subject_aa = $subject_seq->{sequence};

  for (my $i=0; $i<$subject_seq->{length}; $i++) {
    my $subject_word = substr($subject_aa, $i, $word_size);

    next unless (defined $query_words{$subject_word});

    foreach my $query_pos (@{$query_words{$subject_word}}) {
      $dot_plot->draw_line(Point->new($query_pos, $i),
                           Point->new($query_pos + $word_size, $i + $word_size));
    }
  }
}

sub init_dot_plot {
  my ($query_seq, $subject_seq) = @_;

  my $title = sprintf("Dot plot of %s vs. %s",$query_seq->{name}, $subject_seq->{name});

  my $dot_plot = DotPlot->new($query_seq->{length},
                              $subject_seq->{length},
                              { title => $title,
                                x_axis_cfg => { label => $query_seq->{name} },
                                y_axis_cfg => { label => $subject_seq->{name} }
                              });

  $dot_plot->draw_color_grid($query_seq->{cds_starts}, $subject_seq->{cds_starts});

  return $dot_plot;
}

sub parse_arguments {
  my %opts;
  getopts('i:j:x:y:o:w:h:s:', \%opts) or usage();

  # check the required arguments
  &usage() unless ($opts{'i'} && $opts{'j'} && $opts{'o'});

  return (
          query_file => $opts{'i'},
          subject_file => $opts{'j'},
          outfile => $opts{'o'},
          query_coords => $opts{'x'} || "",
          subject_coords => $opts{'y'} || "",
          imgwidth => $opts{'w'} || 600,
          imgheight => $opts{'h'} || 600,
          word_size => $opts{'s'} || 10
         );
}

sub dienice {
  my $msg = shift;
  print "Error: ${msg}", "\n";
  exit;
}

sub usage {
  print "usage: $0 -i <infile> -o <outfile> -w <imgwidth> -h <imgheight>\n";
  exit;
}
