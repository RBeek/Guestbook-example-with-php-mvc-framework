<?php
/**
* Class and Function List:
* Function list:
* - isValid()
* - __construct()
* - add()
* - error()
* - all()
* Classes list:
* - Guestbook
*/
class Guestbook
{

    const TBL_GUESTBOOK = 'guestbook';

    /**
     *
     *
     * @access  private
     */
    private $dba = null;
    private $error = array();
    private static $requirements = array();

    private function isValid($values)
    {
        if (!$values || count($values) !== 3) return false;
        $values['name'] = preg_replace('/[^a-zA-Z0-9 .-]/','',$values['name']);
        $values['comment']= filter_var($values['comment'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
        array_filter($values, 'trim');
        if(!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) return false;
        if(empty($values['name'])||empty($values['comment'])) return false;
        
        return $values;
    }

    public function __construct($dba = null)
    {
        $this->dba = (!is_null($dba)) ? $dba : new Dba(Db::getInstance());
    }

    public function add(array $values = array())
    {
        if (! $values = $this->isValid($values)){
/**

  TODO:
  - add flash message to session

**/

          return false;
        }
        $this->dba->dbInsert(self::TBL_GUESTBOOK, array($values));
    }

    public function error($first = true)
    {
        return $this->error ? ($first ? array_shift($this->error) : array_pop($this->error)) : false;
    }

    public function all()
    {
        $q = "SELECT * FROM " . self::TBL_GUESTBOOK;
        $rows = $this->dba->rawQuery($q);
        return $rows->fetchAll(PDO::FETCH_ASSOC);
    }
}
