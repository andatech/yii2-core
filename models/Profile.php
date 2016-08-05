<?php

namespace anda\core\models;

use Yii;

/**
 * This is the model class for table "user_profile".
 *
 * @property integer $user_id
 * @property string $firstname
 * @property string $lastname
 * @property string $avatar
 * @property string $cover
 * @property string $bio
 * @property string $data
 */
class Profile extends \yii\db\ActiveRecord
{
    //public $coverImage;

    //public $avatarImage;

    public $imageFile;
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'user_profile';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id',], 'required'],
            [['user_id'], 'integer'],
            [['bio', 'data'], 'string'],
            [['firstname', 'lastname', 'avatar', 'cover'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'user_id' => 'User ID',
            'firstname' => 'ชื่อ',
            'lastname' => 'นามสกุล',
            'avatar' => 'รูปประจำตัว',
            'cover' => 'รูปหน้าปก',
            'bio' => 'ประวัติ',
            'data' => 'ข้อมูลอื่นๆ',
        ];
    }
    
    public function getUser()
    {
        return $this->hasOne(\mirage\user\models\User::className(), ['id' => 'user_id']);
    }
    
    public function info()
    {
        $userUploadDir = Yii::$app->homeUrl.'uploads/user/'.Yii::$app->user->id;
        $avatar = $userUploadDir.'/avatar/'.$this->avatar;
        $cover = $userUploadDir.'/cover/'.$this->cover;
        return (object)[
            'id' => $this->user_id,
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'fullname' => $this->firstname.' '.$this->lastname,
            'avatar' => $avatar,
            'cover' => $cover,
            'bio' => $this->bio,
            'data' => $this->data,
            'user' => $this->user,
        ];
    }

    public function covers()
    {
        $cover_dir = Yii::getAlias('@webroot/uploads/user/').$this->user_id.'/cover';
        $files=\yii\helpers\FileHelper::findFiles($cover_dir,['recursive'=>false, 'only'=>['*.jpg','*.png']]);
        $file_url = [];
        foreach ($files as $key => $file) {
            $file_url[] = str_replace(Yii::getAlias('@webroot/'), '', $file);
        }
        
        return $file_url;
    }



    public function upload($path)
    {
        $dir = Yii::getAlias('@webroot/uploads/user/').$this->user_id.'/'.$path.'/';
        if ($this->validate()) {
            $this->imageFile->saveAs($dir . $this->imageFile->baseName . '.' . $this->imageFile->extension);
            return true;
        } else {
            return false;
        }
    }



    public function getFullName() {
        if($this->firstname === null && $this->lastname === null){
            return null;
        }
        return $this->firstname . ' ' . $this->lastname;
    }
}
