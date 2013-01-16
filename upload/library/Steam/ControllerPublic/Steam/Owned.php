<?php

class Steam_ControllerPublic_Steam_Owned extends XenForo_ControllerPublic_Abstract
{
	public function actionIndex()
	{
		$sHelper = new Steam_Helper_Steam();
		$visitor = XenForo_Visitor::getInstance();
		
		if(!$visitor->hasPermission("SteamAuth", "view")){
			throw $this->getErrorOrNoPermissionResponseException('steam_do_not_have_permission');
		}
		else
		{
		$viewParams = array(
			'gameStats' => $sHelper->getGameStatistics()
		);

		return $this->responseView('Steam_ViewPublic_Owned', 'steam_public_owned', $viewParams);
		}
	}
	
	/**
	* Session activity details.
	* @see XenForo_Controller::getSessionActivityDetailsForList()
	*/
	public static function getSessionActivityDetailsForList(array $activities)
	{
		return new XenForo_Phrase('checking_out_steam_stats', array('steamUrl' => XenForo_Link::buildPublicLink('steam')));
	}    		
	
}
