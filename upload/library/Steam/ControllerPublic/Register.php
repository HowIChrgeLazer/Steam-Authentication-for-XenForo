<?php
/**
 * This file is part of Steam Authentication for XenForo
 *
 * Originally Written by Morgan Humes <morgan@lanaddict.com>
 * Copyright 2012 Morgan Humes
 *
 * Code updated by Michael Linback Jr. <webmaster@ragecagegaming.com>
 * Copyright 2014 Michael Linback Jr.
 * Website: http://ragecagegaming.com
 *
 * Steam Authentication for XenForo is free software: you can redistribute
 * it and/or modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * Steam Authentication for XenForo is distributed in the hope that it
 * will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with SteamProfile.  If not, see <http://www.gnu.org/licenses/>.
 *
 * Elements of OpenID code used from LightOpenID by Mewp
 * MIT License http://www.opensource.org/licenses/mit-license.php
 */

class Steam_ControllerPublic_Register extends XFCP_Steam_ControllerPublic_Register {

	const STEAM_LOGIN = 'https://steamcommunity.com/openid/login';
    private $ch = null;

	public function actionSteam() {
		$assocUserId = $this->_input->filterSingle('assoc', XenForo_Input::UINT);
		$redirect = $this->_input->filterSingle('redirect', XenForo_Input::STRING);

		$session = XenForo_Application::get('session');

		if($this->_input->filterSingle('reg', XenForo_Input::UINT)) {
			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				$this->_genUrl()
			);
		}

		// Validate Response
		$id = $this->_validate();
		if(empty($id)) {
			return $this->responseError('Error during authentication.  Please try again.');
		}

		$session->set('steam_id', $id);
		$userModel = $this->_getUserModel();
		$userExternalModel = $this->_getUserExternalModel();

