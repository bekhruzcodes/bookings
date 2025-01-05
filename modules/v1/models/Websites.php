<?php

namespace app\modules\v1\models;

use Yii;
use yii\web\IdentityInterface;

/**
 * This is the model class for table "websites".
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $access_token
 * @property string|null $created_at
 * @property string|null $updated_at
 *
 * @property Bookings[] $bookings
 */
class Websites extends \yii\db\ActiveRecord implements IdentityInterface
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'websites';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'email', 'access_token'], 'required'],
            [['created_at', 'updated_at'], 'safe'],
            [['name', 'email', 'access_token'], 'string', 'max' => 255],
            [['email'], 'unique'],
            [['access_token'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'email' => 'Email',
            'access_token' => 'Access Token',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function findIdentity($id)
    {
        return static::findOne($id);
    }

    /**
     * {@inheritdoc}
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
// Find the website by access token
        return static::findOne(['access_token' => $token]);
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthKey()
    {
// Auth key can be used for additional checks but is not needed in this case
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function validateAuthKey($authKey)
    {
// You can implement additional validation here if needed
        return true;
    }

    /**
     * Gets query for [[Bookings]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getBookings()
    {
        return $this->hasMany(Bookings::class, ['website_id' => 'id']);
    }

    /**
     * Generate access token
     */
    public function generateAccessToken()
    {
        $this->access_token = Yii::$app->security->generateRandomString(40);
    }
}
