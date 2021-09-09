package SeqFeature;
use strict;

sub new {
  my ($class, $name, $sequence, $cds_starts_str) = @_;

  $cds_starts_str ||= "";
  my @cds_starts = (split(",", $cds_starts_str));

  my $objref = {
                name => $name,
                sequence => $sequence,
                length => length($sequence),
                cds_starts => \@cds_starts
               };

  bless $objref, $class;

  return $objref;
}

sub new_from_file {
  my ($class, $infile, $cds_starts_str) = @_;

  my $name;
  my @sequence;

  open my $fh_infile, "<", $infile or &dienice("Cannot open file: $infile: $!");

  while (my $line = <$fh_infile>) {
    next if ( ($line =~ /^#/) || ($line =~ /^\s*$/) );
    chomp($line);

    if ($line =~ /^>(.*)/) {
      $name = $1;
    } else {
      push(@sequence, $line);
    }
  }

  close($fh_infile) or &dienice("Cannot close file: $infile: $!");

  return SeqFeature->new($name, join("", @sequence), $cds_starts_str);
}

sub build_word_table {
  my ($self, $word_size) = @_;

  $word_size ||= 20;
  my %word_hash = ();

  my $word;
  for (my $i=0; $i < ($self->{length} - $word_size + 1); $i++) {
    $word = substr($self->{sequence}, $i, $word_size);

    $word_hash{$word} ||= [];
    push(@{$word_hash{$word}}, $i);
  }

  return %word_hash;
}


sub to_string {
  my ($self) = @_;

  return sprintf("%s\t%s", $self->{name}, $self->{sequence});
}

1;
