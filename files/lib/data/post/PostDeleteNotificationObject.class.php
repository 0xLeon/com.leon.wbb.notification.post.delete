<?php
// wcf imports
require_once(WCF_DIR.'lib/data/user/notification/object/NotificationObject.class.php');

// wbb imports
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
		$message = $this->getFormattedMessage('text/plain');
		$message = StringUtil::stripHTML($message);
		$message = StringUtil::trim($message);
		
		if (StringUtil::length($message) > 100) {
			$message = StringUtil::substring($message, 0, 97) . '...';
		}
		
		if (empty($message)) {
			$message = '#'.$this->entryID;
		}
		
		return $message;
	}
	
	/**
	 * @see NotificationObject::getURL()
	 */
	public function getURL() {
		return 'index.php?page=Thread&postID='.$this->postID.'#post'.$this->postID;
	}
	
	/**
	 * @see NotificationObject::getIcon()
	 */
	public function getIcon() {
		return 'postTrash';
	}
	
	/**
	 * @see ViewablePost::getFormattedMessage()
	 */
	public function getFormattedMessage($outputType = 'text/html') {
		if ($outputType === 'text/html') {
			return parent::getFormattedMessage();
		}
		
                if ($outputType == 'text/plain') {
			$message = StringUtil::stripHTML($this->message);
		}
                else {
			$message = $this->message;
		}
		
		require_once(WCF_DIR.'lib/data/message/bbcode/MessageParser.class.php');
		MessageParser::getInstance()->setOutputType($outputType);
		return MessageParser::getInstance()->parse($message, $this->enableSmilies, $this->enableHtml, $this->enableBBCodes, false);
	}
}
