<?php
namespace API;

/**
 * @author  Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @author  <mlunzena@uos.de>
 * @license GPL 2 or later
 *
 * @condition id ^[a-f0-9]{32}$
 */
class FilesRoute extends RouteMap
{
    // Bootstrap:
    // - Check if file exists and if it is accessable
    // - Declare helper function "adjustFile"
    public function before($router, &$handler, &$parameters)
    {
        require_once 'lib/datei.inc.php';
    }

    /**********************************************/
    /*                                            */
    /* Files Routes                               */
    /*                                            */
    /**********************************************/


    /**
     * (Meta-)Daten einer Datei bzw. eines Ordners
     *
     * @get /file/:id
     */
    public function getFile($file_id) {

        // is it a file?
        if ($file = $this->loadFile($file_id)) {
            return self::fileToJSON($file);
        }

        // or is it a folder?
        else if ($folder = $this->loadFolder($file_id)) {
            return self::folderToJSON($folder);
        }

        $this->halt(404, "Not found.");
    }


    /**
     * Inhalte einer Datei
     *
     * @get /file/:id/content
     *
     * @see public/sendfile.php
     */
    public function getFileContent($file_id)
    {
        $file = $this->loadFile($file_id);

        if (!isset($file)) {
            $this->notFound("File not found");
        }

        if ($file->url) {
            $this->streamLinkedFile($file);
        }

        else {
            if (!file_exists($real_file = get_upload_file_path($file_id))) {
                $this->notFound();
            }

            TrackAccess($file_id, 'dokument');

            $this->lastModified($file->chdate);
            $this->sendFile($real_file, array('filename' => $file->getValue('filename')));
        }
    }

    /**
     * Datei bzw. Ordner erzeugen
     *
     * @post /files/:id
     */
    public function postFiles()
    {
        $this->error(501);
    }

    /**
     * Update einer Datei bzw. eines Ordners
     *
     * @put /file/:id
     */
    public function putFile($id) {
        $this->error(501);
    }

    /**
     * Löschen einer Datei bzw. eines Ordners
     *
     * @delete /file/:id
     */
    public function deleteFile($file_id) {
        $this->error(501);
    }

    /**
     * Dateien/Ordner einer Veranstaltung
     *
     * @get /course/:id/files
     */
    public function getCourseFiles($course_id) {

        $folders = \SimpleCollection::createFromArray(
            \StudipDocumentFolder::findBySeminar_id($course_id))->orderBy('name asc');

        // slice according to demanded pagination
        $this->paginate('/course/:id/files?offset=%u&limit=%u', count($folders));
        $folders = $folders->limit($this->offset, $this->limit);
        foreach ($folders as &$folder) {
            $folder = self::folderToJSON($folder, true);
        }

        return $this->collect($folders);
    }


    /**
     * Returns the file indicated by the $id if it exists otherwise
     * returns NULL. If the file exists, halt the router with a 403,
     * if the user does not have access.
     */
    private function loadFile($id)
    {
        $file = new \StudipDocument($id);

        // return NULL unless it exists
        if ($file->isNew()) {
            return null;
        }

        if (!$file->checkAccess($GLOBALS['user']->id)) {
            $this->error(401);
        }

        return $file;
    }


    private function loadFolder($id)
    {
        $folder = new \StudipDocumentFolder($id);

        // return NULL unless it exists
        if ($folder->isNew()) {
            return null;
        }

        $seminar_id = $folder->seminar_id;

        if (!self::isDocumentModuleActivated($seminar_id)) {
            $this->error(404, "Not found.");
        }

        if (!$GLOBALS['perm']->have_studip_perm('user', $seminar_id, $GLOBALS['user']->id)) {
            $this->error(401);
        }

        return $folder;
    }


    /**
     * Transforms file metadata to a filtered JSON set of attributes.
     */
    private static function fileToJSON($file) {

        $result = array('file_id' => $file->getValue('id'));
        foreach (words('range_id seminar_id name description mkdate chdate filename filesize downloads protected') as $word) {
            $result[$word] = $file->getValue($word);
        }

        $result['author'] = '/user/' . $file->getValue('user_id');
        $result['protected'] = !empty($result['protected']);
        $result['blob'] = '/file/' . $result['file_id'] . '/content';

        self::linkToCourseOrInst($result);

        return $result;
    }


