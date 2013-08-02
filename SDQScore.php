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

class SDQScore {

	private $scales = array(
		'EmotionalSymptoms' => array('headaches', 'worry', 'unhappy', 'nervous', 'fears'),
		'ConductProblems' => array('angry', 'doastold', 'fight', 'lying', 'takesthings'),
		'Hyperactivity' => array('restless', 'fidgeting', 'distracted', 'thinks', 'finish'),
		'PeerProblems' => array('alone', 'friend', 'otherpeople', 'pickedon', 'adults'),
		'Prosocial' => array('nice', 'share', 'helpful', 'kind', 'volunteer'),
	);
	private $reversed = array('doastold', 'friend', 'otherpeople', 'thinks', 'finish');
	private $impact = array('upset', 'homelife', 'friendships', 'classroom', 'leisure', 'peerrelationships');
	private $values = array(
		'not true' => 0,
		'somewhat true' => 1,
		'certainly true' => 2,
		'not at all' => 0,
		'only a little' => 0,
		'quite a lot' => 1,
		'a great deal' => 2,
		// typos in dpp
		'somewhat' => 1,
		'certainly' => 2,
		'certainly true ' => 2,
	);
	private $missing = 'missing';
	private $docfield = 'SDQ_SCORES';

	function addEventHandlers (RequestContext $ctx) {
		$ctx->addEventHandler(ActionEvent::FIELDS_PROCESSED, $this, 'calculateScores');
		$ctx->addEventHandler(ViewerEvent::PRE_SAVE, $this, 'calculateScores');
		$ctx->addEventHandler(ResponseEvent::PRE, $this, 'sendScores');
		$ctx->addEventHandler(PenPusherEvent::PRE_COMPLETE, $this, 'stopComplete');
	}

	function calculateScores (ActionEvent $event) {
		$p1 = $event->document->pages[0]->fields;
		foreach ($this->scales as $group => $questions) {
			$answered = 0;
			$scores[$group] = 0;
			foreach ($questions as $name) {

				$answer = $p1[$name]->value;
				if ($answer && $answer !== "false") {
					$answered++;
					$score = $this->values[$answer];
					$score = (in_array($name, $this->reversed) ? 2 - $score : $score);
					$scores[$group] += $score;
				}
			}

			switch ($answered) {
				case 0:
					$scores[$group] = $this->missing;
					break;

				// Scale score can be prorated if at least 3 items were completed
				case 3:
				case 4:
					g_Log(__METHOD__ . ": $group prorated from $answered questions");
					$scores[$group] = round($scores[$group] * 5 / $answered, 1);
					break;
			}
		}

		$difficulties = $scores;
		unset($difficulties['Prosocial']);
		// Total Difficulties score is counted as missing if one of the component scores is missing
		$total = (in_array($this->missing, $difficulties, TRUE) ? $this->missing : array_sum($difficulties));
		$scores = array('TotalDifficulties' => $total) + $scores; // Put total first
		g_Log(__METHOD__ . ': TotalDifficulties ' . $total);

		// Impact scores
		$group = 'Impact';
		$scores[$group] = 0;
		$p2 = $event->document->pages[1]->fields;
		foreach ($this->impact as $name) {
			// ignore fields from other forms
			if ( ! isset($p2[$name])) continue;

			$answer = $p2[$name]->value;
			if ($answer && $answer !== "false") {
				// impact has no prorated or reversed scoring
				$scores[$group] += $this->values[$answer];
			}
		}

		$store = json_encode($scores);
		if (isset($p1[$this->docfield])) {
			$p1[$this->docfield]->value = $store;
		} else {
			$p1[] = new Field($this->docfield, $store, Field::TYPE_TEXT_SINGLE);
		}
	}

	function sendScores (ResponseEvent $event) {
		$page = $event->document->pages[0];
		$msg[] = $page->address . ' Scores';
		$scores = json_decode($page->fields[$this->docfield]->value, TRUE);
		foreach ($scores as $group => $score) {
			$msg[] = $group . ': ' . $score;
		}

		$this->sendMessage($event, implode(', ', $msg));
	}

	function stopComplete (PenPusherEvent $event) {
		g_Log(__METHOD__ . ': PenPusher response');
		$event->cancelAction = TRUE;
	}

	private function sendMessage (ResponseEvent $event, $msg) {
		// not mine
	}
}
