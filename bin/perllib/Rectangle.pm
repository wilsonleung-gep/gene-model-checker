package Rectangle;
use strict;

sub new {
  my ($class, $width, $height) = @_;

  my $objref = {
                width => $width,
                height => $height
               };

  bless $objref, $class;
  return $objref;
}

sub get_width { $_[0]->{width} }
sub get_height { $_[0]->{height} }

sub margin_offset {
  my ($self, $margin, $factor) = @_;

  $factor ||= 2;

  return Rectangle->new($self->{width}  - $factor * $margin->{x},
                        $self->{height} - $factor * $margin->{y});

}

1;
