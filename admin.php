<?php
/**
 * DokuWiki Plugin sqlite (Admin Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Andreas Gohr <andi@splitbrain.org>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

if (!defined('DOKU_LF')) define('DOKU_LF', "\n");
if (!defined('DOKU_TAB')) define('DOKU_TAB', "\t");
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once(DOKU_PLUGIN.'admin.php');

class admin_plugin_sqlite extends DokuWiki_Admin_Plugin {

    function getInfo() {
        return confToHash(dirname(__FILE__).'plugin.info.txt');
    }

    function getMenuSort() { return 500; }
    function forAdminOnly() { return true; }

    function handle() {
    }

    function html() {
        global $ID;

        echo $this->locale_xhtml('intro');

        if($_REQUEST['db'] && checkSecurityToken()){

            echo '<h2>'.$this->getLang('db').' '.hsc($_REQUEST['db']).'</h2>';
            echo '<div class="level2">';

            echo '<ul>';
            echo '<li><div class="li"><a href="'.
                    wl($ID,array('do'     => 'admin',
                                 'page'   => 'sqlite',
                                 'db'     => $_REQUEST['db'],
                                 'version'=> $_REQUEST['version'],
                                 'sql'    => 'SELECT name,sql FROM sqlite_master WHERE type=\'table\' ORDER BY name',
                                 'sectok' => getSecurityToken())).
                 '">'.$this->getLang('table').'</a></div></li>';
            echo '<li><div class="li"><a href="'.
                    wl($ID,array('do'     => 'admin',
                                 'page'   => 'sqlite',
                                 'db'     => $_REQUEST['db'],
                                 'version'=> $_REQUEST['version'],
                                 'sql'    => 'SELECT name,sql FROM sqlite_master WHERE type=\'index\' ORDER BY name',
                                 'sectok' => getSecurityToken())).
                 '">'.$this->getLang('index').'</a></div></li>';
            echo '</ul>';

            $form = new Doku_Form(array('class'=>'sqliteplugin'));
            $form->startFieldset('SQL Command');
            $form->addHidden('id',$ID);
            $form->addHidden('do','admin');
            $form->addHidden('page','sqlite');
            $form->addHidden('db',$_REQUEST['db']);
            $form->addHidden('version', $_REQUEST['version']);
            $form->addElement('<textarea name="sql" class="edit">'.hsc($_REQUEST['sql']).'</textarea>');
            $form->addElement('<input type="submit" class="button" />');
            $form->endFieldset();
            $form->printForm();


            if($_REQUEST['sql']){

                /** @var $DBI helper_plugin_sqlite */
                $DBI =& plugin_load('helper', 'sqlite');
                if(!$DBI->init($_REQUEST['db'],'')) return;

                $sql = explode(";",$_REQUEST['sql']);
                foreach($sql as $s){
                    $s = preg_replace('!^\s*--.*$!m', '', $s);
                    $s = trim($s);
                    if(!$s) continue;
                    
                    $time_start = microtime(true);
                    
                    $res = $DBI->query("$s;");
                    if ($res === false) continue;

                    $result = $DBI->res2arr($res);

                    $time_end = microtime(true);
                    $time = $time_end - $time_start;
                    
                    $cnt = $DBI->res2count($res);
                    msg($cnt.' affected rows in '.($time<0.0001 ? substr($time,0,5).substr($time,-3) : substr($time,0,7)).' seconds',1);
                    if(!$cnt) continue;

                    echo '<p>';
                    $ths = array_keys($result[0]);
                    echo '<table class="inline">';
                    echo '<tr>';
                    foreach($ths as $th){
                        echo '<th>'.hsc($th).'</th>';
                    }
                    echo '</tr>';
                    foreach($result as $row){
                        echo '<tr>';
                        $tds = array_values($row);
                        foreach($tds as $td){
                            echo '<td>'.hsc($td).'</td>';
                        }
                        echo '</tr>';
                    }
                    echo '</table>';
                    echo '</p>';
                }

            }

            echo '</div>';
        }
    }

    function getTOC(){
        global $conf;
        global $ID;

        $toc = array();
        $fileextensions = array('sqlite2'=>'.sqlite','sqlite3'=>'.sqlite3');

        foreach($fileextensions as $dbformat => $fileextension){
            $toc[] = array(
                            'link'  => '',
                            'title' => $dbformat.':',
                            'level' => 1,
                            'type'  => 'ul',
                         );

            $dbfiles = glob($conf['metadir'].'/*'.$fileextension);

            if(is_array($dbfiles)) foreach($dbfiles as $file){
                $db = basename($file,$fileextension);
                $toc[] = array(
                            'link'  => wl($ID,array('do'=>'admin','page'=>'sqlite','db'=>$db,'version'=>$dbformat,'sectok'=>getSecurityToken())),
                            'title' => $this->getLang('db').' '.$db,
                            'level' => 2,
                            'type'  => 'ul',
                         );
            }
        }

        return $toc;
    }
}

// vim:ts=4:sw=4:et:
