<?php
namespace anda\core\helpers;

/**
 * Description of Html
 *
 * @author madone
 */
class Html extends \yii\helpers\Html{

    public static function a($text,$url=null,$options=[]){
        if(in_array($url, [null,'','#'])){
            return parent::a($text,$url,$options);
        }
        return parent::a($text,$url,$options);       
    }
    
}
