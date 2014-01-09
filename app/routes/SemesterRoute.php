<?php
namespace RESTAPI;
use Request, SemesterData;

/**
 * @author  Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @license GPL 2 or later
 * @todo
 * @condition semester_id ^[0-9a-f]{32}$
 */
class SemesterRoute extends RouteMap
{
    /**
     * Returns a list of all semesters.
     *
     * @get /semesters
     */
    public function getSemesters()
    {
        $semesters = SemesterData::GetSemesterArray();
        return $this->paginated(array_slice($semesters, $this->offset, $this->limit), count($semesters));
    }

    /**
     * Returns a single semester.
     *
     * @get /semester/:semester_id
     */
    public function getSemester($id)
    {
        $temp = SemesterData::getInstance()->getSemesterData($id);
        if (!$temp) {
            throw new RouterException(404);
        }

        return array(
            'semester_id'    => $temp['semester_id'],
            'title'          => $temp['name'],
            'description'    => $temp['description'],
            'begin'          => $temp['beginn'],
            'end'            => $temp['ende'],
            'seminars_begin' => $temp['vorles_beginn'],
            'seminars_end'   => $temp['vorles_ende'],
        );
    }
}
