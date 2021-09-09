package DotPlot;
use strict;

use Carp;
use GD;

use Rectangle;
use Point;
use Axis;

sub new {
  my ($class, $query_length, $subject_length, $ref_options) = @_;

  unless ( (defined $query_length) && (defined $subject_length) ) {
    croak("Error: Dot plot requires query and subject lengths");
  }

  my $ref_settings = { query_length => $query_length,
                       subject_length => $subject_length,
                       img => Rectangle->new(800, 800),
                       margin => Point->new(100, 100),
                       title => "Query versus Subject sequence",
                       x_axis_cfg => { num_ticks => 10, tick_height => 10, label => "Query" },
                       y_axis_cfg => { num_ticks => 10, tick_height => 10, label => "Subject" }
                     };

  @{$ref_settings}{ keys %{$ref_options} } = values %{$ref_options};

  $ref_settings->{actual} = $ref_settings->{img}->margin_offset($ref_settings->{margin});

  $ref_settings->{x_axis} = Axis->new($ref_settings->{x_axis_cfg});
  $ref_settings->{y_axis} = Axis->new($ref_settings->{y_axis_cfg});

  $ref_settings->{scale} = Point->new($ref_settings->{actual}->{width} / $query_length,
                                      $ref_settings->{actual}->{height} / $subject_length);

  $ref_settings->{im} = GD::Image->new($ref_settings->{img}->{width},
                                       $ref_settings->{img}->{height}, 1);

  my $ref_colors = init_colors($ref_settings->{im});
  $ref_settings->{colors} = $ref_colors;
  $ref_settings->{alt_colors} = { x => [$ref_colors->{red100}, $ref_colors->{blue100}],
                                  y => [$ref_colors->{orange100}, $ref_colors->{lightgreen100}]
                                };

  bless $ref_settings, $class;

  init_dotplot($ref_settings);

  return $ref_settings;
}

sub init_colors {
  my ($im) = @_;

  return {
          white   => $im->colorAllocate(255,255,255),
          black   => $im->colorAllocate(0,0,0),
          red100  => $im->colorAllocateAlpha(255,64,64, 100),
          blue100 => $im->colorAllocateAlpha(0,0,200, 100),
          orange100 => $im->colorAllocateAlpha(255,165,0, 100),
          lightgreen100 => $im->colorAllocateAlpha(84,255,159, 100)
         };
}

sub init_dotplot {
  my ($self) = @_;

  $self->draw_frame();
  $self->draw_title();
  $self->draw_axis();
}

sub draw_frame {
  my ($self) = @_;

  $self->{im}->alphaBlending(0);
  $self->{im}->fill(0,0,$self->{colors}->{"white"});

  my $margin = $self->{margin};

  $self->{im}->rectangle($margin->{'x'},
                         $margin->{'y'},
                         $self->{img}->{'width'} - $margin->{'x'},
                         $self->{img}->{'height'} - $margin->{'y'},
                         $self->{colors}->{black});
}

sub draw_title {
  my ($self) = @_;

  my $title_width = (GD::Font->Giant->width) * (length($self->{title}));

  $self->{im}->string(GD::Font->Giant,
              ($self->{img}->{width} - $title_width) / 2,
              $self->{margin}->{'y'} / 2,
              $self->{title},
              $self->{colors}->{black});
}

sub draw_axis {
  my ($self) = @_;

  $self->{x_axis}->draw_x_axis($self);
  $self->{y_axis}->draw_y_axis($self);
}

sub draw_color_grid {
  my ($self, $ref_query_exons, $ref_subject_exons) = @_;

  $self->{im}->alphaBlending(1);

  my ($margin_x, $margin_y) = ($self->{margin}->{x}, $self->{margin}->{y});
  my ($scale_x, $scale_y) = ($self->{scale}->{x}, $self->{scale}->{y});
  my $y_pos = $self->{img}->{height} - $margin_y;

  push(@{$ref_subject_exons}, $self->{subject_length});
  push(@{$ref_query_exons}, $self->{query_length});

  for (my $j=0; $j<scalar(@{$ref_subject_exons}); $j++) {
    $self->{im}->filledRectangle($margin_x,
                                 $y_pos - $ref_subject_exons->[$j+1] * $scale_y,
                                 $margin_x + $self->{actual}->{width},
                                 $y_pos - $ref_subject_exons->[$j] * $scale_y,
                                 $self->{alt_colors}->{y}->[$j % 2]);
  }

  for (my $i=0; $i<scalar(@{$ref_query_exons}); $i++) {
    $self->{im}->filledRectangle($margin_x + $ref_query_exons->[$i] * $scale_x,
                                 $margin_y,
                                 $margin_x + $ref_query_exons->[$i+1] * $scale_x,
                                 $y_pos,
                                 $self->{alt_colors}->{x}->[$i % 2]);
  }

  $self->{im}->alphaBlending(0);
}

sub draw_line {
  my ($self, $start_coords, $end_coords) = @_;

  my ($margin_x, $margin_y) = ($self->{margin}->{x}, $self->{margin}->{y});
  my ($scale_x, $scale_y) = ($self->{scale}->{x}, $self->{scale}->{y});
  my $start_y = $self->{actual}->{height} + $margin_y;

  $self->{im}->line($start_coords->{x} * $scale_x + $margin_x,
                    $start_y - ($start_coords->{y} * $scale_y),
                    $end_coords->{x} * $scale_x + $margin_x,
                    $start_y - ($end_coords->{y} * $scale_y),
                    $self->{colors}->{black});
}

1;
