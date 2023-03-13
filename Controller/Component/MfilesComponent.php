<?php
App::uses('HttpSocket', 'Network/Http');
class MfilesComponent extends Component {
	private $socket = '';
	private $tag = 'mfiles';
	public $settings = array(
		'url' => 'https://mfiles.documentwarehouse.com.na/REST/',
		'Username' => '',
		'Password' => '',
		'VaultGuid' => '',
		'X-Authentication' => '',
	);
	private const MFILES_DATATYPE_TEXT = 1;
	private const MFILES_DATATYPE_INTEGER = 2;
	private const MFILES_DATATYPE_FLOAT = 3;
	private const MFILES_DATATYPE_DATE = 5;
	private const MFILES_DATATYPE_TIME = 6;
	private const MFILES_DATATYPE_TIMESTAMP = 7;
	private const MFILES_DATATYPE_BOOLEAN = 8;
	private const MFILES_DATATYPE_LOOKUP = 9;
	private const MFILES_DATATYPE_MULTI_SELECT_LOOKUP = 10;
	private const MFILES_DATATYPE_INT64 = 11;
	private const MFILES_DATATYPE_FILETIME = 12;
	private const MFILES_DATATYPE_MULTILINE_TEXT = 13;
	private const MFILES_DATATYPE_ACL = 14;

	public function initialize() {
		$this->controller = $controller;
		$this->settings = array_merge($this->settings, $settings);
		$this->socket = new HttpSocket(array(
			'ssl_verify_peer' => false,
			'ssl_verify_peer_name' => false,
			'ssl_allow_self_signed' => true,
			'ssl_verify_depth' => 0,
			'timeout' => 60
		));
		$this->_authenticate();
	}

	public function retrieveByIdNumber($id_number) {
		$payload = json_encode();
		$result = $this->socket->get(
			$this->settings['url'] . 'objects/135/1/ObjectVersion?include=properties',
			$payload,
			array('header' => array(
				'Content-Type' => 'application/json',
				'X-Authentication' => $this->setttings['X-Authentication'],
			))
		);
		$this->log('Mfiles retrieve API request: ' . $this->socket->request['raw'], $this->tag);
		$this->log('Mfiles retrieve API response: ' . $result, $this->tag);
		$result = json_decode($result->body, true);
		return $result;
	}

	public function retrieveByPassportNumber($id_number) {
		$payload = json_encode();
		$result = $this->socket->get(
			$this->settings['url'] . 'objects/135/1/ObjectVersion?include=properties',
			$payload,
			array('header' => array(
				'Content-Type' => 'application/json',
				'X-Authentication' => $this->setttings['X-Authentication'],
			))
		);
		$this->log('Mfiles retrieve API request: ' . $this->socket->request['raw'], $this->tag);
		$this->log('Mfiles retrieve API response: ' . $result, $this->tag);
		$result = json_decode($result->body, true);
		return $result;
	}

	public function saveToVault($driblets) {
		$transformed_data = array('PropertyValues' => array());
		foreach ($driblets as $label => $value) {
			$transformed_data['PropertyValues'][] = $this->_transformToMFiles($label, $value);
			if (in_array($label, array('id_front', 'drivers_front', 'passport', 'proof_of_income', 'proof_of_addres', 'selfie', 'id_rear', 'proof_of_account', 'proof_of_mandate', 'paye_certificate'))) {
				/* First, upload the file and get the relevant details */
				list($upload_id, $filesize) = $this->_processUpload($label, $value);
				$transformed_data['Files'][] = array(
					'UploadID' => $upload_id,
					'Size' => $filesize,
					'Title' => pathinfo($value, PATHINFO_FILENAME),
					'Extension' => pathinfo($value, PATHINFO_EXTENSION)
				);
			}
		}
		$payload = json_encode($transformed_data);
		$result = $this->socket->post(
			$this->settings['url'] . 'objects/101',
			$payload,
			array('header' => array(
				'Content-Type' => 'application/json',
				'X-Authentication' => $this->setttings['X-Authentication'],
			))
		);
		$this->log('Mfiles upload API request: ' . $this->socket->request['raw'], $this->tag);
		$this->log('Mfiles upload API response: ' . $result, $this->tag);
		$result = json_decode($result->body, true);
		return $result;
	}

