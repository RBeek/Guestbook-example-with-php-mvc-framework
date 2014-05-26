<?php
/**
 * Class and Function List:
 * Function list:
 * - __construct()
 * - onOpenId()
 * - getJobs()
 * - onId()
 * - newOpenId()
 * - openid_on_id()
 * - all()
 * Classes list:
 * - Users
 */

class Users
{
    const TBL_OPENID = 'ams_openid';
    const TBL_USERS = 'users';
    const TBL_PROFILES = 'ams_profiles';
    const TBL_MSGS = 'ams_berichten';
    const TBL_LOCATIONS = 'ams_locations';

    /**
     *
     *
     * @access	private
     */
    private $dba = null;

    public function __construct($dba = null)
    {
        $this->dba = (!is_null($dba)) ? $dba : new Dba(Db::getInstance());
    }

    /**
     * Lookup user with OpenID
     *
     * @param	int
     * @param	bool
     * @return	object
     */
    public function onOpenId($openid, $active = null)
    {
        $sql = "SELECT DISTINCT op.provider, g. * ,
				CONCAT( gp.firstname, ' ', gp.lastname ) AS volnaam, gp. *";
        $sql.= " FROM " . self::TBL_OPENID . " AS op
				INNER JOIN " . self::TBL_USERS . " AS g
				USING ( userid )
				INNER JOIN " . self::TBL_PROFILES . " AS gp
				USING ( userid )
				WHERE op.openid=:openid";

        $rows = $this->dba->dbJoin($sql, array(':openid' => $openid));

        if ($rows && !is_null($rows[0]['userid'])) {
            return $rows;
        }
        return FALSE;
    }

    public function getJobs($user_id)
    {
        $q = "SELECT *,jobid AS id FROM ams_jobs,ams_jobtypen WHERE userid='$user_id' AND (ams_jobs.jtypid=tbel_jobtypen.jtypid)";
        $q.= " ORDER BY betaald,jobid DESC";
        $rows = $this->dba->rawQuery($q);
        $items = $rows->fetchAll(PDO::FETCH_ASSOC);
        if ($items) {
            return $items;
        }
        return FALSE;
    }

    /**
     * Lookup user with Id
     *
     * @param	int
     * @param	bool
     * @return	object
     */
    public function onId($userid, $active = null)
    {
        $sql = "SELECT DISTINCT op.provider, g. * ,
				CONCAT( gp.firstname, ' ', gp.lastname ) AS volnaam, gp. *";
        $sql.= " FROM " . self::TBL_OPENID . " AS op
				INNER JOIN " . self::TBL_USERS . " AS g
				USING ( userid )
				INNER JOIN " . self::TBL_PROFILES . " AS gp
				USING ( userid )
				WHERE op.userid =:userid";

        $rows = $this->dba->dbJoin($sql, array(':userid' => $userid));

        return ($rows && !is_null($rows[0]['userid'])) ? $rows : FALSE;
    }

    public function newOpenId($values)
    {
        $lastInsertID = FALSE;
        $db = db::getInstance();

        //'email''laatste_ip' naam firstname referenceId

        $sql["user"] = "INSERT INTO " . self::TBL_USERS . " (email,active,registratie) VALUES(:a,:b,:c)";
        $sql["openid"] = "INSERT INTO " . self::TBL_OPENID . " (userid,openid,provider,email) VALUES(?,?,?,?)";
        $sql["profile"] = "INSERT INTO " . self::TBL_PROFILES . " (userid,firstname,lastname,referenceId) VALUES(:aa,:bb,:cc,:dd)";
        $db->beginTransaction();
        $referenceId = "44";
        try {
            foreach ($sql as $name => & $sql_cmd) {
                $stmt[$name] = $db->prepare($sql_cmd);
            }
            $firstname = ucfirst(strtolower($values['firstname']));
            $lastname = ucfirst(strtolower($values['naam']));
            $stmt["user"]->bindValue(':a', $values['email']);
            $stmt["user"]->bindValue(':b', $values['active'] ? 3 : 0);
            $stmt["user"]->bindValue(':c', date('Y-m-d H:i:s'));
            $stmt["user"]->execute();
            $lastInsertID = $db->lastInsertID();
            $stmt["openid"]->bindValue(1, $lastInsertID);
            $stmt["openid"]->bindValue(2, $values['openid']);
            $stmt["openid"]->bindValue(3, $values['provider']);
            $stmt["openid"]->bindValue(4, $values['email']);
            $stmt["openid"]->execute();
            $stmt["profile"]->bindValue(':aa', $lastInsertID);
            $stmt["profile"]->bindValue(':bb', $firstname);
            $stmt["profile"]->bindValue(':cc', $lastname);
            $stmt["profile"]->bindValue(':dd', $referenceId);
            $stmt["profile"]->execute();

            // $stmt["stats"]->bindValue(1,$lastInsertID);
            // $stmt["stats"]->bindValue(2,'1');
            // $stmt["stats"]->execute();
            $db->commit();
        }
        catch(Exception $e) {
            $db->rollback();

            //TODO Log errors

            return FALSE;
        }
        return $lastInsertID;
    }

    /*
     * Lookup openid on user ID
     *
    */
    public function openIdOnId($userid)
    {
        $rows = $this->dba->dbSelect(self::TBL_OPENID, "userid", $userid);
        return $rows ? $rows : FALSE;
    }

    public function all()
    {
        $q = "SELECT * FROM " . self::TBL_USERS;
        $rows = $this->dba->rawQuery($q);
        $rows = $rows->fetchAll(PDO::FETCH_ASSOC);
        return $rows ? $rows : FALSE;
    }
}
