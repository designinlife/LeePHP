<?php
namespace LeePHP\DB;

use LeePHP\Bootstrap;
use LeePHP\DB\DbPdo;

/**
 * Nested-Tree 多叉树管理类。
 *
 * @author Lei Lee <web.developer.network@gmail.com>
 * @version 1.1.0
 * @copyright (c) 2013-2014, Lei Lee
 */
class DbTree {
    const POSITION_BEFORE       = 'before';
    const POSITION_AFTER        = 'after';
    /**
     * Bootstrap object instance.
     *
     * @var Bootstrap
     */
    private $ctx          = NULL;
    private $_lftName     = 'lft';
    private $_rgtName     = 'rgt';
    private $_depthName   = 'depth';
    private $_tableName   = '';
    private $_primaryName = '';

    /**
     * 静态 DbTree 对象实例引用。
     *
     * @var DbTree
     */
    static private $instance = NULL;

    /**
     * 静态创建 DbTree (Singleton) 对象实例。
     * 
     * @param Bootstrap $ctx  指定 Bootstrap 上下文对象。
     * @param string $table   指定表名称。
     * @param string $primary 指定表主键字段名称。
     * @param string $lft
     * @param string $rgt
     * @param string $depth
     * @return DbTree
     */
    static function instance($ctx, $table, $primary, $lft = 'lft', $rgt = 'rgt', $depth = 'depth') {
        if (!self::$instance)
            self::$instance = new DbTree($ctx, $table, $lft, $rgt, $depth);

        return self::$instance;
    }

    /**
     * 构造函数。
     * 
     * @param Bootstrap $ctx  指定 Bootstrap 上下文对象。
     * @param string $table   指定表名称。
     * @param string $primary 指定表主键字段名称。
     * @param string $lft
     * @param string $rgt
     * @param string $depth
     */
    function __construct($ctx, $table, $primary, $lft = 'lft', $rgt = 'rgt', $depth = 'depth') {
        $this->ctx          = $ctx;
        $this->_tableName   = $table;
        $this->_primaryName = $primary;
        $this->_lftName     = $lft;
        $this->_rgtName     = $rgt;
        $this->_depthName   = $depth;
    }

    /**
     * 析构函数。
     */
    function __destruct() {
        unset($this->ctx);
    }

    /**
     * 创建一个新节点。
     *
     * @param int $id     指定父级节点 ID。
     * @param array $data 指定其它字段信息。
     * @return int        返回 LAST_INSERT_ID() 值。
     */
    function create($id, $data = array()) {
        $dNode = $this->getNodeInfo($id);

        if ($dNode == false)
            return false;

        list($leftId, $rightId, $depth) = $dNode;

        $data[$this->_lftName]   = $rightId;
        $data[$this->_rgtName]   = $rightId + 1;
        $data[$this->_depthName] = $depth + 1;

        $this->ctx->db->execute("UPDATE `" . $this->_tableName . "` SET `" . $this->_lftName . "` = (CASE WHEN `" . $this->_lftName . "` > " . $rightId . " THEN `" . $this->_lftName . "` + 2 ELSE `" . $this->_lftName . "` END), `" . $this->_rgtName . "` = (CASE WHEN `" . $this->_rgtName . "` >= " . $rightId . " THEN `" . $this->_rgtName . "` + 2 ELSE `" . $this->_rgtName . "` END) WHERE `" . $this->_rgtName . "` >= " . $rightId, NULL, DbPdo::SQL_TYPE_UPDATE);

        $fields = array();
        $vars   = array();
        foreach ($data as $key => $value) {
            $fields[] = "`" . $key . "`";
            $vars[]   = '?';
        }

        $auto_id = ( int ) $this->ctx->db->execute("INSERT INTO `" . $this->_tableName . "` (" . implode(', ', $fields) . ") VALUES(" . implode(', ', $vars) . ")", array_values($data), DbPdo::SQL_TYPE_INSERT);

        return $auto_id;
    }

    /**
     * Delete a node and all its child nodes.
     *
     * @param int $id
     */
    function delete($id) {
        $dNode = $this->getNodeInfo($id);

        if ($dNode == false)
            return false;

        list($leftId, $rightId) = $dNode;

        $this->ctx->db->execute("DELETE FROM `" . $this->_tableName . "` WHERE `" . $this->_lftName . "` BETWEEN " . $leftId . " AND " . $rightId, NULL, DbPdo::SQL_TYPE_DELETE);

        $deltaId = (($rightId - $leftId) + 1);

        $this->ctx->db->execute("UPDATE `" . $this->_tableName . "` SET `" . $this->_lftName . "` = (CASE WHEN `" . $this->_lftName . "` > " . $leftId . " THEN `" . $this->_lftName . "` - " . $deltaId . " ELSE `" . $this->_lftName . "` END), `" . $this->_rgtName . "` = (CASE WHEN `" . $this->_rgtName . "` > " . $leftId . " THEN `" . $this->_rgtName . "` - " . $deltaId . " ELSE `" . $this->_rgtName . "` END) WHERE `" . $this->_rgtName . "` > " . $rightId, NULL, DbPdo::SQL_TYPE_DELETE);

        return true;
    }