	private function _authenticate() {
		$payload = json_encode(array(
			'Username' => $this->settings['Username'],
			'Password' => $this->settings['Password'],
			'VaultGuid' => $this->settings['VaultGuid']
		));
		$result = $this->socket->post(
			$this->settings['url'] . 'server/authenticationtokens',
			$payload,
			array('header' => array(
				'Content-Type' => 'application/json',
			))
		);
		$this->log('Mfiles authenticate API request: ' . $this->socket->request['raw'], $this->tag);
		$this->log('Mfiles authenticate API response: ' . $result, $this->tag);
		$result = json_decode($result->body, true);
		$this->setttings['X-Authentication'] = $result['Value'];
	}

	private function _getDocumentTypeByLabel($label) {
		$documentTypeLookupTable = array_flip(array(
			10 => 'id_front',
			13 => 'drivers_front',
			14 => 'passport',
			15 => 'proof_of_income',
			17 => 'proof_of_addres',
			18 => 'selfie',
			19 => 'id_rear',
			20 => 'proof_of_account',
			21 => 'proof_of_mandate',
			22 => 'paye_certificate'
		));
		return $documentTypeLookupTable[$label];
	}

	private function _getPropertyDefByLabel($label) {
		$propertyDefLookupTable = array(
			'dob' => 1103,
			'email' => 1064,
			'employer' => 1157,
			'firstname' => 1034,
			'id' => 1149,
			'id_number' => 1036,
			'lastname' => 1035,
			'mobile_number' => 1147,
			'nationality' => 1153,
			'occupation' => 1246,
			'passport_expiry' => 1048,
			'passport_number' => 1148,
			'pay_using' => 1162,
			'phone_home' => 1189,
			'phone_work' => 1165,
			'previous_surname' => 1042,
			'residential_address' => 1079,
			'source_of_income' => 1159,
			'title' => 1040,

			'id_front' => 1045,
			'drivers_front' => 1045,
			'passport' => 1045,
			'proof_of_income' => 1045,
			'proof_of_addres' => 1045,
			'selfie' => 1045,
			'id_rear' => 1045,
			'proof_of_account' => 1045,
			'proof_of_mandate' => 1045,
			'paye_certificate' => 1045
		);
		return $propertyDefLookupTable[$label];
	}

	private function _getTypedValueByLabelAndValue($label, $value) {
		$propertyDef = $this->_getPropertyDefByLabel($label);
		if (in_array($label, array('id', 'id_number', 'passport_number', 'lastname', 'firstname', 'email', 'residential_address', 'mobile_number', 'employer', 'previous_surname', 'phone_home', 'phone_work'))) {
			$typedValue = array(
				'DataType' => self::MFILES_DATATYPE_TEXT,
				'Value' => $value
			);
		} else {
			switch ($propertyDef) {
				case 'id_front':
				case 'drivers_front':
				case 'passport':
				case 'proof_of_income':
				case 'proof_of_addres':
				case 'selfie':
				case 'id_rear':
				case 'proof_of_account':
				case 'proof_of_mandate':
				case 'paye_certificate':
					$typedValue = array(
						'DataType' => $this->_getDocumentTypeByLabel($label),
						'Value' => $value
					);
					break;
				case 'passport_expiry':
				case 'dob':
					$typedValue = array(
						'DataType' => self::MFILES_DATATYPE_DATE,
						'Value' => $value
					);
					break;
				case 'title':
				case 'nationality':
				case 'occupation':
				case 'source_of_income':
				case 'pay_using':
					$functionName = '_lookup' . str_replace('_', '', ucwords($label, '_'));
					$typedValue = array(
						'DataType' => self::MFILES_DATATYPE_LOOKUP,
						'Lookup' => array('Item' => $this->$functionName($value)),
						'Lookups' => null
					);
					break;
			}
		}
		return $typedValue;
	}

