<?php
namespace RESTAPI\Routes;

class Blubber extends \RESTAPI\RouteMap
{

    /**
     * Some inclusions before the routes can be prcessed
     */
    public static function before()
    {
        require_once 'public/plugins_packages/core/Blubber/models/BlubberPosting.class.php';
        require_once 'public/plugins_packages/core/Blubber/models/BlubberStream.class.php';
        require_once 'public/plugins_packages/core/Blubber/models/BlubberUser.class.php';
    }

    /**
     * List blubber in a course
     *
     * @get /course/:course_id/blubber
     */
    public function getCourseBlubber($course_id)
    {
        if (!$GLOBALS['perm']->have_studip_perm("autor", $course_id)) {
            $this->error(401);
        }
        $stream  = \BlubberStream::getCourseStream($course_id);
        return $this->getStreamBlubberRestResource($stream, array("course_id" => $course_id));
    }

    /**
     * List blubber in a user's profile
     *
     * @get /user/:user_id/blubber
     */
    public function getProfileBlubber($user_id)
    {
        $stream  = \BlubberStream::getProfileStream($user_id);
        return $this->getStreamBlubberRestResource($stream, array("user_id" => $user_id));
    }

    /**
     * List blubber in a custom stream
     *
     * @get /user/:user_id/blubberstreams/:stream_id
     */
    public function getCustomStreamBlubber($user_id, $stream_id)
    {
        if ($stream_id === "global") {
            $stream = \BlubberStream::getGlobalStream();
        } else {
            $stream = new \BlubberStream($stream_id);
            if ($stream['user_id'] !== $GLOBALS['user']->id) {
                $this->error(401);
            }
        }
        return $this->getStreamBlubberRestResource($stream, array('user_id' => $user_id, 'stream_id' => $stream_id));
    }

    /**
     * Returns the rest resource of the stream regarding a stream a context-id and the parameters
     * stream-time, limit and offset (all in $this).
     * @param BlubberStream $stream : the stream
     * @param $course_id : a context-id of the stream for caching purposes
     * @return Array('collection' => array(...), 'pagination' => array())
     */
    public function getStreamBlubberRestResource($stream, $parameter) {
        $total   = $stream->fetchNumberOfThreads();
        $threads = $stream->fetchThreads((int) $this->offset, (int) $this->limit ?: null, $this->stream_time ?: null);

        $json = array();

        foreach ($threads as $thread) {
            $json[] = $thread->toRestResource();
        }

        $this->etag(md5(serialize($json)));

        return $this->paginated($json, $total, $parameter);
    }

    /**
     * Displays all data to a special blubber
     *
     * @get /blubber/:blubber_id
     * @param string blubber_id : id of any blubber (comment or thread)
     */
    public function getBlubberData($blubber_id) {
        $blubber = new \BlubberPosting($blubber_id);
        if (($blubber['context_type'] === "course" && !$GLOBALS['perm']->have_studip_perm("autor", $blubber['Seminar_id']))
            or ($blubber['context_type'] === "private" && !$blubber->isRelated())) {
            $this->error(401);
        }
        $json = $blubber->toRestResource();

        $this->etag(md5(serialize($json)));
        return $json;
    }

    /**
     * Create a new blubber. Parameters are blubbercontent, context_type, course_id, private_adressees
     *
     * @post /blubber
     *
     * @param string blubbercontent : content of the blubber. Can have {@@}mentions if you want.
     * @param string context_type : "public", "private" or "course". If set to "course" you need to define the parameter course_id.
     * @param string|null course_id : id of the seminar, the blubber should be in. Leave away if context_type is not "course".
     * @param array|null private_adressees : array of user_ids of people that should receive the private blubber. Remember that mentioned users will also see the private blubber, so it's your choice if the user should be mentioned or in this array. Leave blank if context_type is not "private".
     */
    public function createNewBlubber() {
        $blubber = new \BlubberPosting();
        $blubber['user_id'] = $GLOBALS['user']->id;
        $blubber['external_contact'] = 0;
        $blubber['author_host'] = $_SERVER['REMOTE_ADDR'];
        switch ($this->data['context_type']) {
            case "course":
                if ($this->course_id
                        && $GLOBALS['perm']->have_studip_perm("autor", $this->course_id)) {
                    $blubber['context_type'] = "course";
                    $blubber['seminar_id'] = $this->course_id;
                } else {
                    $this->error(401);
                }
                break;
            case "private":
                $blubber['context_type'] = "private";
                //relate users
                break;
            default:
            case "public":
                $blubber['context_type'] = "public";
                break;
        }
        $blubber->setId($blubber->getNewId());

        \BlubberPosting::$mention_posting_id = $blubber->getId();
        \StudipTransformFormat::addStudipMarkup("mention1", '@\"[^\n\"]*\"', "", "\BlubberPosting::mention");
        \StudipTransformFormat::addStudipMarkup("mention2", '@[^\s]*[\d\w_]+', "", "\BlubberPosting::mention");
        $content = \transformBeforeSave(\studip_utf8decode($this->data['blubbercontent']));
        $blubber['name'] = $blubber['description'] = $content;

        $blubber->store();

        if ($blubber['context_type'] === "private") {
            $statement = DBManager::get()->prepare(
                "INSERT IGNORE INTO blubber_mentions " .
                "SET user_id = :user_id, " .
                "topic_id = :thread_id, " .
                "mkdate = UNIX_TIMESTAMP() " .
            "");
            $statement->execute(array(
                'user_id' => $GLOBALS['user']->id,
                'thread_id' => $blubber->getId()
            ));
            foreach ($this->data['private_adressees'] as $user_id) {
                $statement->execute(array(
                    'user_id' => $user_id,
                    'thread_id' => $blubber->getId()
                ));
            }
        }
        $this->redirect('blubber/' . $blubber->getId(), 201, "ok");
    }

