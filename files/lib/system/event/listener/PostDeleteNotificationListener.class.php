<?php
// wcf imports
require_once(WCF_DIR.'lib/system/event/EventListener.class.php');
require_once(WCF_DIR.'lib/data/user/notification/NotificationHandler.class.php');

// wbb imports
require_once(WBB_DIR.'lib/data/post/PostDeleteNotificationObject.class.php');
require_once(WBB_DIR.'lib/data/post/PostEditor.class.php');
require_once(WBB_DIR.'lib/data/thread/ThreadEditor.class.php');

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
		if (MODULE_USER_NOTIFICATION) {
			if ($className === 'PostActionPage') {
				$markedPostIDs = WCF::getSession()->getVar('markedPosts');
				
				if (($eventObj->post !== null) && ($eventObj->post->userID != WCF::getUser()->userID)) {
					if ($eventObj->action === 'trash') {
						if (!THREAD_ENABLE_RECYCLE_BIN || !$eventObj->board->getModeratorPermission('canDeletePost') || $eventObj->post->isDeleted) {
							return;
						}
						
						NotificationHandler::fireEvent('trashed', 'postDelete', $eventObj->post->postID, $eventObj->post->userID, array(
							'trashedByUserID' => WCF::getUser()->userID,
							'trashedByUsername' => WCF::getUser()->username,
							'trashReason' => $eventObj->reason,
							'threadID' => $eventObj->thread->threadID,
							'threadTopic' => $eventObj->thread->topic
						));
						
						return true;
					}
					else if ($eventObj->action === 'delete') {
						if (!$eventObj->board->getModeratorPermission('canDeletePostCompletely')) {
							return;
						}
						
						NotificationHandler::revokeEvent(array('trashed'), 'postDelete', $eventObj->post->postID);
						NotificationHandler::fireEvent('deleted', 'postDelete', $eventObj->post->postID, $eventObj->post->userID, array(
							'deletedByUserID' => WCF::getUser()->userID,
							'deletedByUsername' => WCF::getUser()->username,
							'threadID' => $eventObj->thread->threadID,
							'threadTopic' => $eventObj->thread->topic
						));
						
						return true;
					}
					else if ($eventObj->action === 'recover') {
						if (!$eventObj->board->getModeratorPermission('canDeletePostCompletely') || !$eventObj->post->isDeleted) {
							return;
						}
						
						NotificationHandler::revokeEvent(array('trashed'), 'postDelete', $eventObj->post);
						
						return true;
					}
				}
				
				if (($markedPostIDs !== null) && count($markedPostIDs)) {
					if ($eventObj->action === 'deleteAll') {
						$trashPosts = array();
						$trashPostsThreadIDs = array();
						$deletePosts = array();
						$deletePostsThreadIDs = array();
						
						$sql = "SELECT		post.*, thread.threadID, thread.topic
							FROM		wbb".WBB_N."_post post
							LEFT JOIN	wbb".WBB_N."_thread thread
							ON		(post.threadID = thread.threadID)
							WHERE		post.postID IN (".implode(',', $markedPostIDs).")";
						$result = WCF::getDB()->sendQuery($sql);
						while ($row = WCF::getDB()->fetchArray($result)) {
							if ($row['userID'] != WCF::getUser()->userID) {
								if ($row['isDeleted'] || !THREAD_ENABLE_RECYCLE_BIN) {
									$deletePosts[$row['postID']] = new PostDeleteNotificationObject(null, $row);
									$deletePostsThreadIDs[] = $row['threadID'];
								}
								else {
									$trashPosts[$row['postID']] = new PostDeleteNotificationObject(null, $row);
									$trashPostsThreadIDs[] = $row['threadID'];
								}
							}
						}
						
						list($trashPostsBoards, $trashPostsBoardIDs) = ThreadEditor::getBoards(implode(',', $trashPostsThreadIDs));
						list($deletePostsBoards, $deletePostsBoardIDs) = ThreadEditor::getBoards(implode(',', $deletePostsThreadIDs));
						
						foreach ($trashPostsBoards as $trashPostsBoard) {
							$trashPostsBoard->checkModeratorPermission('canDeletePost');
						}
						
						foreach ($deletePostsBoards as $deletePostsBoard) {
							$deletePostsBoard->checkModeratorPermission('canDeletePostCompletely');
						}
						
						unset($trashPostsThreadIDs, $deletePostsThreadIDs, $trashPostsBoards, $deletePostsBoards, $trashPostsBoardIDs, $deletePostsBoardIDs);
						
						foreach ($trashPosts as $trashPost) {
							NotificationHandler::fireEvent('trashed', 'postDelete', $trashPost, $trashPost->userID, array(
								'trashedByUserID' => WCF::getUser()->userID,
								'trashedByUsername' => WCF::getUser()->username,
								'trashReason' => $eventObj->reason,
								'threadID' => $trashPost->threadID,
								'threadTopic' => $trashPost->topic
							));
						}
						
						foreach ($deletePosts as $deletePost) {
							NotificationHandler::revokeEvent(array('trashed'), 'postDelete', $deletePost);
							NotificationHandler::fireEvent('deleted', 'postDelete', $deletePost, $deletePost->userID, array(
								'deletedByUserID' => WCF::getUser()->userID,
								'deletedByUsername' => WCF::getUser()->username,
								'threadID' => $deletePost->threadID,
								'threadTopic' => $deletePost->topic
							));
						}
						
						return true;
					}
					else if ($eventObj->action === 'recoverAll') {
						$threadIDs = PostEditor::getThreadIDs(implode(',', $markedPostIDs));
						$notificationObjectObjects = NotificationHandler::getNotificationObjectTypeObject('postDelete')->getObjects($markedPostIDs);
						
						list($boards, $boardIDs) = ThreadEditor::getBoards($threadIDs);
						
						foreach ($boards as $board) {
							$board->checkModeratorPermission('canDeletePostCompletely');
						}
						
						unset($threadIDs, $boards, $boardIDs);
						
						foreach ($notificationObjectObjects as $notificationObjectObject) {
							NotificationHandler::revokeEvent(array('trashed'), 'postDelete', $notificationObjectObject);
						}
						
						return true;
					}
				}
			}
			else if ($className === 'ThreadPage') {
				$posts = $eventObj->postList->posts;
				$postIDs = array();
				$user = new NotificationUser(null, WCF::getUser(), false);
				$packageID = NotificationHandler::getNotificationObjectTypeObject('postDelete')->getPackageID();
				
				foreach ($posts as $post) {
					if ($post->isDeleted && ($post->userID == $user->userID)) {
						$postIDs[] = $post->postID;
					}
				}
				
				unset($posts);
				
				if (isset($user->notificationFlags[$packageID]) && ($user->notificationFlags[$packageID] > 0)) {
					$count = NotificationEditor::markConfirmedByObjectVisit($user->userID, array('trashed'), 'postDelete', $postIDs);
					$user->removeOutstandingNotification($packageID, $count);
				}
			}
			else if (($className === 'PostEditForm') && ($eventObj->post->userID != WCF::getUser()->userID) && isset($_POST['deletePost']) && isset($_POST['sure'])) {
				if ($eventObj->post->isDeleted && $eventObj->board->getModeratorPermission('canDeletePostCompletely')) {
					NotificationHandler::revokeEvent(array('trashed'), 'postDelete', $eventObj->post->postID);
					NotificationHandler::fireEvent('deleted', 'postDelete', $eventObj->post->postID, $eventObj->post->userID, array(
						'deletedByUserID' => WCF::getUser()->userID,
						'deletedByUsername' => WCF::getUser()->username,
						'threadID' => $eventObj->thread->threadID,
						'threadTopic' => $eventObj->thread->topic
					));
				}
				else if (!$eventObj->post->isDeleted && THREAD_ENABLE_RECYCLE_BIN && $eventObj->board->getModeratorPermission('canDeletePost')) {
					NotificationHandler::fireEvent('trashed', 'postDelete', $eventObj->post->postID, $eventObj->post->userID, array(
						'trashedByUserID' => WCF::getUser()->userID,
						'trashedByUsername' => WCF::getUser()->username,
						'trashReason' => $eventObj->deleteReason,
						'threadID' => $eventObj->thread->threadID,
						'threadTopic' => $eventObj->thread->topic
					));
				}
			}
		}
	}
}
