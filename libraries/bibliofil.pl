#!/usr/bin/perl -w 

# Use this file: http://bibliotek.bibsys.no/bibserv/biblet/bibliotek?lang=nb
# Remove the column "Bib.kode" in a spreadsheet and save as CSV

use strict;
use Text::CSV_XS;

my @rows;
my $csv = Text::CSV_XS->new({ binary => 1 })  or die "Cannot use CSV: ".Text::CSV->error_diag ();

# print "<?php\n";

open my $fh, "bibliofil.csv" or die "bibliofil.csv: $!";
while ( my $row = $csv->getline( $fh ) ) {
    
    my $name = $row->[0];
    my $zurl = $row->[1];
    if ($zurl eq '') {
    	next;
    }
    my $port = $row->[2];
    my $base = $row->[3];
    $zurl .= ":$port/$base";
    $zurl =~ m/[z3950|websok]\.(.*?)\./ig;
    my $id = $1;

	print <<EOF;
INSERT INTO libraries SET 
	id               = '$id', 
    name             = '$name',
    name_short       = '$name',
    records_max      = 100, 
    records_per_page = 10, 
    system           = 'bibliofil',  
    z3950            = '$zurl', 
    theme            = 'apple'
;
EOF
	print "\n";

}

# print "?>\n";

$csv->eof or $csv->error_diag();
close $fh;
