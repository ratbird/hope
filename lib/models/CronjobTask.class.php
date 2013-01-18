<?
/**
 * 
 */
class CronjobTask extends SimpleORMap
{
    /**
     *
     */
    public function __construct($id = null)
    {
        $this->db_table = 'cronjobs_tasks';
        $this->has_many['schedules'] = array(
            'class_name' => 'CronjobSchedule',
            'on_delete'  => 'delete',
            'on_store'   => 'store'
        );

        $this->registerCallback('after_initialize', 'loadClass');

        parent::__construct($id);
        
//        $this->loadClass();
    }

    protected function loadClass()
    {
        if (!empty($this->class) && !class_exists($this->class)) {
            require_once $GLOBALS['STUDIP_BASE_PATH'] . '/' . $this->filename;
        }
    }

    /**
     * Returns whether the task is defined in the core system or via a plugin.
     *
     * @return bool True if task is defined in core system
     */
    public function isCore()
    {
        return strpos($this->filename, 'plugins_packages') === false;
    }

    /**
     * 
     */
    public function engage($last_result, $parameters = array())
    {
        $task = new $this->class;

        $task->setUp();
        $result = $task->execute($last_result, $parameters);
        $task->tearDown();

        return $result;
    }
    
    /**
     * Proxy the static methods "getDescription", "getName" and
     * "getParameters" from the task class.
     *
     * @param  String $field Field which should be accessed.
     * @return String Value of the method call
     */
    public function getValue($field)
    {
        if (in_array($field, words('description name parameters'))) {
            $method = 'get' . ucfirst($field);
            return call_user_func("{$this->class}::{$method}");
        }
        return parent::getValue($field);
    }
}