    /**
     * Returns all comments of the blubber starting with the newest.
     * Returns an empty array if blubber_id is from a comment.
     *
     * @get /blubber/:blubber_id/comments
     * @param string $blubber_id : id of the thread
     */
    public function getComments($blubber_id) {
        $thread = new \BlubberPosting($blubber_id);
        if (($thread['context_type'] === "course" && !$GLOBALS['perm']->have_studip_perm("autor", $thread['Seminar_id']))
            or ($thread['context_type'] === "private" && !$thread->isRelated())) {
            $this->error(401);
        }
        \BlubberPosting::$course_hashes = $thread['context_type'] === "course" ? $thread['Seminar_id'] : false;

        $comments = $thread->getChildren($this->offset, $this->limit);

        $json = array();

        foreach ($comments as $comment) {
            $json[] = $comment->toRestResource();
        }

        $this->etag(md5(serialize($json)));

        return $this->paginated($json, $thread->getNumberOfChildren(), array('blubber_id' => $blubber_id));
    }

    /**
     * Edits the content of a blubber. Sends a message of the change to the author, if the editing user is not
     * the author of the blubber, to inform him/her about the change.
     * If the content is empty the blubber is going to be deleted, because we don't want empty
     * blubber in the system.
     *
     * @put /blubber/:blubber_id
     * @param $blubber_id
     *
     * @param blubbercontent : new content for the blubber
     */
    public function editBlubber($blubber_id) {
        $blubber = new \BlubberPosting($blubber_id);
        if (($blubber['context_type'] === "course" && !$GLOBALS['perm']->have_studip_perm("tutor", $blubber['Seminar_id']))
            or ($blubber['user_id'] === $GLOBALS['user']->id && !$blubber['external_contact'])) {
            $this->error(401);
        }
        $old_content = $blubber['description'];

        \BlubberPosting::$mention_posting_id = $blubber->getId();
        \StudipTransformFormat::addStudipMarkup("mention1", '@\"[^\n\"]*\"', "", "\BlubberPosting::mention");
        \StudipTransformFormat::addStudipMarkup("mention2", '@[^\s]*[\d\w_]+', "", "\BlubberPosting::mention");
        $content = \transformBeforeSave(\studip_utf8decode($this->data['blubbercontent']));
        $blubber['name'] = $blubber['description'] = $content;

        if ($blubber['description']) {
            if ($blubber['user_id'] !== $GLOBALS['user']->id) {
                $messaging = new \messaging();
                setTempLanguage($blubber['user_id']);
                $messaging->insert_message(
                    sprintf(
                        _("%s hat als Moderator gerade Ihren Beitrag im Blubberforum editiert.\n\nDie alte Version des Beitrags lautete:\n\n%s\n\nDie neue lautet:\n\n%s\n"),
                        get_fullname(), $old_content, $blubber['description']
                    ),
                    get_username($blubber['user_id']),
                    $GLOBALS['user']->id,
                    null, null, null, null,
                    _("�nderungen an Ihrem Posting.")
                );
                restoreLanguage();
            }
        } else {
            if ($blubber['user_id'] !== $GLOBALS['user']->id) {
                setTempLanguage($blubber['user_id']);
                $messaging = new \messaging();
                $messaging->insert_message(
                    sprintf(
                        _("%s hat als Moderator gerade Ihren Beitrag im Blubberforum GEL�SCHT.\n\nDer alte Beitrag lautete:\n\n%s\n"),
                        get_fullname(), $old_content
                    ),
                    get_username($blubber['user_id']),
                    $GLOBALS['user']->id,
                    null, null, null, null,
                    _("Ihr Posting wurde gel�scht.")
                );
                restoreLanguage();
            }
            $blubber->delete();
        }
        $this->status(204);
    }

    /**
     * Deletes the blubber and informs the author of the blubber if
     * the current user is not the author of the blubber.
     *
     * @delete /blubber/:blubber_id
     * @param $blubber_id
     */
    public function deleteBlubber($blubber_id) {
        $blubber = new \BlubberPosting($blubber_id);
        if (($blubber['context_type'] === "course" && !$GLOBALS['perm']->have_studip_perm("tutor", $blubber['Seminar_id']))
            or ($blubber['user_id'] === $GLOBALS['user']->id && !$blubber['external_contact'])) {
            $this->error(401);
        }

        if ($blubber['user_id'] !== $GLOBALS['user']->id) {
            setTempLanguage($blubber['user_id']);
            $messaging = new \messaging();
            $messaging->insert_message(
                sprintf(
                    _("%s hat als Moderator gerade Ihren Beitrag im Blubberforum GEL�SCHT.\n\nDer alte Beitrag lautete:\n\n%s\n"),
                    get_fullname(), $blubber['description']
                ),
                get_username($blubber['user_id']),
                $GLOBALS['user']->id,
                null, null, null, null,
                _("Ihr Posting wurde gel�scht.")
            );
            restoreLanguage();
        }
        $blubber->delete();
        $this->status(204);
    }
}
