package Axis;
use strict;

use GD;
use Data::Dumper;

sub new {
  my ($class, $ref_options) = @_;

  my $ref_settings = { num_ticks => 10,
                       tick_height => 10,
                       label_font_size => GD::Font->Large,
                       scale_font_size => GD::Font->Small,
                       label => "Sequence"
                     };

  @{$ref_settings}{ keys %{$ref_options} } = values %{$ref_options};

  $ref_settings->{label_width} =
    $ref_settings->{label_font_size}->width * (length($ref_settings->{label}));

  my $objref = $ref_settings;

  bless $objref, $class;

  return $objref;
}

sub draw_x_axis {
  my ($self, $dot_plot) = @_;

  my $black = $self->{colors}->{black};

  my ($margin_x, $margin_y) = ($dot_plot->{margin}->{x}, $dot_plot->{margin}->{y});
  my $label_start = ($dot_plot->{img}->{width} - $self->{label_width}) / 2;

  $dot_plot->{im}->string($self->{label_font_size},
                          $label_start,
                          $dot_plot->{img}->{height} - ($margin_y / 4),
                          $self->{label},
                          $black);

  my $query_length = $dot_plot->{query_length};
  my $step_size = $self->calc_tick_size($query_length);

  my $tick_y_top_pos = $dot_plot->{actual}->{height} + $margin_y;
  my $tick_y_bottom_pos = $tick_y_top_pos + $self->{tick_height};

  my $query_pos = $step_size;

  my ($x_pos, $tick_label, $tick_label_width);
  while ($query_pos < $query_length) {
    $x_pos = ($query_pos - 1) * $dot_plot->{scale}->{x} + $margin_x;

    $dot_plot->{im}->line($x_pos, $tick_y_top_pos,
                          $x_pos, $tick_y_bottom_pos,
                          $black);

    $tick_label = sprintf("%d", $query_pos);
    $tick_label_width = $self->{scale_font_size}->width * (length($tick_label));

    $dot_plot->{im}->string($self->{scale_font_size},
                            $x_pos - ($tick_label_width / 2),
                            $tick_y_bottom_pos,
                            $tick_label,
                            $black);

    $query_pos += $step_size;
  }
}

sub draw_y_axis {
  my ($self, $dot_plot) = @_;

  my $black = $self->{colors}->{black};

  my ($margin_x, $margin_y) = ($dot_plot->{margin}->{x}, $dot_plot->{margin}->{y});
  my $label_start = ($dot_plot->{img}->{height} + $self->{label_width}) / 2;

  $dot_plot->{im}->stringUp($self->{label_font_size},
                            $margin_x / 4,
                            $label_start,
                            $self->{label},
                            $black);

  my $subject_length = $dot_plot->{subject_length};
  my $step_size = $self->calc_tick_size($subject_length);

  my $x_left_pos = $margin_x - $self->{tick_height};
  my $x_right_pos = $margin_x;

  my $actual_height = $dot_plot->{actual}->{height};
  my $tick_label_height = $self->{scale_font_size}->height;

  my $subject_pos = $step_size;
  my ($y_pos, $tick_label, $tick_label_width);
  while ($subject_pos < $subject_length) {
    $y_pos = $actual_height + $margin_y - ($subject_pos - 1) * $dot_plot->{scale}->{y};

    $dot_plot->{im}->line($x_left_pos, $y_pos,
                          $x_right_pos, $y_pos,
                          $black);

    $tick_label = sprintf("%d", $subject_pos);
    $tick_label_width = $self->{scale_font_size}->width * (length($tick_label));

    $dot_plot->{im}->string($self->{scale_font_size},
                            $margin_x - $self->{tick_height} - $tick_label_width,
                            $y_pos - ($tick_label_height / 2),
                            $tick_label,
                            $black);

    $subject_pos += $step_size;
  }
}

sub calc_tick_size {
  my ($self, $seq_length) = @_;

  my @tick_sizes = (1,10,20,50,100,200,500,1000,2000,5000,10000);

  for (my $i=0; $i<scalar(@tick_sizes); $i++) {
    if ($self->{num_ticks} * $tick_sizes[$i] > $seq_length) {
      return $tick_sizes[$i];
    }
  }

  return $tick_sizes[-1];
}

1;
