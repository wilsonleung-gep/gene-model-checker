package Range;
use strict;

sub new {
  my ($class, $start, $end) = @_;

  my $objref = {
                start => $start,
                end => $end
               };

  bless $objref, $class;

  return $objref;
}

sub start { $_[0]->{start} }
sub end   { $_[0]->{end} }

sub get_length {
  my ($self) = @_;

  return $self->end - $self->start
}

sub to_string {
  my ($self) = @_;

  return sprintf("%d-%d", $self->start, $self->end);
}

1;
