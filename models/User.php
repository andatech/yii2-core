<?php
namespace anda\core\models;

use Yii;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;
use yii\helpers\FileHelper;
use yii\helpers\ArrayHelper;


/**
 * User model
 *
 * @property integer $id
 * @property string $username
 * @property string $password_hash
 * @property string $password_reset_token
 * @property string $email
 * @property string $auth_key
 * @property integer $status
 * @property integer $created_at
 * @property integer $updated_at
 * @property string $password write-only password
 */
class User extends ActiveRecord implements IdentityInterface
{
    const STATUS_DELETED = 0;
    const STATUS_WAITING = 1;
    const STATUS_BANNED = 2;
    const STATUS_ACTIVE = 10;

    public $currentPassword;
    public $newPassword;
    public $newPasswordConfirm;

    protected $module;

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->module = Yii::$app->getModule('user');
        parent::init();
    }

    public function test()
    {
        return Yii::$app->user->identityClass;
    }


    public static function tableName()
    {
        return '{{%user}}';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::className(),
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['username', 'email'], 'required'],
            ['status', 'default', 'value' => self::STATUS_WAITING],
            ['status', 'in', 'range' => [
                self::STATUS_ACTIVE, 
                self::STATUS_WAITING, 
                self::STATUS_BANNED, 
                self::STATUS_DELETED
            ]],

            [['currentPassword', 'newPassword', 'newPasswordConfirm'], 'required', 'on' => 'changePassword'],
            [['newPassword', 'newPasswordConfirm'], 'required', 'on' => 'create'],
            [['currentPassword'], 'validateCurrentPassword'],
            [['newPassword', 'newPasswordConfirm'], 'string', 'min'=>3,],
            [['newPassword', 'newPasswordConfirm'], 'filter', 'filter'=>'trim',],
            ['newPasswordConfirm', 'compare', 'compareAttribute'=>'newPassword', 'message'=>"Passwords don't match"],
        ];
    }

    /*public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios['changePassword'] = ['currentPassword','newPassword', 'newPasswordConfirm'];
        //Scenario Values Only Accepted
        return $scenarios;
    }*/

    /**
     * @inheritdoc
     */
    public static function findIdentity($id)
    {
        return static::findOne(['id' => $id, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        throw new NotSupportedException('"findIdentityByAccessToken" is not implemented.');
    }

    /**
     * Finds user by username
     *
     * @param string $username
     * @return static|null
     */
    public static function findByUsername($username)
    {
        return static::findOne(['username' => $username, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * Finds user by password reset token
     *
     * @param string $token password reset token
     * @return static|null
     */
    public static function findByPasswordResetToken($token)
    {
        if (!static::isPasswordResetTokenValid($token)) {
            return null;
        }

        return static::findOne([
            'password_reset_token' => $token,
            'status' => self::STATUS_ACTIVE,
        ]);
    }

    /**
     * Finds out if password reset token is valid
     *
     * @param string $token password reset token
     * @return boolean
     */
    public static function isPasswordResetTokenValid($token)
    {
        if (empty($token)) {
            return false;
        }

        $timestamp = (int) substr($token, strrpos($token, '_') + 1);
        $expire = Yii::$app->params['user.passwordResetTokenExpire'];
        return $timestamp + $expire >= time();
    }

    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->getPrimaryKey();
    }

    /**
     * @inheritdoc
     */
    public function getAuthKey()
    {
        return $this->auth_key;
    }

    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return boolean if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    /**
     * Generates password hash from password and sets it to the model
     *
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    /**
     * Generates "remember me" authentication key
     */
    public function generateAuthKey()
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }

    /**
     * Generates new password reset token
     */
    public function generatePasswordResetToken()
    {
        $this->password_reset_token = Yii::$app->security->generateRandomString() . '_' . time();
    }

    /**
     * Removes password reset token
     */
    public function removePasswordResetToken()
    {
        $this->password_reset_token = null;
    }


    public function validateCurrentPassword()
    {
        if(!$this->verifyPassword($this->currentPassword)){
            $this->addError('currentPassword', 'Current password incorrect.');
        }
    }

    public function verifyPassword($password)
    {
        $dbpassword = static::findOne(['username' => Yii::$app->user->identity->username, 'status' => self::STATUS_ACTIVE])->password_hash;

        return Yii::$app->security->validatePassword($password, $dbpassword);
    }


    /*************EVENT**********************/
    public function afterSave($insert, $changedAttributes )
    {
        if($insert){
            $profile = new Profile();
            $profile->user_id = $this->id;

            $profile->load(Yii::$app->request->post());
            if($profile->save()) {
                $this->prepareUserDir();
            }else{
                throw new NotSupportedException('Save profile ERROR.');
            }

            parent::afterSave($insert, $changedAttributes);
        }
    }

    public function afterDelete()
    {
        $profile = Profile::findOne(['user_id' => $this->id]);
        $profile->delete();

        $removeDir = rtrim(Yii::$app->controller->module->userUploadDir, '/').'/'.$this->id;
        FileHelper::removeDirectory($removeDir);

        parent::afterDelete();
    }

    /******************EVENT********/

    public function prepareUserDir()
    {
        $baseDir = rtrim(Yii::$app->controller->module->userUploadDir, '/').'/'.$this->id;
        $dirs = ['avatar','cover',];
        foreach ($dirs as $key => $dir) {
            FileHelper::createDirectory($baseDir.'/'.$dir);
        }
    }

    public function getProfile()
    {
        return $this->hasOne(\mirage\user\models\Profile::className(), ['user_id' => 'id']);
    }

    public function getStatusList()
    {
        return[
            self::STATUS_ACTIVE => 'Active', 
            self::STATUS_WAITING => 'Waiting', 
            self::STATUS_BANNED => 'Banned', 
            self::STATUS_DELETED => 'Deleted'
        ];
    }

    public function getStatusName()
    {
        $list = $this->getStatusList();
        if(array_key_exists($this->status, $list)){
            return $list[$this->status];
        }

        return null;
    }

















    public static function getUserList()
    {
        $model = self::find()->all();

        return ArrayHelper::map($model, 'id', 'profile.fullname');
    }

    public function userData()
    {
        if(Yii::$app->user->isGuest) return null;

        $this->verifyProfile();
        $this->verifyUserDir();

        
        return (object)[
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'firstname' => $this->profile->firstname,
            'lastname' => $this->profile->lastname,
            'fullname' => $this->profile->fullname,
            'avatar' => $this->profile->avatar,
            'cover' => $this->profile->cover,
            'bio' => $this->profile->bio,
            'data' => $this->profile->data,
            'roles' => Yii::$app->authManager->getRoles($this->id),
        ];
    }
    
    public function userInfo()
    {
        //print_r(Yii::$app->controller->module->params);   
        if(Yii::$app->user->isGuest) return null;
        
        $userData = $this->userData();

        $userUploadPath = $this->module->userUploadDir;
        $userUploadPath .= '/'.$this->module->userUploadPath.'/'.$userData->id;
        //$userUploadPath = $userData->id;

        $userData->firstname = $this->verifyValue($userData->firstname);
        $userData->lastname = $this->verifyValue($userData->lastname);
        $userData->fullname = $this->verifyValue($userData->fullname);
        $userData->avatar = $this->verifyImage($userUploadPath.'/avatar/'.$userData->avatar, 'default-avatar.jpg');
        $userData->cover = $this->verifyImage($userUploadPath.'/cover/'.$userData->cover, 'default-cover.jpg');
        $userData->bio = $this->verifyValue($userData->bio);
        $userData->data = $this->verifyValue($userData->data);
        $userData->roles = (count($userData->roles) > 0) ? $userData->roles : [(object)['name' => null]];

        return $userData;
    }

    private function verifyValue($val)
    {
        return ($val === null) ? 'Not set' : $val;
    }

    private function verifyImage($val, $defaultImage = 'no-image.jpg')
    {
        if(is_file($val)){
            $file = realpath($val);
            $webPath = realpath($this->module->userUploadDir);
            $fileUrl = str_replace($webPath, '', $file);
            $fileUrl = str_replace('\\', '/', $fileUrl);
            return $fileUrl;
        }else{
            $assetDir = Yii::$app->assetManager->getPublishedUrl('@mirage/user/client');
            return $assetDir.'/images/'.$defaultImage;
        }
    }


    protected function verifyProfile()
    {
        if($this->profile){
            return true;
        }else{
            $model = new \mirage\user\models\Profile();
            $model->user_id = $this->id;
            $model->firstname = null;
            $model->lastname = null;
            $model->avatar = null;
            $model->cover = null;
            $model->bio = null;
            $model->data = null;
            $model->save();

            
            return false;
        }
    }

    private function verifyUserDir()
    {
        $userDir = $this->module->userUploadDir.'/'.$this->module->userUploadPath.'/'.$this->id;

        $dirs = [
            'avatar' => 'avatar',
            'cover' => 'cover',
        ];

        foreach ($dirs as $key => $dir) {
            $createDir = $userDir.'/'.$dir;

            if(!is_dir($createDir)){
                FileHelper::createDirectory($createDir);
            }
        }
    }


    public static function getUserApi($id = null)
    {
        Yii::$app->assetManager->publish('@mirage/user/client');
        if($id === null){
            $model = static::findOne(Yii::$app->user->id);
        }else{
            $model = static::findOne($id);
        }
        return (object)[
            'data' => $model->userData(),
            'info' => $model->userInfo(),
        ];
    }
}
