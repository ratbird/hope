<?php
/**
 * The sidebar supersedes the pretty static infobox of Stud.IP.
 *
 * @author  Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @license GPL 2 or later
 * @since   3.1
 */
class Sidebar extends WidgetContainer
{
    /**
     * Constructor, tries to automagically set the sidebar's title.
     */
    public function __construct()
    {
        parent::__construct();

        $this->setTitle();
    }

    /**
     * Contains an optional image for the container.
     */
    protected $image = false;
    protected $title = false;
    protected $context_avatar = null;

    /**
     * Set an image for the sidebar.
     *
     * @param String $image The image relative to assets/images
     */
    public function setImage($image)
    {
        $this->image = $image;
    }

    /**
     * Returns the image for the sidebar.
     *
     * @return mixed Either the previously set image or false if no image
     *               has been set.
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * Removes a previously set image.
     */
    public function removeImage()
    {
        $this->image = false;
    }

    /**
     * Set a title of the sidebar.
     *
     * @param String $title The title of the sidebar.
     */
    public function setTitle($title = true)
    {
        $this->title = $title;
    }

    /**
     * Returns the title of the sidebar.
     *
     * @return mixed Either the previously set title or false if no title has
     *               been set
     */
    public function getTitle()
    {
        if ($this->title === true) {
            $breadcrumbs = $this->getBreadCrumbs();
            if (count($breadcrumbs) >= 2) {
                $nav = array_slice($breadcrumbs, 1, 1);
                $nav = reset($nav);
                $this->title = $nav->getTitle();
            } else {
                $this->title = false;
            }
        }
        return $this->title;
    }

    /**
     * Removes a previously set title.
     */
    public function removeTitle()
    {
        $this->title = false;
    }

    /**
     * Sets an avatar as a context-indicator. For example in a course a course-
     * avatar will indicate which course teh user is navigating in.
     * @param Avatar $avatar : the avatar object of the context
     */
    public function setContextAvatar(Avatar $avatar)
    {
        $this->context_avatar = $avatar;
    }

    /**
     * Removes a previously set context-indicator.
     */
    public function removeContextAvatar()
    {
        $this->context_avatar = null;
    }


    /**
     * Renders the sidebar.
     * The sidebar will only be rendered if it actually contains any widgets.
     * It will use the template "sidebar.php" located at "templates/sidebar".
     * A notification is dispatched before and after the actual rendering
     * process.
     *
     * @return String The HTML code of the rendered sidebar.
     */
    public function render()
    {
        $content = '';

        if ($this->context_avatar === null) {
            $breadcrumbs = $this->getBreadCrumbs();
            $keys = array_keys($breadcrumbs);
            if (reset($keys) === 'course') {
                $course = Course::findCurrent();
                if ($course) {
                    if ($course->getSemClass()->offsetGet('studygroup_mode')) {
                        $avatar = StudygroupAvatar::getAvatar($course->id);
                    } else {
                        $avatar = CourseAvatar::getAvatar($course->id);
                    }
                } else {
                    $institute = Institute::findCurrent();
                    $avatar = InstituteAvatar::getAvatar($institute->id);
                }
                $this->setContextAvatar($avatar);
            }
        }

        NotificationCenter::postNotification('SidebarWillRender', $this);

        if ($this->hasWidgets()) {
            $template = $GLOBALS['template_factory']->open('sidebar/sidebar');
            $template->widgets = $this->widgets;
            $template->image   = $this->getImage();
            $template->title   = $this->getTitle();
            $template->avatar  = $this->context_avatar;
            $content = $template->render();
        }

        NotificationCenter::postNotification('SidebarDidRender', $this);

        return $content;
    }

    /**
     * Returns a breadcrumb path of the currently active navigation.
     *
     * @return Array List of currently active navigation items.
     */
    private function getBreadCrumbs($navigation = null)
    {
        if ($navigation === null) {
            $navigation = Navigation::getItem('/');
        }

        $breadcrumbs = array();
        foreach ($navigation as $idx => $nav) {
            if ($nav->isActive()) {
                $breadcrumbs[$idx] = $nav;
                if ($nav->activeSubnavigation()) {
                    $breadcrumbs = array_merge($breadcrumbs, $this->getBreadCrumbs($nav));
                }
            }
        }
        return $breadcrumbs;
    }
}