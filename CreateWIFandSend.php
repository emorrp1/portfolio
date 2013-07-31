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

class CreateWIFandSend {

	private $typeId = '';
	private $fields = array(
		'hospital_no' => 'PAS Number / Unit Number',
		'nhs_no' => 'NHS Number',
		'surname' => 'Surname',
		'firstname' => 'Forename',
		'procedure_date' => 'Document Date',
		'dob' => 'Date of Birth',
	);
	private $tmp;
	private $document;

	function addEventHandlers (RequestContext $ctx) {
		$ctx->addEventHandler(MailerEvent::PRE_MAIL, $this, 'createWIF');
		$ctx->addEventHandler(RendererEvent::PDF, $this, 'copyPDF');
	}

	function createWIF (MailerEvent $event) {
		g_Log(__METHOD__ . ': creating WIF file');
		$wif = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8" ?>
			<fileparameters>
				<revision number="1.2" />
			</fileparameters>
		');

		$primary = $wif->addChild('primarylink');
			$unique = $primary->addChild('uniquereference', $event->document->id);
			$unique->addAttribute('type', 'I');
			if ($this->typeId) {
				$primary->addChild('typeid', $this->typeId);
			}

		$additional = $wif->addChild('additionalindexes');
			$i = 0;
			$p1 = $event->document->pages[0]->fields;
			foreach ($this->fields as $fieldname => $indexname) {
				$isDate = strpos($indexname, 'Date') !== FALSE;
				if ( $isDate || trim($p1[$fieldname]->value)) {

					$i++;
					$index = $additional->addChild('index_' . $i);
					$index->addChild('id', $indexname);
					if ($isDate) {
						$this->document = $event->document;
						$this->addDateField($index, $fieldname);
					} else {
						$index->addChild('value', trim($p1[$fieldname]->value));
					}
				}
			}
			$additional->addAttribute('count', $i);

		g_Log(__METHOD__ . ': attaching WIF to email');
		$this->mkTmp();
		$file = $this->tmp . 'Formidable' . $event->document->id . '.wif';
		$wif->asXML($file);
		$event->mailDeliverer->attachments[] = new Attachment($file, basename($file), 'application/xml');
	}

	function copyPDF (RendererEvent $event) {
		g_Log(__METHOD__ . ': copying PDF to WinDIP');
		$this->mkTmp();
		$file = $this->tmp . 'Formidable' . $event->document->id . '.pdf';
		@copy($event->file, $file);
	}

	private function addDateField (SimpleXMLElement &$parent, $fname, $page=0) {
		// not mine
	}

	private function mkTmp () {
		// not mine
	}
}