    /**
     * Transforms folder metadata to a filtered JSON set of attributes.
     */
    private static function folderToJSON($folder, $shallow = false) {

        $result = array('folder_id' => $folder->getValue('id'));
        foreach (words('range_id seminar_id user_id name description mkdate chdate') as $word) {
            $result[$word] = $folder->getValue($word);
        }

        // transform user_id
        $result['author'] = '/user/'.$result['user_id'];
        unset($result['user_id']);

        self::linkToCourseOrInst($result);

        $result['permissions'] = $folder->getPermissions();

        $result['documents'] = self::loadDocuments($folder->id);

        if (!$shallow) {
            $result['folders']   = self::loadFolders($folder->id);
        }

        return $result;
    }

    // transform seminar_id
    private static function linkToCourseOrInst(&$result)
    {
        $type = get_object_type($result['seminar_id'], array('sem', 'inst'));

        if ($type === 'sem') {
            $result['course'] = '/course/'.$result['seminar_id'];
        } else if ($type === 'inst') {
            $result['institute'] = '/institute/'.$result['seminar_id'];;
        }
        unset($result['seminar_id']);
    }


    private static function loadFolders($range_id)
    {
        $query = "SELECT folder_id, name, mkdate, chdate, permission,
                         IFNULL(description, '') AS description
                   FROM folder
                   WHERE range_id IN (:range_id, MD5(CONCAT(:range_id, 'top_folder')))
                     AND permission > 0

                   UNION

                   SELECT DISTINCT folder_id, folder.name,
                                   IFNULL(folder.description, '') AS description,
                                   folder.mkdate, folder.chdate, folder.permission
                   FROM themen AS th
                   INNER JOIN folder ON (th.issue_id = folder.range_id)
                   WHERE th.seminar_id = :range_id AND folder.permission > 0

                   UNION

                   SELECT folder_id, folder.name,
                          IFNULL(folder.description, '') AS description,
                          folder.mkdate, folder.chdate, folder.permission
                   FROM statusgruppen sg
                   INNER JOIN statusgruppe_user AS sgu
                     ON (sg.statusgruppe_id = sgu.statusgruppe_id AND sgu.user_id = :user_id)
                   INNER JOIN folder ON (sgu.statusgruppe_id = folder.range_id)
                   WHERE sg.range_id = :range_id AND folder.permission > 0";
        $statement = \DBManager::get()->prepare($query);
        $statement->bindParam(':range_id', $range_id);
        $statement->bindParam(':user_id', $GLOBALS['user']->id);
        $statement->execute();
        $folders =  $statement->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($folders as &$folder) {
           $folder['permissions'] = self::permsToArray($folder['permission']);
           unset($folder['permission']);
        }

        return $folders;
    }

    private static function permsToArray($permission)
    {
        foreach (array(1=>'visible', 'writable', 'readable', 'extendable') as $bit => $perm) {
          if ($permission & $bit)
            $result[] = $perm;
        }
        return $result;
    }

    private static function loadDocuments($folder_id)
    {
        $files = \StudipDocument::findByFolderId($folder_id);
        $result = array();
        foreach ($files as $file) {
            $result[] = self::fileToJSON($file);
        }
        return $result;
    }


    private static function isDocumentModuleActivated($range_id)
    {
        // Documents is 2nd bit (0-based indexed!) in modules flag
        $query = "SELECT modules & (1 << 1) != 0 FROM seminare WHERE Seminar_id = ?";
        $statement = \DBManager::get()->prepare($query);
        $statement->execute(array($range_id));
        return $statement->fetchColumn();
    }


    private function streamLinkedFile($file)
    {
        $link_data = parse_link($file->getValue('url'));
        if ($link_data['response_code'] != 200) {
            $this->error(404, sprintf('File contents for file %s not found', $file->id));
        }

        $filename = $file->getValue('filename');
        $headers = array(
            "Content-Type"        => get_mime_type($filename),
            "Content-Disposition" => sprintf('attachment; filename="%s"', basename($filename))
        );

        if ($filesize = $link_data['Content-Length']) {
            $headers["Content-Length"] = $filesize;
        }

        $this->halt(200, $headers, function () use ($file) { readfile($file->url); });
    }
}
