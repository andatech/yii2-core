<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 * 
 */

namespace anda\core\controllers;

use Yii;

/**
 * Description of BasicConcontroller
 *
 * @author Andasoft
 */
class AuthenController extends BaseController {

    public $redirectLogin = null;

    /**
     * 
     * @param type $action 
     * @return boolean
     */
    public function beforeAction($action) {
        // Yii::$app->user->loginUrl[0] ดูที่ config.php
        $this->redirectLogin = ($this->redirectLogin === null) ? Yii::$app->user->loginUrl[0] : $this->redirectLogin;

        if (!parent::beforeAction($action))
            return false;


        if (Yii::$app->requestedRoute === Yii::$app->user->loginUrl[0]) {
            return true;
        }

        if (Yii::$app->user->isGuest) {
            Yii::$app->user->setReturnUrl(Yii::$app->request->url);
            Yii::$app->getResponse()->redirect([$this->redirectLogin])->send();
            return false;
        }
        return true;
    }

}
