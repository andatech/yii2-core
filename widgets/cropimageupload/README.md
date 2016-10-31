cropped image upload extension for Yii2
======================


Widget ตัวนี้ได้คัดลอกมาจาก [karpoff/yii2-crop-image-upload](https://github.com/karpoff/yii2-crop-image-upload).
และได้ปรับปรุงโค๊ดให้รองรับการ Upload และ เรียก Url ไฟล์อยู่ภายไต้โฟลเดอร์ของค่าฟิลด์ในตารางได้

ตัวอย่างของเดิมกำหนด Path เป็น "url" => "/uploads/user/{id}" เมื่อ process ก็จะได้ image url = /uploads/user/{id}/avatar.jpg
จึงได้นำมาปรับปรุง Code แล้วจะได้ค่า image url = /uploads/user/5/avatar.jpg

และได้เพิ่มความสามารถให้ resize[width, height] รูปตามขนาดที่ต้องการหลังจากการอัพโหลดได้


Usage
-----

### Upload image and create crop

Attach the behavior in your model:

```php
//Model

use anda\core\widgets\cropimageupload\CropImageUploadBehavior;

...

class Document extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['photo', 'file', 'extensions' => 'jpg, jpeg, gif, png', 'on' => ['insert', 'update']],
        ];
    }

    /**
     * @inheritdoc
     */
    function behaviors()
    {
        return [
            [
                'class' => CropImageUploadBehavior::className(),
                'attribute' => 'photo',
                'scenarios' => ['insert', 'update'],
                'path' => '@webroot/uploads/user/{user_id}/avatars',
                'url' => '@web/uploads/user/{user_id}/avatars',
				'ratio' => 1,
				'resize' => [200, 200],
				'crop_field' => 'photo_crop',
				'cropped_field' => 'photo_cropped',
            ],
        ];
    }
}
```

Example view file:

```php
<?php use anda\core\widgets\cropimageupload\CropImageUpload;; ?>

<?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>
    <?= $form->field($model, 'photo')->widget(CropImageUpload::className()) ?>
    <div class="form-group">
        <?= Html::submitButton('Submit', ['class' => 'btn btn-primary']) ?>
    </div>
<?php ActiveForm::end(); ?>
```
