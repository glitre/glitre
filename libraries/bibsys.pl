#!/usr/bin/perl -w 

# Use this file: http://bibliotek.bibsys.no/bibserv/biblet/bibliotek?lang=nb
# Remove the column "Bib.kode" in a spreadsheet and save as CSV

use strict;
use Text::CSV_XS;

my @rows;
my $csv = Text::CSV_XS->new({ binary => 1 })  or die "Cannot use CSV: ".Text::CSV->error_diag ();

# print "<?php\n";

open my $fh, "bibsys.csv" or die "bibsys.csv: $!";
while ( my $row = $csv->getline( $fh ) ) {
    
    my $name = $row->[0];
    my $short = $row->[1];
    my $id = lc($short);
    $id =~ s|/||g;
    $id =~ s|Æ|ae|ig;
    $id =~ s|Ø|oe|ig;
    $id =~ s|Å|aa|ig;

	print <<EOF;
INSERT INTO libraries SET 
	id               = '$id', 
    name             = '$name',
    name_short       = '$name',
    records_max      = 100, 
    records_per_page = 10, 
    system           = 'bibsys',  
    z3950            = 'z3950.bibsys.no:2100/$short', 
    theme            = 'apple'
;
EOF
	print "\n";

}

# print "?>\n";

$csv->eof or $csv->error_diag();
close $fh;
