<?php
// wcf imports
require_once(WCF_DIR.'lib/data/user/notification/object/AbstractNotificationObjectType.class.php');
require_once(WBB_DIR.'lib/data/post/PostDeleteNotificationObject.class.php');

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
class PostDeleteNotificationObjectType extends AbstractNotificationObjectType {
	 /**
         * @see NotificationObjectType::getObjectByID()
         */
        public function getObjectByID($objectID) {
                // get object
                $post = new PostDeleteNotificationObject($objectID);
                if (!$post->postID) return null;

                // return object
                return $post;
        }
	
	/**
         * @see NotificationObjectType::getObjectByObject()
         */
        public function getObjectByObject($object) {
		if ($object instanceof PostDeleteNotificationObject) {
			$post = $object;
		}
		else {
			$post = new PostDeleteNotificationObject($object->postID);
		}
		
                if (!$post->postID) return null;
		
                // return object
                return $post;
        }
	
	/**
         * @see NotificationObjectType::getObjectsByIDArray()
         */
        public function getObjectsByIDArray($objectIDArray) {
                $posts = array();
                $sql = "SELECT		*
			FROM 		wcf".WCF_N."_user_guestbook
			WHERE 		postID IN (".implode(',', $objectIDArray).")";
                $result = WCF::getDB()->sendQuery($sql);
                while ($row = WCF::getDB()->fetchArray($result)) {
                        $posts[$row['postID']] = new PostDeleteNotificationObject(null, $row);
                }
		
                return $posts;
        }
	
	/**
         * @see NotificationObjectType::getPackageID()
         */
        public function getPackageID() {
                return PACKAGE_ID;
        }
}