		$stAssoc = $userExternalModel->getExternalAuthAssociation('steam', $id);
		if($stAssoc && $userModel->getUserById($stAssoc['user_id'])) {
			
			/** @var XenForo_ControllerHelper_Login $loginHelper */
			$loginHelper = $this->getHelper('Login');
			$loginHelper->tfaRedirectIfRequiredPublic($stAssoc['user_id'], $redirect, true);
            
			/* Cookies */
			XenForo_Visitor::setup($stAssoc['user_id']);
            XenForo_Application::get('session')->changeUserId($stAssoc['user_id']);
			$userModel->setUserRememberCookie($stAssoc['user_id']);

			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				$this->getDynamicRedirect(false, false)
			);
		}

		$existingUser = false;
		if(XenForo_Visitor::getUserId()) {
			$existingUser = XenForo_Visitor::getInstance();
		} else if($assocUserId) {
			$existingUser = $userModel->getUserById($assocUserId);
		}

		if($existingUser) {
			// must associate: matching user
			return $this->responseView('XenForo_ViewPublic_Register_Steam', 'register_steam', array(
				'associateOnly'	=> true,
				'existingUser'	=> $existingUser,
				'redirect'		=> $redirect
			));
		}

		if(!XenForo_Application::get('options')->get('registrationSetup', 'enabled')) {
			$this->_assertRegistrationActive();
		}

		$username = '';

        $sHelper = new Steam_Helper_Steam();
        $steamProfileAPI = $sHelper->getSteamProfileAPI($id);
        $json_object = $sHelper->getJsonData($steamProfileAPI);
		
        $json_decoded = json_decode($json_object);
		
        if(empty($json_decoded)) {
            return $this->responseError('Problem communicating with Steam Community. Please try your registration again.');
        }
        
		if(!empty($json_decoded)) {
			$username = $json_decoded->response->players[0]->personaname;
			
            if (!isset($json_decoded->response->players[0]->loccountrycode)) {
                $location = ''; //No location
			}
            
            if (isset($json_decoded->response->players[0]->loccountrycode)) {
                $location = $json_decoded->response->players[0]->loccountrycode;
                
                switch($location) {
                    case "AF": $location = "Afghanistan"; break;
                    case "AL": $location = "Albania"; break;
                    case "DZ": $location = "Algeria"; break;
                    case "AD": $location = "Andorra"; break;
                    case "AO": $location = "Angola"; break;
                    case "AG": $location = "Antigua and Barbuda"; break;
                    case "AR": $location = "Argentina"; break;
                    case "AM": $location = "Armenia"; break;
                    case "AU": $location = "Australia"; break;
                    case "AT": $location = "Austria"; break;
                    case "AZ": $location = "Azerbaijan"; break;
                    case "BS": $location = "The Bahamas"; break;
                    case "BH": $location = "Bahrain"; break;
                    case "BD": $location = "Bangladesh"; break;
                    case "BB": $location = "Barbados"; break;
                    case "BY": $location = "Belarus"; break;
                    case "BE": $location = "Belgium"; break;
                    case "BZ": $location = "Belize"; break;
                    case "BJ": $location = "Benin"; break;
                    case "BT": $location = "Bhutan"; break;
                    case "BO": $location = "Bolivia"; break;
                    case "BA": $location = "Bosnia and Herzegovina"; break;
                    case "BW": $location = "Botswana"; break;
                    case "BR": $location = "Brazil"; break;
                    case "BN": $location = "Brunei"; break;
                    case "BG": $location = "Bulgaria"; break;
                    case "BF": $location = "Burkina Faso"; break;
                    case "BI": $location = "Burundi"; break;
                    case "KH": $location = "Cambodia"; break;
                    case "CM": $location = "Cameroon"; break;
                    case "CA": $location = "Canada"; break;
                    case "CV": $location = "Cape Verde"; break;
                    case "CF": $location = "Central African Republic"; break;
                    case "TD": $location = "Chad"; break;
                    case "CL": $location = "Chile"; break;
                    case "CN": $location = "China"; break;
                    case "CO": $location = "Colombia"; break;
                    case "KM": $location = "Comoros"; break;
                    case "CG": $location = "Congo, Republic of the"; break;
                    case "CD": $location = "Congo, Democratic Republic of the"; break;
                    case "CR": $location = "Costa Rica"; break;
                    case "CI": $location = "Cote d'Ivoire"; break;
                    case "HR": $location = "Croatia"; break;
                    case "CU": $location = "Cuba"; break;
                    case "CY": $location = "Cyprus"; break;
                    case "CZ": $location = "Czech Republic"; break;
                    case "DK": $location = "Denmark"; break;
                    case "DJ": $location = "Djibouti"; break;
                    case "DM": $location = "Dominica"; break;
                    case "DO": $location = "Dominican Republic"; break;
                    case "TL": $location = "Timor-Leste"; break;
                    case "EC": $location = "Ecuador"; break;
                    case "EG": $location = "Egypt"; break;
                    case "SV": $location = "El Salvador"; break;
                    case "GQ": $location = "Equatorial Guinea"; break;
                    case "ER": $location = "Eritrea"; break;
                    case "EE": $location = "Estonia"; break;
                    case "ET": $location = "Ethiopia"; break;
                    case "FJ": $location = "Fiji"; break;
                    case "FI": $location = "Finland"; break;
                    case "FR": $location = "France"; break;
                    case "GA": $location = "Gabon"; break;
                    case "GM": $location = "Gambia"; break;
                    case "GE": $location = "Georgia"; break;
                    case "DE": $location = "Germany"; break;
                    case "GH": $location = "Ghana"; break;
                    case "GR": $location = "Greece"; break;
                    case "GD": $location = "Grenada"; break;
                    case "GT": $location = "Guatemala"; break;
                    case "GN": $location = "Guinea"; break;
                    case "GW": $location = "Guinea-Bissau"; break;
                    case "GY": $location = "Guyana"; break;
                    case "HT": $location = "Haiti"; break;
                    case "HN": $location = "Honduras"; break;
                    case "HU": $location = "Hungary"; break;
                    case "IS": $location = "Iceland"; break;
                    case "IN": $location = "India"; break;
                    case "ID": $location = "Indonesia"; break;
                    case "IR": $location = "Iran"; break;
                    case "IQ": $location = "Iraq"; break;
                    case "IE": $location = "Ireland"; break;
                    case "IL": $location = "Israel"; break;
                    case "IT": $location = "Italy"; break;
                    case "JM": $location = "Jamaica"; break;
                    case "JP": $location = "Japan"; break;
                    case "JO": $location = "Jordan"; break;
                    case "KZ": $location = "Kazakhstan"; break;
                    case "KE": $location = "Kenya"; break;
                    case "KI": $location = "Kiribati"; break;
                    case "KP": $location = "Korea, North"; break;
                    case "KR": $location = "Korea, South"; break;
                    case "ZZ": $location = "Kosovo"; break;
                    case "KW": $location = "Kuwait"; break;
                    case "KG": $location = "Kyrgyzstan"; break;
                    case "LA": $location = "Laos"; break;
                    case "LV": $location = "Latvia"; break;
                    case "LB": $location = "Lebanon"; break;
                    case "LS": $location = "Lesotho"; break;
                    case "LR": $location = "Liberia"; break;
                    case "LY": $location = "Libya"; break;
                    case "LI": $location = "Liechtenstein"; break;
                    case "LT": $location = "Lithuania"; break;
                    case "LU": $location = "Luxembourg"; break;
                    case "MK": $location = "Macedonia"; break;
                    case "MG": $location = "Madagascar"; break;
                    case "MW": $location = "Malawi"; break;
                    case "MY": $location = "Malaysia"; break;
                    case "MV": $location = "Maldives"; break;
                    case "ML": $location = "Mali"; break;
                    case "MT": $location = "Malta"; break;
                    case "MH": $location = "Marshall Islands"; break;
                    case "MR": $location = "Mauritania"; break;
                    case "MU": $location = "Mauritius"; break;
                    case "MX": $location = "Mexico"; break;
                    case "FM": $location = "Micronesia, Federated States of"; break;
                    case "MD": $location = "Moldova"; break;
                    case "MC": $location = "Monaco"; break;
                    case "MN": $location = "Mongolia"; break;
                    case "ME": $location = "Montenegro"; break;
                    case "MA": $location = "Morocco"; break;
                    case "MZ": $location = "Mozambique"; break;
                    case "MM": $location = "Myanmar (Burma)"; break;
                    case "NA": $location = "Namibia"; break;
                    case "NR": $location = "Nauru"; break;
                    case "NP": $location = "Nepal"; break;
                    case "NL": $location = "Netherlands"; break;
                    case "NZ": $location = "New Zealand"; break;
                    case "NI": $location = "Nicaragua"; break;
                    case "NE": $location = "Niger"; break;
                    case "NG": $location = "Nigeria"; break;
                    case "NO": $location = "Norway"; break;
                    case "OM": $location = "Oman"; break;
                    case "PK": $location = "Pakistan"; break;
                    case "PW": $location = "Palau"; break;
                    case "PA": $location = "Panama"; break;
                    case "PG": $location = "Papua New Guinea"; break;
                    case "PY": $location = "Paraguay"; break;
                    case "PE": $location = "Peru"; break;
                    case "PH": $location = "Philippines"; break;
                    case "PL": $location = "Poland"; break;
                    case "PT": $location = "Portugal"; break;
                    case "QA": $location = "Qatar"; break;
                    case "RO": $location = "Romania"; break;
                    case "RU": $location = "Russia"; break;
                    case "RW": $location = "Rwanda"; break;
                    case "KN": $location = "Saint Kitts and Nevis"; break;
                    case "LC": $location = "Saint Lucia"; break;
                    case "VC": $location = "Saint Vincent and the Grenadines"; break;
                    case "WS": $location = "Samoa"; break;
                    case "SM": $location = "San Marino"; break;
                    case "ST": $location = "Sao Tome and Principe"; break;
                    case "SA": $location = "Saudi Arabia"; break;
                    case "SN": $location = "Senegal"; break;
                    case "RS": $location = "Serbia"; break;
                    case "SC": $location = "Seychelles"; break;
                    case "SL": $location = "Sierra Leone"; break;
                    case "SG": $location = "Singapore"; break;
                    case "SK": $location = "Slovakia"; break;
                    case "SI": $location = "Slovenia"; break;
                    case "SB": $location = "Solomon Islands"; break;
                    case "SO": $location = "Somalia"; break;
                    case "ZA": $location = "South Africa"; break;
                    case "SS": $location = "South Sudan"; break;
                    case "ES": $location = "Spain"; break;
                    case "LK": $location = "Sri Lanka"; break;
                    case "SD": $location = "Sudan"; break;
                    case "SR": $location = "Suriname"; break;
                    case "SZ": $location = "Swaziland"; break;
                    case "SE": $location = "Sweden"; break;
                    case "CH": $location = "Switzerland"; break;
                    case "SY": $location = "Syria"; break;
                    case "TW": $location = "Taiwan"; break;
                    case "TJ": $location = "Tajikistan"; break;
                    case "TZ": $location = "Tanzania"; break;
                    case "TH": $location = "Thailand"; break;
                    case "TG": $location = "Togo"; break;
                    case "TO": $location = "Tonga"; break;
                    case "TT": $location = "Trinidad and Tobago"; break;
                    case "TN": $location = "Tunisia"; break;
                    case "TR": $location = "Turkey"; break;
                    case "TM": $location = "Turkmenistan"; break;
                    case "TV": $location = "Tuvalu"; break;
                    case "UG": $location = "Uganda"; break;
                    case "UA": $location = "Ukraine"; break;
                    case "AE": $location = "United Arab Emirates"; break;
                    case "GB": $location = "United Kingdom"; break;
                    case "US": $location = "United States of America"; break;
                    case "UY": $location = "Uruguay"; break;
                    case "UZ": $location = "Uzbekistan"; break;
                    case "VU": $location = "Vanuatu"; break;
                    case "VA": $location = "Vatican City (Holy See)"; break;
                    case "VE": $location = "Venezuela"; break;
                    case "VN": $location = "Vietnam"; break;
                    case "YE": $location = "Yemen"; break;
                    case "ZM": $location = "Zambia"; break;
                    case "ZW": $location = "Zimbabwe"; break;
                    default: $location = ''; //No location
                }
                
                if (isset($json_decoded->response->players[0]->locstatecode) 
                    && strcmp($location,'United States of America') == 0) {
                    $userstate = $json_decoded->response->players[0]->locstatecode;
                    switch($userstate) {
                        case "AL": $location = "Alabama, " . $location; break;
                        case "AK": $location = "Alaska, " . $location; break;
                        case "AZ": $location = "Arizona, " . $location; break;
                        case "AR": $location = "Arkansas, " . $location; break;
                        case "CA": $location = "California, " . $location; break;
                        case "CO": $location = "Colorado, " . $location; break;
                        case "CT": $location = "Connecticut, " . $location; break;
                        case "DE": $location = "Delaware, " . $location; break;
                        case "FL": $location = "Florida, " . $location; break;
                        case "GA": $location = "Georgia, " . $location; break;
                        case "HI": $location = "Hawaii, " . $location; break;
                        case "ID": $location = "Idaho, " . $location; break;
                        case "IL": $location = "Illinois, " . $location; break;
                        case "IN": $location = "Indiana, " . $location; break;
                        case "IA": $location = "Iowa, " . $location; break;
                        case "KS": $location = "Kansas, " . $location; break;
                        case "KY": $location = "Kentucky, " . $location; break;
                        case "LA": $location = "Louisiana, " . $location; break;
                        case "ME": $location = "Maine, " . $location; break;
                        case "MD": $location = "Maryland, " . $location; break;
                        case "MA": $location = "Massachusetts, " . $location; break;
                        case "MI": $location = "Michigan, " . $location; break;
                        case "MN": $location = "Minnesota, " . $location; break;
                        case "MS": $location = "Mississippi, " . $location; break;
                        case "MO": $location = "Missouri, " . $location; break;
                        case "MT": $location = "Montana, " . $location; break;
                        case "NE": $location = "Nebraska, " . $location; break;
                        case "NV": $location = "Nevada, " . $location; break;
                        case "NH": $location = "New Hampshire, " . $location; break;
                        case "NJ": $location = "New Jersey, " . $location; break;
                        case "NM": $location = "New Mexico, " . $location; break;
                        case "NY": $location = "New York, " . $location; break;
                        case "NC": $location = "North Carolina, " . $location; break;
                        case "ND": $location = "North Dakota, " . $location; break;
                        case "OH": $location = "Ohio, " . $location; break;
                        case "OK": $location = "Oklahoma, " . $location; break;
                        case "OR": $location = "Oregon, " . $location; break;
                        case "PA": $location = "Pennsylvania, " . $location; break;
                        case "RI": $location = "Rhode Island, " . $location; break;
                        case "SC": $location = "South Carolina, " . $location; break;
                        case "SD": $location = "South Dakota, " . $location; break;
                        case "TN": $location = "Tennessee, " . $location; break;
                        case "TX": $location = "Texas, " . $location; break;
                        case "UT": $location = "Utah, " . $location; break;
                        case "VT": $location = "Vermont, " . $location; break;
                        case "VA": $location = "Virginia, " . $location; break;
                        case "WA": $location = "Washington, " . $location; break;
                        case "WV": $location = "West Virginia, " . $location; break;
                        case "WI": $location = "Wisconsin, " . $location; break;
                        case "WY": $location = "Wyoming, " . $location; break;
                    }
                }
            }

		}

		$i = 2;
		$origName = $username;
		while($username != "" && $userModel->getUserByName($username)) {
			$username = "$origName $i";
			$i++;
		}

		return $this->responseView('XenForo_ViewPublic_Register_Steam', 'register_steam', array(
			'username'		=> $username,
			'redirect'		=> $redirect,
			'customFields'	=> $this->_getFieldModel()->prepareUserFields(
				$this->_getFieldModel()->getUserFields(array('registration' => true)),
				true
			),
			'timeZones'		=> XenForo_Helper_TimeZone::getTimeZones(),
			'tosUrl'		=> XenForo_Dependencies_Public::getTosUrl(),
			'location'		=> $location
		), $this->_getRegistrationContainerParams());
	}

	public function actionSteamRegister() {
		$this->_assertPostOnly();
		$session = XenForo_Application::get('session');

		if(!$session->get('steam_id')) {
			return $this->responseError('Lost Steam ID');
		}

		// Get User Profile Data
		$id = $session->get('steam_id');

        $sHelper = new Steam_Helper_Steam();
        $steamProfileAPI = $sHelper->getSteamProfileAPI($id);
        $json_object = $sHelper->getJsonData($steamProfileAPI);
		$json_decoded = json_decode($json_object);
		
		if(!empty($json_decoded)) {
			$username = $json_decoded->response->players[0]->personaname;
			$avatar = $json_decoded->response->players[0]->avatarfull;
		}

		$userModel = $this->_getUserModel();
		$userExternalModel = $this->_getUserExternalModel();

		$doAssoc = ($this->_input->filterSingle('associate', XenForo_Input::STRING) || $this->_input->filterSingle('force_assoc', XenForo_Input::UINT));

		if($doAssoc) {
        
            $userId = $this->_associateExternalAccount();
			$userExternalModel->updateExternalAuthAssociation('steam', $id, $userId);

			$this->updateUserStats($userId, $id);

			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				$this->getDynamicRedirect(false, false)
			);
		}

		$data = $this->_input->filter(array(
			'username'	=> XenForo_Input::STRING,
			'timezone'	=> XenForo_Input::STRING,
			'email'		=> XenForo_Input::STRING,
			'gender'	=> XenForo_Input::STRING,
			'location'	=> XenForo_Input::STRING,
			'dob_day'	=> XenForo_Input::UINT,
			'dob_month'	=> XenForo_Input::UINT,
			'dob_year'	=> XenForo_Input::UINT
		));

		if(XenForo_Dependencies_Public::getTosUrl() && !$this->_input->filterSingle('agree', XenForo_Input::UINT)) {
			return $this->responseError(new XenForo_Phrase('you_must_agree_to_terms_of_service'));
		}

		$options = XenForo_Application::get('options');

		$writer = XenForo_DataWriter::create('XenForo_DataWriter_User');
		if($options->registrationDefaults) {
			$writer->bulkSet($options->registrationDefaults, array('ignoreInvalidFields' => true));
		}
		$writer->bulkSet($data);

		$auth = XenForo_Authentication_Abstract::create('XenForo_Authentication_NoPassword');
		$writer->set('scheme_class', $auth->getClassName());
		$writer->set('data', $auth->generate(''), 'xf_user_authenticate');

		$writer->set('user_group_id', XenForo_Model_User::$defaultRegisteredGroupId);
		$writer->set('language_id', XenForo_Visitor::getInstance()->get('language_id'));

		$customFields = $this->_input->filterSingle('custom_fields', XenForo_Input::ARRAY_SIMPLE);
		$customFieldsShown = $this->_input->filterSingle('custom_fields_shown', XenForo_Input::STRING, array('array' => true));
		$writer->setCustomFields($customFields, $customFieldsShown);

		$writer->advanceRegistrationUserState(false);
		$writer->preSave();

		if($options->get('registrationSetup', 'requireDob')) {
			// dob required
			if(!$data['dob_day'] || !$data['dob_month'] || !$data['dob_year']) {
				$writer->error(new XenForo_Phrase('please_enter_valid_date_of_birth'), 'dob');
			} else {
				$userAge = $this->_getUserProfileModel()->getUserAge($writer->getMergedData(), true);
				if($userAge < 1) {

				} else if($userAge < intval($options->get('registrationSetup', 'minimumAge'))) {
					// TODO: set a cookie to prevent re-registration attempts
					// But I don't care
					$writer->error(new XenForo_Phrase('sorry_you_too_young_to_create_an_account'));
				}
			}
		}

		$writer->save();
		$user = $writer->getMergedData();

		if(!$options->steamAvatarReg) {
            unset($avatar);
        }
        
        if(!empty($avatar)) {
			$avatarFile = tempnam(XenForo_Helper_File::getTempDir(), 'xf');

			$httpClient = XenForo_Helper_Http::getClient(preg_replace('/\s+/', '%20', $avatar));
			$response = $httpClient->request('GET');
			if($response->isSuccessful()) {
				file_put_contents($avatarFile, $response->getBody());
			}
			// Apply Avatar
			try {  
				$user = array_merge($user, $this->getModelFromCache('XenForo_Model_Avatar')->applyAvatar($user['user_id'], $avatarFile));
			} catch (XenForo_Exception $e) {}

			@unlink($avatarFile);
		}
		
		$userExternalModel->updateExternalAuthAssociation('steam', $id, $user['user_id']);

		XenForo_Model_Ip::log($user['user_id'], 'user', $user['user_id'], 'register');
		
		/* Cookies */
		$userModel->setUserRememberCookie($user['user_id']);
		
		$session->changeUserId($user['user_id']);
		XenForo_Visitor::setup($user['user_id']);
		$this->updateUserStats($user['user_id'], $id);

		$redirect = $this->_input->filterSingle('redirect', XenForo_Input::STRING);

		$viewParams = array(
			'user'		=> $user,
			'redirect'	=> ($redirect ? XenForo_Link::convertUriToAbsoluteUri($redirect) : ''),
			'steam' => true
		);
		
		return $this->responseView(
			'XenForo_ViewPublic_Register_Process',
			'register_process',
			$viewParams,
			$this->_getRegistrationContainerParams()
		);
	}

	/**
	 * Generates URI to be used
	 */
	private function _genUrl() {
        $callbackUri = XenForo_Link::buildPublicLink('full:register/steam', false, array(
            'redirect' => $this->getDynamicRedirect()
        ));
        
        $host = $_SERVER['HTTP_HOST'];
        $trustRoot = (strpos($host, '://') ? $host : 'http://' . $host);
        if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
            && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')
        ) {
            $trustRoot = (strpos($host, '://') ? $host : 'https://' . $host);
        }

		$params = array(
			'openid.ns'			=> 'http://specs.openid.net/auth/2.0',
			'openid.mode'		=> 'checkid_setup',
			'openid.return_to'	=> $callbackUri,
			'openid.realm'		=> $trustRoot,
			'openid.identity'	=> 'http://specs.openid.net/auth/2.0/identifier_select',
			'openid.claimed_id'	=> 'http://specs.openid.net/auth/2.0/identifier_select'
		);

		return self::STEAM_LOGIN . '?' . http_build_query($params, '', '&');
	}

	/**
	 * Validates OpenId Returned Data
	 */
	protected function _validate(){
		if(empty($_GET['openid_assoc_handle'])) {
			return false;
		}

        if(function_exists('curl_version'))
		{
            
            $data = ($_SERVER['REQUEST_METHOD'] === 'POST') ? $_POST : $_GET;
            
            $params = array(
                    'openid.assoc_handle' => $data['openid_assoc_handle'],
                    'openid.signed'       => $data['openid_signed'],
                    'openid.sig'          => $data['openid_sig'],
            );
            
            $params['openid.ns'] = 'http://specs.openid.net/auth/2.0';
            
            foreach (explode(',', $data['openid_signed']) as $item)
            {
                $value = $data['openid_' . str_replace('.','_',$item)];
                $params['openid.' . $item] = function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc() ? stripslashes($value) : $value;
            }
            $params['openid.mode'] = 'check_authentication';
            
            $params = http_build_query($params, '', '&');
            
            /*
            $headercurl = array (
                "Accept-language: en",
                "Content-type: application/x-www-form-urlencoded",
                "Content-Length: " . strlen($data),
            );
            */
            
            $this->ch = curl_init(self::STEAM_LOGIN);
            $curl = curl_init(self::STEAM_LOGIN . ('GET' && $params ? '?' . $params : ''));
            curl_setopt($this->ch, CURLOPT_POST, true);
            curl_setopt($this->ch, CURLOPT_POSTFIELDS, $params);
            curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($this->ch, CURLOPT_HEADER, false);
            curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, false);
            //curl_setopt($this->ch, CURLOPT_HTTPHEADER, $headercurl);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array('Accept: application/xrds+xml, */*'));
            curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);
            $result = curl_exec($this->ch);
            curl_close( $this->ch );
		}
        else
		{
            
            // Start off with some basic params
            $params = array(
                'openid.assoc_handle'    => $_GET['openid_assoc_handle'],
                'openid.signed'          => $_GET['openid_signed'],
                'openid.sig'             => $_GET['openid_sig'],
                'openid.ns'              => 'http://specs.openid.net/auth/2.0',
            );

            // Get all the params that were sent back and resend them for validation
            $signed = explode(',', $_GET['openid_signed']);

            foreach($signed as $item) {
                $val = $_GET['openid_' . str_replace('.', '_', $item)];
                $params['openid.' . $item] = get_magic_quotes_gpc() ? stripslashes($val) : $val;
            }

            // Finally, add the all important mode.
            $params['openid.mode'] = 'check_authentication';

            // Stored to send a Content-Length header
            $data =  http_build_query($params);
            $context = stream_context_create(array(
                'http' => array(
                    'method'  => 'POST',
                    'header'  =>
                        "Accept-language: en\r\n".
                        "Content-type: application/x-www-form-urlencoded\r\n" .
                        "Content-Length: " . strlen($data) . "\r\n",
                    'content' => $data,
                ),
            ));
            
            $result=file_get_contents(self::STEAM_LOGIN, false, $context);
		}

		// Validate wheather it's true and if we have a good ID
		preg_match("#^https://steamcommunity.com/openid/id/([0-9]{17,25})#", $_GET['openid_claimed_id'], $matches);
		$steamID64 = is_numeric($matches[1]) ? $matches[1] : 0;

		// Return our final value
		return preg_match("#is_valid\s*:\s*true#i", $result) == 1 ? $steamID64 : '';
	}

	private function updateUserStats($userId, $steamId) {
        $options = XenForo_Application::get('options');
		$gamestatsreg = $options->steamGameStatsReg;
		if ($gamestatsreg > 0) {
            $db = XenForo_Application::get('db');
            $sHelper = new Steam_Helper_Steam();
            $games = $sHelper->getUserGames($steamId);
            foreach($games as $id => $data) {
                // game info
                $db->query("INSERT IGNORE INTO xf_steam_games(game_id, game_name, game_logo, game_link) 
                            VALUES($id, '{$data['name']}', '{$data['logo']}', '{$data['link']}');");

                // update
                $r = $db->fetchRow("SELECT * FROM xf_user_steam_games WHERE user_id = $userId AND game_id = $id;");
                if($r == NULL) {
                    // Insert
                    $db->insert("xf_user_steam_games", array('user_id'=>$userId, 'game_id'=>$id, 'game_hours'=>$data['hours'], 'game_hours_recent'=>$data['hours_recent']));
                } else {
                    // Update
                    $db->query("UPDATE xf_user_steam_games SET game_hours = {$data['hours']}, game_hours_recent = {$data['hours_recent']} 
                                WHERE user_id = $userId AND game_id = $id;");
                }
            }
		}
	}
}

?>
