<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2007, Akelos Media, S.L.  & Bermi Ferrer Martinez |
// | Released under the GNU Lesser General Public License, see LICENSE.txt|
// +----------------------------------------------------------------------+
/**
 see http://svn.viney.net.nz/things/rails/plugins/acts_as_taggable_on_steroids/lib/acts_as_taggable.rb
*/
/**
* @package ActiveRecord
* @subpackage Behaviours
* @author Arno Schneider <arno a.t. bermilabs dot com>
* @copyright Copyright (c) 2002-2007, Akelos Media, S.L. http://www.akelos.org
* @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
*/

require_once(AK_LIB_DIR.DS.'AkActiveRecord'.DS.'AkObserver.php');


class ActsAsSluggable extends AkObserver
{
    var $_instance;
    var $_slug_source;
    var $_slug_target;
    var $_validated = false;
    var $custom_replacements = array();

    function ActsAsSluggable(&$ActiveRecordInstance, $options = array())
    {
        $this->_instance = &$ActiveRecordInstance;
        $this->init($options);
    }
    
    function init($options = array())
    {
        if (isset($options['slug_source'])) {
            $this->_slug_source = $options['slug_source'];
        }
        if (isset($options['slug_target'])) {
            $this->_slug_target = $options['slug_target'];
        }
        if (isset($options['replacements']) && is_array($options['replacements'])) {
            $this->custom_replacements = $options['replacements'];
        }
        if (isset($this->_slug_source) && isset($this->_slug_target)) {
            $this->observe(&$this->_instance);
        }
    }

    function afterValidation(&$record)
    {
        return $this->_createSlug(&$record);
    }
    
    
    function _generateSlug(&$record)
    {
        $sourceData = false;
        $slug = false;
        if (method_exists($record,$this->_slug_source)) {
            $sourceData = $record->{$this->_slug_source}();
        } else if (isset($record->{$this->_slug_source})) {
            $sourceData = $record->{$this->_slug_source};
        }
        if (!empty($sourceData) && $record->hasColumn($this->_slug_target)) {
            $slug = $this->_getUrlSafeName(&$record,$sourceData);
            $slug = $this->_getUniqueSlug(&$record, $slug);
        }
        return $slug;
    }
    function _createSlug(&$record)
    {
        $slug = $this->_generateSlug(&$record);
        if ($slug!==false) {
            $record->set($this->_slug_target,$slug);
        }
        return true;
    }
    
    function _getUniqueSlug(&$record, $slug)
    {
        $tries = 0;
        $orgSlug = $slug;
        while (($found = $record->findFirst($this->_slug_target.' = ? AND id <> ?',$slug,$record->id==null?-1:$record->id))) {
            $slug =$orgSlug.'-'.$tries;
            $tries++;
            if($tries>20) {
                $slug = $orgSlug.'-'.md5(time());
                break;
            }
        }
        return $slug;
        
    }
    
    function _getUrlSafeName(&$record,$name)
    {
        $safe_name = strtolower($name);
        $renderTo = array();
        $unsafe = explode(" ", "À Á Â Ã Ä Å A A Ç C C D D Ð È É Ê Ë E E G Ì Í
        Î Ï I L L L Ñ N N Ò Ó Ô Õ Ö Ø O R R S S S T T Ù Ú Û Ü U U Ý Z Z Z à á â ã ä
        å a a ç c c d d è é ê ë e e g ì í î ï i l l l ñ n n ð ò ó ô õ ö ø o r r s s
        s t t ù ú û ü u u ý ÿ z z z");
        $safe = explode(" ", "A A A A A A A A C C C D D D E E E E E E G I I
        I I I L L L N N N O O O O O O O R R S S S T T U U U U U U Y Z Z Z a a a a a
        a a a c c c d d e e e e e e g i i i i i l l l ny n n o o o o o o o o r r s s
        s t t u u u u u u y y z z z");
        for ($i=0; $i<count($unsafe); $i++) {
            $renderTo[$unsafe[$i]] = $safe[$i];
        }
        $ligatures = array("Æ"=>"Ae", "æ"=>"ae",
                           "ß"=>"ss");
        $umlaute = array("Ä"=>"Ae", "ä"=>"ae", "Ö"=>"Oe", "ö"=>"oe", "Ü"=>"Ue",
                         "ü"=>"ue");
        $specialCharsUnsafe =array("¡","!"," ","`","]","[","~","^","\\","|","}","{","%","#",">","<",
                                       "'",'"',"",'@','$','&',
                                       ',','/',':',';','=','?');
        $specialCharsSafe = array("-exclamation-","-exclamation-","-","-accent-","-rbracket-","-lbracket-","-tilde-","-caret-","-backslash-","-pipe-","-rbrace-","-lbrace-","-percent-","-hash-","-more-","-less-",
                                  "","",'-','-at-','-dollar-','-and-',
                                  '-comma-','-or-','-colon-','-semicolon-','-equals-','-question-');
        
        for ($i=0; $i<count($specialCharsUnsafe); $i++) {
            $renderTo[$specialCharsUnsafe[$i]] = $specialCharsSafe[$i];
        }
        $renderTo = array_merge($renderTo, $umlaute);
        $renderTo = array_merge($renderTo, $ligatures);
        $renderTo = array_merge($renderTo, $ligatures);

        $keys = array_keys($renderTo);
        $values = array_values($renderTo);
        foreach (array_keys($record->sluggable->custom_replacements) as $customKey) {
            array_unshift($keys,$customKey);
            array_unshift($values,$record->sluggable->custom_replacements[$customKey]);
        }
        $safe_name = str_replace($keys,
                                 $values,
                                 $safe_name);
        $safe_name = preg_replace('/-+/','-',$safe_name);
        $safe_name = trim($safe_name,'-');
        return $safe_name;
    }
}
?>