	private function _lookupNationality($value) {
		$lookupTable = array_flip(array(
			1 => 'Afghan',
			2 => 'Albanian',
			3 => 'Algerian',
			4 => 'American',
			5 => 'Andorran',
			6 => 'Angolan',
			7 => 'Anguillan',
			8 => 'Argentine',
			9 => 'Armenian',
			10 => 'Australian',
			11 => 'Austrian',
			12 => 'Azerbaijani',
			13 => 'Bahamian',
			14 => 'Bahraini',
			15 => 'Bangladeshi',
			16 => 'Barbadian',
			17 => 'Belarusian',
			18 => 'Belgian',
			19 => 'Belizean',
			20 => 'Beninese',
			21 => 'Bermudian',
			22 => 'Bhutanese',
			23 => 'Bolivian',
			24 => 'Botswanan',
			25 => 'Brazilian',
			26 => 'British',
			27 => 'British Virgin Islander',
			28 => 'Bruneian',
			29 => 'Bulgarian',
			30 => 'Burkinan',
			31 => 'Burmese',
			32 => 'Burundian',
			33 => 'Cambodian',
			34 => 'Cameroonian',
			35 => 'Canadian',
			36 => 'Cape Verdean',
			37 => 'Cayman Islander',
			38 => 'Central African',
			39 => 'Chadian',
			40 => 'Chilean',
			41 => 'Chinese',
			42 => 'Citizen of Antigua and Barbuda',
			43 => 'Citizen of Bosnia and Herzegovina',
			44 => 'Citizen of Guinea-Bissau',
			45 => 'Citizen of Kiribati',
			46 => 'Citizen of Seychelles',
			47 => 'Citizen of the Dominican Republic',
			48 => 'Citizen of Vanuatu',
			49 => 'Colombian',
			50 => 'Comoran',
			51 => 'Congolese (Congo)',
			52 => 'Congolese (DRC)',
			53 => 'Cook Islander',
			54 => 'Costa Rican',
			55 => 'Croatian',
			56 => 'Cuban',
			57 => 'Cymraes',
			58 => 'Cymro',
			59 => 'Cypriot',
			60 => 'Czech',
			61 => 'Danish',
			62 => 'Djiboutian',
			63 => 'Dominican',
			64 => 'Dutch',
			65 => 'East Timorese',
			66 => 'Ecuadorean',
			67 => 'Egyptian',
			68 => 'Emirati',
			69 => 'English',
			70 => 'Equatorial Guinean',
			71 => 'Eritrean',
			72 => 'Estonian',
			73 => 'Ethiopian',
			74 => 'Faroese',
			75 => 'Fijian',
			76 => 'Filipino',
			77 => 'Finnish',
			78 => 'French',
			79 => 'Gabonese',
			80 => 'Gambian',
			81 => 'Georgian',
			82 => 'German',
			83 => 'Ghanaian',
			84 => 'Gibraltarian',
			85 => 'Greek',
			86 => 'Greenlandic',
			87 => 'Grenadian',
			88 => 'Guamanian',
			89 => 'Guatemalan',
			90 => 'Guinean',
			91 => 'Guyanese',
			92 => 'Haitian',
			93 => 'Honduran',
			94 => 'Hong Konger',
			95 => 'Hungarian',
			96 => 'Icelandic',
			97 => 'Indian',
			98 => 'Indonesian',
			99 => 'Iranian',
			100 => 'Iraqi',
			101 => 'Irish',
			102 => 'Israeli',
			103 => 'Italian',
			104 => 'Ivorian',
			105 => 'Jamaican',
			106 => 'Japanese',
			107 => 'Jordanian',
			108 => 'Kazakh',
			109 => 'Kenyan',
			110 => 'Kittitian',
			111 => 'Kosovan',
			112 => 'Kuwaiti',
			113 => 'Kyrgyz',
			114 => 'Lao',
			115 => 'Latvian',
			116 => 'Lebanese',
			117 => 'Liberian',
			118 => 'Libyan',
			119 => 'Liechtenstein citizen',
			120 => 'Lithuanian',
			121 => 'Luxembourger',
			122 => 'Macanese',
			123 => 'Macedonian',
			124 => 'Malagasy',
			125 => 'Malawian',
			126 => 'Malaysian',
			127 => 'Maldivian',
			128 => 'Malian',
			129 => 'Maltese',
			130 => 'Marshallese',
			131 => 'Martiniquais',
			132 => 'Mauritanian',
			133 => 'Mauritian',
			134 => 'Mexican',
			135 => 'Micronesian',
			136 => 'Moldovan',
			137 => 'Monegasque',
			138 => 'Mongolian',
			139 => 'Montenegrin',
			140 => 'Montserratian',
			141 => 'Moroccan',
			142 => 'Mosotho',
			143 => 'Mozambican',
			144 => 'Namibian',
			145 => 'Nauruan',
			146 => 'Nepalese',
			147 => 'New Zealander',
			148 => 'Nicaraguan',
			149 => 'Nigerian',
			150 => 'Nigerien',
			151 => 'Niuean',
			152 => 'North Korean',
			153 => 'Northern Irish',
			154 => 'Norwegian',
			155 => 'Omani',
			156 => 'Pakistani',
			157 => 'Palauan',
			158 => 'Palestinian',
			159 => 'Panamanian',
			160 => 'Papua New Guinean',
			161 => 'Paraguayan',
			162 => 'Peruvian',
			163 => 'Pitcairn Islander',
			164 => 'Polish',
			165 => 'Portuguese',
			166 => 'Prydeinig',
			167 => 'Puerto Rican',
			168 => 'Qatari',
			169 => 'Romanian',
			170 => 'Russian',
			171 => 'Rwandan',
			172 => 'Salvadorean',
			173 => 'Sammarinese',
			174 => 'Samoan',
			175 => 'Sao Tomean',
			176 => 'Saudi Arabian',
			177 => 'Scottish',
			178 => 'Senegalese',
			179 => 'Serbian',
			180 => 'Sierra Leonean',
			181 => 'Singaporean',
			182 => 'Slovak',
			183 => 'Slovenian',
			184 => 'Solomon Islander',
			185 => 'Somali',
			186 => 'South African',
			187 => 'South Korean',
			188 => 'South Sudanese',
			189 => 'Spanish',
			190 => 'Sri Lankan',
			191 => 'St Helenian',
			192 => 'St Lucian',
			193 => 'Stateless',
			194 => 'Sudanese',
			195 => 'Surinamese',
			196 => 'Swazi',
			197 => 'Swedish',
			198 => 'Swiss',
			199 => 'Syrian',
			200 => 'Taiwanese',
			201 => 'Tajik',
			202 => 'Tanzanian',
			203 => 'Thai',
			204 => 'Togolese',
			205 => 'Tongan',
			206 => 'Trinidadian',
			207 => 'Tristanian',
			208 => 'Tunisian',
			209 => 'Turkish',
			210 => 'Turkmen',
			211 => 'Turks and Caicos Islander',
			212 => 'Tuvaluan',
			213 => 'Ugandan',
			214 => 'Ukrainian',
			215 => 'Uruguayan',
			216 => 'Uzbek',
			217 => 'Vatican citizen',
			218 => 'Venezuelan',
			219 => 'Vietnamese',
			220 => 'Vincentian',
			221 => 'Wallisian',
			222 => 'Welsh',
			223 => 'Yemeni',
			224 => 'Zambian',
			225 => 'Zimbabwean'
		));
		return $lookupTable[$value];
	}

