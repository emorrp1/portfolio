#!/usr/bin/php
<?php
/*
Copyright (c) 2013, Philip Morrell
All rights reserved.

Redistribution and use in source and binary forms, with or without modification,
are permitted provided that the following conditions are met:

  Redistributions of source code must retain the above copyright notice, this
  list of conditions and the following disclaimer.

  Redistributions in binary form must reproduce the above copyright notice, this
  list of conditions and the following disclaimer in the documentation and/or
  other materials provided with the distribution.

  Neither the name of the copyright holder nor the names of any
  contributors may be used to endorse or promote products derived from
  this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR
ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

// See AuditScore.php
$config = simplexml_load_file('../config.xml');
$results = $config->xpath('//Group/@name');
foreach ($results as $group) {
	$name = (string) $group;

	// e.g. $name = p3_sec1_q8 or p3_sec1_q14
	preg_match_all('/\d+/', $name, $details);

	$sections[$details[0][1]][$details[0][0]-1][] = $name;
}

/* The data structure it's creating
array(
	// Sections
	[1] => array(
		// Pages
		[1] => array(
			// Group names
			'p2_sec1_q1',
			'p2_sec1_q7',
		),
		[2] => array(
			'p3_sec1_q8',
			'p3_sec1_q14',
		),
	),
	[14] => array(
		[42] => array(
			'p43_sec14_q1',
			'p43_sec14_q4',
		),
	),
);
*/

$f = fopen('sections.json', 'w');
fwrite($f, json_encode($sections));
fclose($f);