    /**
     * Moving node and all child node to another, as a subset.
     *
     * @param int $id
     * @param int $to_id
     * @return boolean
     */
    function changeAll($id, $to_id) {
        $dNode = $this->getNodeInfo($id);
        if ($dNode == false)
            return false;
        list($leftId, $rightId, $level) = $dNode;

        $dNode = $this->getNodeInfo($to_id);
        if ($dNode == false)
            return false;
        list($leftIdP, $rightIdP, $levelP) = $dNode;

        if ($id == $to_id || $leftId == $leftIdP || ($leftIdP >= $leftId && $leftIdP <= $rightId) || ($level == $levelP + 1 && $leftId > $leftIdP && $rightId < $rightIdP)) {
            return false;
        }

        if ($leftIdP < $leftId && $rightIdP > $rightId && $levelP < $level - 1) {
            $sql = 'UPDATE ' . $this->_tableName . ' SET ' . $this->_depthName . ' = CASE WHEN ' . $this->_lftName . ' BETWEEN ' . $leftId . ' AND ' . $rightId . ' THEN ' . $this->_depthName . sprintf('%+d', -($level - 1) + $levelP) . ' ELSE ' . $this->_depthName . ' END, ' . $this->_rgtName . ' = CASE WHEN ' . $this->_rgtName . ' BETWEEN ' . ($rightId + 1) . ' AND ' . ($rightIdP - 1) . ' THEN ' . $this->_rgtName . '-' . ($rightId - $leftId + 1) . ' ' . 'WHEN ' . $this->_lftName . ' BETWEEN ' . $leftId . ' AND ' . $rightId . ' THEN ' . $this->_rgtName . '+' . ((($rightIdP - $rightId - $level + $levelP) / 2) * 2 + $level - $levelP - 1) . ' ELSE ' . $this->_rgtName . ' END, ' . $this->_lftName . ' = CASE WHEN ' . $this->_lftName . ' BETWEEN ' . ($rightId + 1) . ' AND ' . ($rightIdP - 1) . ' THEN ' . $this->_lftName . '-' . ($rightId - $leftId + 1) . ' ' . 'WHEN ' . $this->_lftName . ' BETWEEN ' . $leftId . ' AND ' . $rightId . ' THEN ' . $this->_lftName . '+' . ((($rightIdP - $rightId - $level + $levelP) / 2) * 2 + $level - $levelP - 1) . ' ELSE ' . $this->_lftName . ' END ' . 'WHERE ' . $this->_lftName . ' BETWEEN ' . ($leftIdP + 1) . ' AND ' . ($rightIdP - 1);
        } elseif ($leftIdP < $leftId) {
            $sql = 'UPDATE ' . $this->_tableName . ' SET ' . $this->_depthName . ' = CASE WHEN ' . $this->_lftName . ' BETWEEN ' . $leftId . ' AND ' . $rightId . ' THEN ' . $this->_depthName . sprintf('%+d', -($level - 1) + $levelP) . ' ELSE ' . $this->_depthName . ' END, ' . $this->_lftName . ' = CASE WHEN ' . $this->_lftName . ' BETWEEN ' . $rightIdP . ' AND ' . ($leftId - 1) . ' THEN ' . $this->_lftName . '+' . ($rightId - $leftId + 1) . ' ' . 'WHEN ' . $this->_lftName . ' BETWEEN ' . $leftId . ' AND ' . $rightId . ' THEN ' . $this->_lftName . '-' . ($leftId - $rightIdP) . ' ELSE ' . $this->_lftName . ' END, ' . $this->_rgtName . ' = CASE WHEN ' . $this->_rgtName . ' BETWEEN ' . $rightIdP . ' AND ' . $leftId . ' THEN ' . $this->_rgtName . '+' . ($rightId - $leftId + 1) . ' ' . 'WHEN ' . $this->_rgtName . ' BETWEEN ' . $leftId . ' AND ' . $rightId . ' THEN ' . $this->_rgtName . '-' . ($leftId - $rightIdP) . ' ELSE ' . $this->_rgtName . ' END ' . 'WHERE (' . $this->_lftName . ' BETWEEN ' . $leftIdP . ' AND ' . $rightId . ' ' . 'OR ' . $this->_rgtName . ' BETWEEN ' . $leftIdP . ' AND ' . $rightId . ')';
        } else {
            $sql = 'UPDATE ' . $this->_tableName . ' SET ' . $this->_depthName . ' = CASE WHEN ' . $this->_lftName . ' BETWEEN ' . $leftId . ' AND ' . $rightId . ' THEN ' . $this->_depthName . sprintf('%+d', -($level - 1) + $levelP) . ' ELSE ' . $this->_depthName . ' END, ' . $this->_lftName . ' = CASE WHEN ' . $this->_lftName . ' BETWEEN ' . $rightId . ' AND ' . $rightIdP . ' THEN ' . $this->_lftName . '-' . ($rightId - $leftId + 1) . ' ' . 'WHEN ' . $this->_lftName . ' BETWEEN ' . $leftId . ' AND ' . $rightId . ' THEN ' . $this->_lftName . '+' . ($rightIdP - 1 - $rightId) . ' ELSE ' . $this->_lftName . ' END, ' . $this->_rgtName . ' = CASE WHEN ' . $this->_rgtName . ' BETWEEN ' . ($rightId + 1) . ' AND ' . ($rightIdP - 1) . ' THEN ' . $this->_rgtName . '-' . ($rightId - $leftId + 1) . ' ' . 'WHEN ' . $this->_rgtName . ' BETWEEN ' . $leftId . ' AND ' . $rightId . ' THEN ' . $this->_rgtName . '+' . ($rightIdP - 1 - $rightId) . ' ELSE ' . $this->_rgtName . ' END ' . 'WHERE (' . $this->_lftName . ' BETWEEN ' . $leftId . ' AND ' . $rightIdP . ' ' . 'OR ' . $this->_rgtName . ' BETWEEN ' . $leftId . ' AND ' . $rightIdP . ')';
        }

        $this->ctx->db->execute($sql, NULL, DbPdo::SQL_TYPE_UPDATE);

        return true;
    }

