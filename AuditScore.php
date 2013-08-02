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

class AuditScore {

	private $file = 'sections.json';
	private $ctx;

	function addEventHandlers (RequestContext $ctx) {
		$ctx->addEventHandler(ActionEvent::FIELDS_PROCESSED, $this, 'calculateScores');
		$ctx->addEventHandler(ViewerEvent::PRE_SAVE, $this, 'calculateScores');

		// Need to re-generate Text PDF for emailing after SAVE
		$this->ctx = $ctx;
		$ctx->addEventHandler(ViewerEvent::SAVED, $this, 'regenPDF');
	}

	function calculateScores (ActionEvent $event) {
		$sections = json_decode(file_get_contents(dirname(__FILE__) . "/$this->file"), TRUE);
		$pgs = $event->document->pages;
		$p1 = $pgs[0]->fields;

		$compliance = 0;
		foreach ($sections as $n => $pages) {
			$score[$n]['yes'] = 0;
			$score[$n]['no'] = 0;
			$score[$n]['na'] = 0;
			foreach ($pages as $p => $fields) {

				$page = $pgs[$p]->fields;
				foreach ($fields as $f) {
					$value = $page[$f]->value;
					// ignore unanswered questions
					if ($value) $score[$n][$value]++;
				}
			}

			// Calculate percentage compliance
			$scored = $score[$n]['yes'] + $score[$n]['no'];
			$percent = ($scored ? 100 * $score[$n]['yes'] / $scored : 0);
			$compliance += $percent;
			// round and pad to box
			$percent = str_pad(round($percent), 3, ' ', STR_PAD_LEFT);
			$p1['p1_sec' . $n . '_percent']->value = $percent;

			// $page will now be last page of section
			$base = 'p' . ($p+1) . '_sec' . $n . '_';
			$page[$base . 'yes_percent']->value = $percent;
			foreach ($score[$n] as $column => $total) {
				$page[$base . $column . '_total']->value = str_pad($total, 2, ' ', STR_PAD_LEFT);
			}
		}

		// Record total
		$compliance = str_pad(round($compliance / 14), 3, ' ', STR_PAD_LEFT);
		$p1['p1_total_percent']->value = $compliance;
		g_Log(__METHOD__ . ': Overall score ' . $compliance . '%');
	}

	function regenPDF (ViewerEvent $event) {
		// not mine
	}
}
