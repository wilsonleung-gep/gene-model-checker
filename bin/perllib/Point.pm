package Point;
use strict;

sub new {
  my ($class, $x, $y) = @_;

  my $objref = {
                x => $x,
                y => $y
               };

  bless $objref, $class;
  return $objref;
}

sub get_x { $_[0]->{x} }
sub get_y { $_[0]->{y} }

1;
