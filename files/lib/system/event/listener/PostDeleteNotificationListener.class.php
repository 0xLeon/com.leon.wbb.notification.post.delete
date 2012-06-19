<?php
// wcf imports
require_once(WCF_DIR.'lib/system/event/EventListener.class.php');
require_once(WCF_DIR.'lib/data/user/notification/NotificationHandler.class.php');

/**
 * Handles the notification system regarding post deletion
 *
 * @author	Stefan Hahn
 * @copyright	2012, Stefan Hahn
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.leon.wbb.notification.post.delete
 * @subpackage	system.event.listener
 * @category 	Burning Board
 */
class PostDeleteNotificationListener implements EventListener {
	/**
         * @see EventListener::execute()
         */
        public function execute($eventObj, $className, $eventName) {
                if (MODULE_USER_NOTIFICATION && ($eventObj->post !== null)) {
			if ($eventObj->action === 'trash') {
				if (!THREAD_ENABLE_RECYCLE_BIN || !$eventObj->board->getModeratorPermission('canDeletePost') || $eventObj->post->isDeleted) {
					return;
				}
				
				NotificationHandler::fireEvent('trashed', 'postDelete', $eventObj->post->postID, $eventObj->post->userID);
			}
			else if ($eventObj->action === 'delete') {
				if (!$eventObj->board->checkModeratorPermission('canDeletePostCompletely')) {
					return;
				}
				
				NotificationHandler::fireEvent('deleted', 'postDelete', $eventObj->post->postID, $eventObj->post->userID);
			}
			else if ($eventObj->action === 'deleteAll') {
				
			}
			else if ($eventObj->action === 'recover') {
				if (!$eventObj->board->checkModeratorPermission('canDeletePostCompletely') || !$eventObj->post->isDeleted) {
					return;
				}
				
				NotificationHandler::revokeEvent(array('trashed'), 'postDelete', $eventObj->post);
			}
			else if ($eventObj->action === 'recoverAll') {
				
			}
		}
	}
}