    /**
     * Change the location of two sibling nodes.
     *
     * @param int $id1
     * @param int $id2
     * @param string $position
     * @return boolean
     */
    function changePosition($id1, $id2, $position = 'after') {
        $dNode = $this->getNodeInfo($id1);
        if ($dNode == false)
            return false;
        list($leftId1, $rightId1, $level1) = $dNode;

        $dNode = $this->getNodeInfo($id2);
        if ($dNode == false)
            return false;
        list($leftId2, $rightId2, $level2) = $dNode;

        if ($level1 != $level2)
            return false;

        if ('before' == $position) {
            if ($leftId1 > $leftId2) {
                $sql = 'UPDATE ' . $this->_tableName . ' SET ' . $this->_rgtName . ' = CASE WHEN ' . $this->_lftName . ' BETWEEN ' . $leftId1 . ' AND ' . $rightId1 . ' THEN ' . $this->_rgtName . ' - ' . ($leftId1 - $leftId2) . ' ' . 'WHEN ' . $this->_lftName . ' BETWEEN ' . $leftId2 . ' AND ' . ($leftId1 - 1) . ' THEN ' . $this->_rgtName . ' +  ' . ($rightId1 - $leftId1 + 1) . ' ELSE ' . $this->_rgtName . ' END, ' . $this->_lftName . ' = CASE WHEN ' . $this->_lftName . ' BETWEEN ' . $leftId1 . ' AND ' . $rightId1 . ' THEN ' . $this->_lftName . ' - ' . ($leftId1 - $leftId2) . ' ' . 'WHEN ' . $this->_lftName . ' BETWEEN ' . $leftId2 . ' AND ' . ($leftId1 - 1) . ' THEN ' . $this->_lftName . ' + ' . ($rightId1 - $leftId1 + 1) . ' ELSE ' . $this->_lftName . ' END ' . 'WHERE ' . $this->_lftName . ' BETWEEN ' . $leftId2 . ' AND ' . $rightId1;
            } else {
                $sql = 'UPDATE ' . $this->_tableName . ' SET ' . $this->_rgtName . ' = CASE WHEN ' . $this->_lftName . ' BETWEEN ' . $leftId1 . ' AND ' . $rightId1 . ' THEN ' . $this->_rgtName . ' + ' . (($leftId2 - $leftId1) - ($rightId1 - $leftId1 + 1)) . ' ' . 'WHEN ' . $this->_lftName . ' BETWEEN ' . ($rightId1 + 1) . ' AND ' . ($leftId2 - 1) . ' THEN ' . $this->_rgtName . ' - ' . (($rightId1 - $leftId1 + 1)) . ' ELSE ' . $this->_rgtName . ' END, ' . $this->_lftName . ' = CASE WHEN ' . $this->_lftName . ' BETWEEN ' . $leftId1 . ' AND ' . $rightId1 . ' THEN ' . $this->_lftName . ' + ' . (($leftId2 - $leftId1) - ($rightId1 - $leftId1 + 1)) . ' ' . 'WHEN ' . $this->_lftName . ' BETWEEN ' . ($rightId1 + 1) . ' AND ' . ($leftId2 - 1) . ' THEN ' . $this->_lftName . ' - ' . ($rightId1 - $leftId1 + 1) . ' ELSE ' . $this->_lftName . ' END ' . 'WHERE ' . $this->_lftName . ' BETWEEN ' . $leftId1 . ' AND ' . ($leftId2 - 1);
            }
        }
        if ('after' == $position) {
            if ($leftId1 > $leftId2) {
                $sql = 'UPDATE ' . $this->_tableName . ' SET ' . $this->_rgtName . ' = CASE WHEN ' . $this->_lftName . ' BETWEEN ' . $leftId1 . ' AND ' . $rightId1 . ' THEN ' . $this->_rgtName . ' - ' . ($leftId1 - $leftId2 - ($rightId2 - $leftId2 + 1)) . ' ' . 'WHEN ' . $this->_lftName . ' BETWEEN ' . ($rightId2 + 1) . ' AND ' . ($leftId1 - 1) . ' THEN ' . $this->_rgtName . ' +  ' . ($rightId1 - $leftId1 + 1) . ' ELSE ' . $this->_rgtName . ' END, ' . $this->_lftName . ' = CASE WHEN ' . $this->_lftName . ' BETWEEN ' . $leftId1 . ' AND ' . $rightId1 . ' THEN ' . $this->_lftName . ' - ' . ($leftId1 - $leftId2 - ($rightId2 - $leftId2 + 1)) . ' ' . 'WHEN ' . $this->_lftName . ' BETWEEN ' . ($rightId2 + 1) . ' AND ' . ($leftId1 - 1) . ' THEN ' . $this->_lftName . ' + ' . ($rightId1 - $leftId1 + 1) . ' ELSE ' . $this->_lftName . ' END ' . 'WHERE ' . $this->_lftName . ' BETWEEN ' . ($rightId2 + 1) . ' AND ' . $rightId1;
            } else {
                $sql = 'UPDATE ' . $this->_tableName . ' SET ' . $this->_rgtName . ' = CASE WHEN ' . $this->_lftName . ' BETWEEN ' . $leftId1 . ' AND ' . $rightId1 . ' THEN ' . $this->_rgtName . ' + ' . ($rightId2 - $rightId1) . ' ' . 'WHEN ' . $this->_lftName . ' BETWEEN ' . ($rightId1 + 1) . ' AND ' . $rightId2 . ' THEN ' . $this->_rgtName . ' - ' . (($rightId1 - $leftId1 + 1)) . ' ELSE ' . $this->_rgtName . ' END, ' . $this->_lftName . ' = CASE WHEN ' . $this->_lftName . ' BETWEEN ' . $leftId1 . ' AND ' . $rightId1 . ' THEN ' . $this->_lftName . ' + ' . ($rightId2 - $rightId1) . ' ' . 'WHEN ' . $this->_lftName . ' BETWEEN ' . ($rightId1 + 1) . ' AND ' . $rightId2 . ' THEN ' . $this->_lftName . ' - ' . ($rightId1 - $leftId1 + 1) . ' ELSE ' . $this->_lftName . ' END ' . 'WHERE ' . $this->_lftName . ' BETWEEN ' . $leftId1 . ' AND ' . $rightId2;
            }
        }

        $this->ctx->db->execute($sql, NULL, DbPdo::SQL_TYPE_UPDATE);

        return true;
    }

    function setLftName($lftName) {
        $this->_lftName = $lftName;
        return $this;
    }

    function setRgtName($rgtName) {
        $this->_rgtName = $rgtName;
        return $this;
    }

    function setDepthName($depthName) {
        $this->_depthName = $depthName;
        return $this;
    }

    function setTableName($tableName) {
        $this->_tableName = $tableName;
        return $this;
    }

    /**
     * For a single node data.
     *
     * @param int $id
     * @return array
     */
    private function getNodeInfo($id) {
        $data = $this->ctx->db->fetch("SELECT `" . $this->_lftName . "`, `" . $this->_rgtName . "`, `" . $this->_depthName . "` FROM `" . $this->_tableName . "` WHERE `" . $this->_primaryName . "` = ?", array(
            ( int ) $id
        ));
        if ($data) {
            return array(
                ( int ) $data[$this->_lftName],
                ( int ) $data[$this->_rgtName],
                ( int ) $data[$this->_depthName]
            );
        }
        return false;
    }
}
