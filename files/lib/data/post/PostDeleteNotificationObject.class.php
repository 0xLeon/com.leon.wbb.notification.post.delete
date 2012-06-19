<?php
// wcf imports
require_once(WCF_DIR.'lib/data/user/notification/object/NotificationObject.class.php');
require_once(WBB_DIR.'lib/data/post/ViewablePost.class.php');

/**
 * An implementation of NotificationObjectType to support the usage of a post deletions as a notification object.
 *
 * @author	Stefan Hahn
 * @copyright	2012, Stefan Hahn
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.leon.wbb.notification.post.delete
 * @subpackage	data.post
 * @category 	Burning Board
 */
class PostDeleteNotificationObject extends ViewablePost implements NotificationObject {
	/**
	 * @see NotificationObject::getObjectID()
	 */
	public function getObjectID() {
		return $this->postID;
	}
	
	/**
	 * @see NotificationObject::getTitle()
	 */
	public function getTitle() {
		// return $this->getExcerpt();
	}
	
	/**
	 * @see NotificationObject::getURL()
	 */
	public function getURL() {
		return 'index.php?page=Thread&postID='+$this->getObjectID()+SID_ARG_2ND+'#post'+$this->getObjectID();
	}
	
	/**
	 * @see NotificationObject::getIcon()
	 */
	public function getIcon() {
		return 'guestbook';
	}
}
