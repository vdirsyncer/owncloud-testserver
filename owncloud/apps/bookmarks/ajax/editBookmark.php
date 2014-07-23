<?php

/**
* ownCloud - bookmarks plugin - edit bookmark script
*
* @author Golnaz Nilieh
* @copyright 2011 Golnaz Nilieh <golnaz.nilieh@gmail.com>
* 
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
* License as published by the Free Software Foundation; either 
* version 3 of the License, or any later version.
* 
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU AFFERO GENERAL PUBLIC LICENSE for more details.
*  
* You should have received a copy of the GNU Affero General Public
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
* 
*/

// Check if we are a user
OCP\JSON::checkLoggedIn();
OCP\JSON::callCheck();

OCP\JSON::checkAppEnabled('bookmarks');

// Check if it is a valid URL
if (filter_var($_POST['url'], FILTER_VALIDATE_URL) === FALSE) {
	OC_JSON::error();
	exit();
}

// If we go the dialog form submit
if(isset($_POST['url'])) {
	$title = isset($_POST['title']) ? $_POST['title'] : '';
	$tags = isset($_POST['item']['tags']) ? $_POST['item']['tags'] : array();
	$pub = isset($_POST['is_public']) ? true : false;

	if(isset($_POST['record_id']) && is_numeric($_POST['record_id']) ) { //EDIT
		$id = OC_Bookmarks_Bookmarks::editBookmark($_POST['record_id'], $_POST['url'], $_POST['title'], $tags, $_POST['description'], $pub);
	}
	else {
		if(isset($_POST['from_own'])) {
			$datas = OC_Bookmarks_Bookmarks::getURLMetadata($_POST['url']);
			if(isset($datas['title'])) $title = $datas['title'];
		}
		$id = OC_Bookmarks_Bookmarks::addBookmark($_POST['url'], $title, $tags, $_POST['description'], $pub);
	}
	$bm = OC_Bookmarks_Bookmarks::findOneBookmark($id);
	OCP\JSON::success(array('item'=>$bm));
	exit();
}
OC_JSON::error();
exit();