	private function _lookupOccupation($value) {
		$lookupTable = array_flip(array(
			1 => 'Academical',
			2 => 'Accountant',
			3 => 'Actuary',
			4 => 'Administrator',
			5 => 'Advocate',
			6 => 'Allied Medical Service',
			7 => 'Airline Cabin Staff',
			8 => 'Ambassador',
			9 => 'Analyst',
			10 => 'Apprentice',
			11 => 'Architect',
			12 => 'Artisan',
			13 => 'Artist',
			14 => 'Assistant',
			15 => 'Attache For Embassy',
			16 => 'Attorney',
			17 => 'Banker',
			18 => 'Barber/Hairdresser'
		));
		return $lookupTable[$value];
	}

	private function _lookupPayUsing($value) {
		$lookupTable = array_flip(array(
			1 => 'Salary earner',
			2 => 'Self-employed',
			3 => 'Maintenance',
			4 => 'Commission',
			5 => 'Dividends',
			6 => 'Interest',
			7 => 'Court order',
			8 => 'Pension'
		));
		return $lookupTable[$value];
	}

	private function _lookupSourceOfIncome($value) {
		$lookupTable = array_flip(array(
			1 => 'Salary',
			2 => 'Pension',
			3 => 'Dividend',
			4 => 'Interest received',
			5 => 'Rent Received'
		));
		return $lookupTable[$value];
	}

