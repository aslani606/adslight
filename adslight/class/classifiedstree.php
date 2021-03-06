<?php
/*
-------------------------------------------------------------------------
                     ADSLIGHT 2 : Module for Xoops

        Redesigned and ameliorate By Luc Bizet user at www.frxoops.org
        Started with the Classifieds module and made MANY changes
        Website : http://www.luc-bizet.fr
        Contact : adslight.translate@gmail.com
-------------------------------------------------------------------------
             Original credits below Version History
##########################################################################
#                    Classified Module for Xoops                         #
#  By John Mordo user jlm69 at www.xoops.org and www.jlmzone.com         #
#      Started with the MyAds module and made MANY changes               #
##########################################################################
 Original Author: Pascal Le Boustouller
 Author Website : pascal.e-xoops@perso-search.com
 Licence Type   : GPL
-------------------------------------------------------------------------
*/

class ClassifiedsTree
{
    var $table;
    var $id;
    var $pid;
    var $order;
    var $title;
    var $db;

function ClassifiedsTree($table_name, $id_name, $pid_name)
    {
        $this->db =& XoopsDatabaseFactory::getDatabaseConnection();
        $this->table = $table_name;
        $this->id = $id_name;
        $this->pid = $pid_name;
    }

function getFirstChild($sel_id, $order="")
    {
        $arr =array();
        $sql = 'SELECT SQL_CACHE * FROM '.$this->table.' WHERE '.$this->pid.'='.$sel_id.'';

        $categories = adslight_MygetItemIds('adslight_view');
        if (is_array($categories) && count($categories) > 0) {
        $sql .= ' AND '.$this->pid.' IN ('.implode(',', $categories).') ';
}

        if ($order != '') {
            $sql .= " ORDER BY $order";
        }

        $result = $this->db->query($sql);
        $count = $this->db->getRowsNum($result);
        if ($count==0) {
            return $arr;
        }
        while ( $myrow=$this->db->fetchArray($result) ) {
            array_push($arr, $myrow);
        }

        return $arr;
    }

function getFirstChildId($sel_id)
    {
        $idarray =array();
        $result = $this->db->query('SELECT SQL_CACHE '.$this->id.' FROM '.$this->table.' WHERE '.$this->pid.'='.mysql_real_escape_string($sel_id).'');

        $categories = adslight_MygetItemIds('adslight_view');
        if (is_array($categories) && count($categories) > 0) {
        $result .= ' AND '.$this->pid.' IN ('.implode(',', $categories).') ';
}

        $count = $this->db->getRowsNum($result);
        if ($count == 0) {
            return $idarray;
        }
        while ( list($id) = $this->db->fetchRow($result) ) {
            array_push($idarray, $id);
        }

        return $idarray;
    }

function getAllChildId($sel_id, $order='', $idarray = array())
    {
        $sql = 'SELECT SQL_CACHE '.$this->id.' FROM '.$this->table.' WHERE '.$this->pid.'='.mysql_real_escape_string($sel_id).'';

        $categories = adslight_MygetItemIds('adslight_view');
        if (is_array($categories) && count($categories) > 0) {
        $sql .= ' AND '.$this->pid.' IN ('.implode(',', $categories).') ';
}

        if ($order != '') {
            $sql .= ' ORDER BY '.$order.'';
        }
        $result=$this->db->query($sql);
        $count = $this->db->getRowsNum($result);
        if ($count==0) {
            return $idarray;
        }
        while ( list($r_id) = $this->db->fetchRow($result) ) {
            array_push($idarray, $r_id);
            $idarray = $this->getAllChildId($r_id,$order,$idarray);
        }

        return $idarray;
    }

function getAllParentId($sel_id, $order='', $idarray = array())
    {
        $sql = 'SELECT '.$this->pid.' FROM '.$this->table.' WHERE '.$this->id.'='.mysql_real_escape_string($sel_id).'';

        $categories = adslight_MygetItemIds('adslight_view');
        if (is_array($categories) && count($categories) > 0) {
        $sql .= ' AND '.$this->pid.' IN ('.implode(',', $categories).') ';
}

        if ($order != '') {
            $sql .= ' ORDER BY '.$order.'';
        }
        $result=$this->db->query($sql);
        list($r_id) = $this->db->fetchRow($result);
        if ($r_id == 0) {
            return $idarray;
        }
        array_push($idarray, $r_id);
        $idarray = $this->getAllParentId($r_id,$order,$idarray);

        return $idarray;
    }

function getPathFromId($sel_id, $title, $path='')
    {
        $result = $this->db->query('SELECT '.$this->pid.', '.$title.' FROM '.$this->table.' WHERE '.$this->id.'='.mysql_real_escape_string($sel_id).'');

        $categories = adslight_MygetItemIds('adslight_view');
        if (is_array($categories) && count($categories) > 0) {
        $result .= ' AND cid IN ('.implode(',', $categories).') ';
    }

        if ( $this->db->getRowsNum($result) == 0 ) {
            return $path;
        }
        list($parentid,$name) = $this->db->fetchRow($result);
        $myts =& MyTextSanitizer::getInstance();
        $name = $myts->htmlSpecialChars($name);
        $path = '/'.$name.$path.'';
        if ($parentid == 0) {
            return $path;
        }
        $path = $this->getPathFromId($parentid, $title, $path);

        return $path;
    }

function makeMySelBox($title,$order="",$preset_id=0, $none=0, $sel_name="", $onchange="")
    {
        if ($sel_name == '') {
            $sel_name = $this->id;
        }
        $myts =& MyTextSanitizer::getInstance();
        echo '<select name="'.$sel_name.'"';
        if ($onchange != "") {
            echo ' onchange="'.$onchange.'"';
        }
        echo '>';

    $sql = 'SELECT SQL_CACHE cid, title FROM '.$this->table.' WHERE pid=0';
    $categories = adslight_MygetItemIds('adslight_submit');

if (is_array($categories) && count($categories) > 0) {
    $sql .= ' AND cid IN ('.implode(',', $categories).') ';
    }
        if ($order != '') {
            $sql .= " ORDER BY $order";
        }

        $result = $this->db->query($sql);
        if ($none) {
            echo '<option value="0">----</option>';
        }
        while ( list($catid, $name) = $this->db->fetchRow($result) ) {
            $sel = "";
            if ($catid == $preset_id) {
                $sel = ' selected="selected"';
            }
            echo '<option value='.$catid.''.$sel.'>'.$name.'</option>';
            $sel = "";
            $arr = $this->getChildTreeArray($catid, $order);
            foreach ($arr as $option) {
                $option['prefix'] = str_replace('.','--',$option['prefix']);
                $catpath = $option['prefix'].'&nbsp;'.$myts->displayTarea($option[$title]);
                if ($option['cid'] == $preset_id) {
                    $sel = ' selected="selected"';
                }
                echo '<option value="'.$option['cid'].'"'.$sel.'>'.$catpath.'</option>';
                $sel = '';
            }
        }
        echo '</select>';
    }

function getNicePathFromId($sel_id, $title, $funcURL, $path="")
    {

        $sql = 'SELECT SQL_CACHE '.$this->pid.', '.$title.' FROM '.$this->table.' WHERE '.$this->id.'='.mysql_real_escape_string($sel_id).'';
        $result = $this->db->query($sql);
        if ( $this->db->getRowsNum($result) == 0 ) {
            return $path;
        }
        list($parentid,$name) = $this->db->fetchRow($result);
        $myts =& MyTextSanitizer::getInstance();
        $name = $myts->htmlSpecialChars($name);

        $arrow = '<img src="'.XOOPS_URL.'/modules/adslight/images/arrow.gif" alt="&raquo;" />';

        $path = '&nbsp;&nbsp;'.$arrow.'&nbsp;&nbsp;<a title="'._ADSLIGHT_ANNONCES.' '.$name.'" href="'.$funcURL.''.$this->id.'='.mysql_real_escape_string($sel_id).'">'.$name.'</a>'.$path.'';

        if ($parentid == 0) {
            return $path;
        }
        $path = $this->getNicePathFromId($parentid, $title, $funcURL, $path);

        return $path;
    }

function getIdPathFromId($sel_id, $path='')
    {
        $result = $this->db->query('SELECT SQL_CACHE '.$this->pid.' FROM '.$this->table.' WHERE '.$this->id.'='.mysql_real_escape_string($sel_id).'');
        if ( $this->db->getRowsNum($result) == 0 ) {
            return $path;
        }
        list($parentid) = $this->db->fetchRow($result);
        $path = '/'.$sel_id.$path.'';
        if ($parentid == 0) {
            return $path;
        }
        $path = $this->getIdPathFromId($parentid, $path);

        return $path;
    }

function getAllChild($sel_id=0,$order='',$parray = array())
    {
    $sql = 'SELECT SQL_CACHE * FROM '.$this->table.' WHERE '.$this->pid.'='.mysql_real_escape_string($sel_id).'';

    $categories = adslight_MygetItemIds('adslight_view');
    if (is_array($categories) && count($categories) > 0) {
    $sql .= ' AND '.$this->pid.' IN ('.implode(',', $categories).') ';
    }

        if ($order != '') {
            $sql .= ' ORDER BY '.$order.'';
        }

        $result = $this->db->query($sql);
        $count = $this->db->getRowsNum($result);
        if ($count == 0) {
            return $parray;
        }
        while ( $row = $this->db->fetchArray($result) ) {
            array_push($parray, $row);
            $parray=$this->getAllChild($row[$this->id],$order,$parray);
        }

        return $parray;
    }

function getChildTreeArray($sel_id=0,$order='',$parray = array(),$r_prefix='')
    {
    global $mydirname;

        $sql = 'SELECT SQL_CACHE * FROM '.$this->table.' WHERE '.$this->pid.'='.mysql_real_escape_string($sel_id).'';

        $categories = adslight_MygetItemIds('adslight_view');
if (is_array($categories) && count($categories) > 0) {
    $sql .= ' AND cid IN ('.implode(',', $categories).') ';
}

        if ($order != '') {
            $sql .= ' ORDER BY '.$order.'';
        }
        $result = $this->db->query($sql);
        $count = $this->db->getRowsNum($result);
        if ($count == 0) {
            return $parray;
        }
        while ( $row = $this->db->fetchArray($result) ) {
            $row['prefix'] = $r_prefix.'.';
            array_push($parray, $row);
            $parray = $this->getChildTreeArray($row[$this->id],$order,$parray,$row['prefix']);
        }

        return $parray;
    }

function makeAdSelBox($title,$order="",$preset_id=0, $none=0, $sel_name="", $onchange="")
{
        global  $xoopsModuleConfig, $myts, $xoopsDB, $pathIcon16;
        require XOOPS_ROOT_PATH.'/modules/adslight/include/gtickets.php';

        if ($sel_name == '') {
            $sel_name = $this->id;
        }

        $sql = 'select '.$this->id.', '.$title.', ordre FROM '.$this->table.' WHERE '.$this->pid.'=0';
        if ($order != '') {
            $sql .= ' ORDER BY '.$order.'';
        }
        $result = $xoopsDB->query($sql);
        while ( list($catid, $name, $ordre) = $xoopsDB->fetchRow($result) ) {

            echo '<table width="100%" border="0" class="outer"><tr>
                <th align="left">';
            if ($xoopsModuleConfig['adslight_csortorder'] == 'ordre') { echo '('.$ordre.')'; }
            echo '&nbsp;&nbsp;'.$name.'&nbsp;&nbsp;</th>
                <th align="center" width="10%"><a href="category.php?op=AdsNewCat&amp;cid='.addslashes($catid).'"><img src="'. $pathIcon16 .'/add.png' .'" border=0 width=18 height=18 alt="'._AM_ADSLIGHT_ADDSUBCAT.'" title="'._AM_ADSLIGHT_ADDSUBCAT.'"></a></th>
                <th align="center" width="10%"><a href="category.php?op=AdsModCat&amp;cid='.addslashes($catid).'"><img src="'. $pathIcon16 .'/edit.png' .'" border=0 width=18 height=18 alt="'._AM_ADSLIGHT_MODIFSUBCAT.'" title ="'._AM_ADSLIGHT_MODIFSUBCAT.'"></a></th>
                <th align="center" width="10%"><a href="category.php?op=AdsDelCat&amp;cid='.addslashes($catid).'"><img src="'. $pathIcon16 .'/delete.png' .'" border=0 width=18 height=18 alt="'._AM_ADSLIGHT_DELSUBCAT.'" title="'._AM_ADSLIGHT_DELSUBCAT.'"></a></th>
                </tr>';

            $arr = $this->getChildTreeMapArray($catid, $order);
            $class = 'odd';
            foreach ($arr as $option) {
               echo '<tr class="'.$class.'"><td>';

                $option['prefix'] = str_replace("."," &nbsp;&nbsp;-&nbsp;",$option['prefix']);
                $catpath = $option['prefix']."&nbsp;&nbsp;".$myts->htmlSpecialChars($option[$title]);
                $ordreS = $option['ordre'];
                if ($xoopsModuleConfig["adslight_csortorder"] == 'ordre') { echo '('.$ordreS.')'; }
                echo ''.$catpath.'</a></td>
                    <td align="center"><a href="category.php?op=AdsNewCat&amp;cid='.$option[$this->id].'"><img src="'. $pathIcon16 .'/add.png' .'" border=0 width=18 height=18 alt="'._AM_ADSLIGHT_ADDSUBCAT.'"title="'._AM_ADSLIGHT_ADDSUBCAT.'"></a></td>
                    <td align="center"><a href="category.php?op=AdsModCat&amp;cid='.$option[$this->id].'"><img src="'. $pathIcon16 .'/edit.png' .'" border=0 width=18 height=18 alt="'._AM_ADSLIGHT_MODIFSUBCAT.'" title ="'._AM_ADSLIGHT_MODIFSUBCAT.'"></a></td>
                    <td align="center"><a href="category.php?op=AdsDelCat&amp;cid='.$option[$this->id].'"><img src="'. $pathIcon16 .'/delete.png' .'" border=0 width=18 height=18 alt="'._AM_ADSLIGHT_DELSUBCAT.'" title="'._AM_ADSLIGHT_DELSUBCAT.'"></a></td>';

                $class = ($class == 'even') ? 'odd' : 'even';

            }
            echo '</td></tr></table><br />';
        }

    }

function getChildTreeMapArray($sel_id=0,$order="",$parray = array(),$r_prefix="")
{
        global $xoopsDB;
        $sql = 'select SQL_CACHE * FROM '.$this->table.' WHERE '.$this->pid.'='.mysql_real_escape_string($sel_id).'';

    $categories = adslight_MygetItemIds('adslight_view');
    if (is_array($categories) && count($categories) > 0) {
    $sql .= ' AND '.$this->pid.' IN ('.implode(',', $categories).') ';
    }

        if ($order != '') {
            $sql .= ' ORDER BY '.$order.'';
        }
        $result = $xoopsDB->query($sql);
        $count = $xoopsDB->getRowsNum($result);
        if ($count == 0) {
            return $parray;
        }
        while ( $row = $xoopsDB->fetchArray($result) ) {
            $row['prefix'] = $r_prefix.'.';
            array_push($parray, $row);
            $parray = $this->getChildTreeMapArray($row[$this->id],$order,$parray,$row['prefix']);
        }

        return $parray;
    }

function getCategoryList()
    {
        $result = $this->db->query('SELECT SQL_CACHE cid, pid, title FROM '.$this->table);
        $ret = array();
        $myts =& MyTextSanitizer::getInstance();
        while ($myrow = $this->db->fetchArray($result)) {
            $ret[$myrow['cid']] = array('title' => $myts->htmlspecialchars($myrow['title']), 'pid' => $myrow['pid']);
        }

        return $ret;
    }

}