	private function _lookupTitle($value) {
		$lookupTable = array_flip(array(
			1 => 'ADM',
			2 => 'ADV',
			3 => 'AG',
			4 => 'Father',
			5 => 'Dr',
			6 => 'Mr',
			7 => 'Mrs',
			8 => 'Ms',
			9 => 'Judge'
		));
		return $lookupTable[$value];
	}

	private function _processUpload($label, $value) {
		switch ($label) {
			case 'selfie':
				$folder = 'selfies';
				break;
			case 'id_front':
			case 'id_rear':
			case 'drivers_front':
			case 'drivers_rear':
				$folder = 'ids';
				break;
			case 'passport':
				$folder = 'passports';
				break;
			case 'passport':
				$folder = 'passports';
				break;
			case 'uploaded_file_1':
			case 'uploaded_file_2':
			case 'uploaded_file_3':
			case 'uploaded_file_4':
			case 'uploaded_file_5':
			case 'uploaded_file_6':
			case 'uploaded_file_7':
			case 'uploaded_file_8':
			case 'uploaded_file_9':
			case 'uploaded_file_10':
			case 'broker_declaration':
				$folder = 'uploads';
				break;
			default:
				$folder = 'edd';
				break;
		}
		$path = 'https://identitytoday.com.na';
		$payload = file_get_contents($path . '/assets/' . $folder . '/' . $value);
		$result = $this->socket->post(
			$this->settings['url'] . 'files',
			$payload,
			array('header' => array(
				'Content-Type' => 'application/octet-stream',
				'X-Authentication' => $this->setttings['X-Authentication'],
			))
		);
		$result = json_decode($result->body, true);
		return array($result['UploadID'], $result['Size']);
	}

	private function _transformToMFiles($label, $value) {
		$structure = array(
            'PropertyDef' => $this->_getPropertyDefByLabel($label),
            'TypedValue' => $this->_getTypedValueByLabelAndValue($label, $value)
		);
		return $structure;
	}
}

/*
case 'match':
	$result = 1040;
	break;
case 'match_confidence':
	$result = 1040;
	break;
case 'kairos_liveness_selfie':
	$result = 1040;
	break;
case 'id_liveness':
	$result = 1040;
	break;
case 'face_confidence':
	$result = 1040;
	break;
*/